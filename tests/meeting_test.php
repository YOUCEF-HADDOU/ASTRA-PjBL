<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for mrproject meetings
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;

global $CFG;
require_once($CFG->dirroot . '/mod/mrproject/locallib.php');

/**
 * Unit tests for the mrproject_meetings class.
 *
 * @group     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mrproject_meeting_testcase extends advanced_testcase {

    /**
     * @var int Course_modules id used for testing
     */
    protected $moduleid;

    /**
     * @var int Course id used for testing
     */
    protected $courseid;

    /**
     * @var int mrproject id used for testing
     */
    protected $mrprojectid;

    /**
     * @var int User id of teacher used for testing
     */
    protected $teacherid;

    /**
     * @var int a meeting used for testing
     */
    protected $meetingid;

    /**
     * @var int[] tasks used for testing
     */
    protected $taskids;

    /**
     * @var int[] id of students used for testing
     */
    protected $students;

    protected function setUp() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        $this->students = array();
        for ($i = 0; $i < 3; $i++) {
            $this->students[$i] = $this->getDataGenerator()->create_user()->id;
        }

        $options = array();
        $options['meetingtimes'] = array();
        $options['meetingstudents'] = array();
        $options['meetingtimes'][0] = time() + DAYSECS;
        $options['meetingstudents'][0] = $this->students;

        $mrproject = $this->getDataGenerator()->create_module('mrproject', array('course' => $course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $mrproject->cmid));

        $this->mrprojectid = $mrproject->id;
        $this->moduleid  = $coursemodule->id;
        $this->courseid  = $coursemodule->course;
        $this->teacherid = 2;  // Admin user.
        $this->meetingid = $DB->get_field('mrproject_meeting', 'id', array('mrprojectid' => $mrproject->id), MUST_EXIST);
        $this->taskids = array_keys($DB->get_records('mrproject_task', array('meetingid' => $this->meetingid)));
    }

    /**
     * Assert that a record is present in the DB
     *
     * @param string $table name of table to test
     * @param int $id id of record to look for
     * @param string $msg message
     */
    private function assert_record_present($table, $id, $msg = "") {
        global $DB;

        $ex = $DB->record_exists($table, array('id' => $id));
        $this->assertTrue($ex, "Checking whether record $id is present in table $table: $msg");
    }

    /**
     * Assert that a record is absent from the DB
     *
     * @param string $table name of table to test
     * @param int $id id of record to look for
     * @param string $msg message
     */
    private function assert_record_absent($table, $id, $msg = "") {
        global $DB;

        $ex = $DB->record_exists($table, array('id' => $id));
        $this->assertFalse($ex, "Checking whether record $id is absent in table $table: $msg");
    }

    /**
     * Test creating a meeting with tasks
     */
    public function test_create() {

        global $DB;

        $mrproject = mrproject::load_by_id($this->mrprojectid);
        $meeting = $mrproject->create_meeting();

        $meeting->teacherid = $this->getDataGenerator()->create_user()->id;
        $meeting->starttime = time();
        $meeting->duration = 60;

        $newapp1 = $meeting->create_task();
        $newapp1->studentid = $this->getDataGenerator()->create_user()->id;
        $newapp2 = $meeting->create_task();
        $newapp2->studentid = $this->getDataGenerator()->create_user()->id;

        $meeting->save();

        $newid = $meeting->get_id();
        $this->assertNotEquals(0, $newid, "Checking meeting id after creation");

        $newcnt = $DB->count_records('mrproject_task', array('meetingid' => $newid));
        $this->assertEquals(2, $newcnt, "Counting number of tasks after addition");

    }


    /**
     * Test deleting a meeting and associated data
     */
    public function test_delete() {

        $mrproject = mrproject::load_by_id($this->mrprojectid);

        // Make sure calendar events are all created.
        $meeting = meeting::load_by_id($this->meetingid, $mrproject);
        $start = $meeting->starttime;
        $meeting->save();

        // Load again, to delete.
        $meeting = meeting::load_by_id($this->meetingid, $mrproject);
        $meeting->delete();

        $this->assert_record_absent('mrproject_meeting', $this->meetingid);
        foreach ($this->taskids as $id) {
            $this->assert_record_absent('mrproject_task', $id);
        }

        $this->assert_event_absent($this->teacherid, $start, "");
        foreach ($this->students as $student) {
            $this->assert_event_absent($student, $start, "");
        }

    }

    /**
     * Test adding an task to a meeting.
     */
    public function test_add_task() {

        global $DB;

        $mrproject = mrproject::load_by_id($this->mrprojectid);
        $meeting = meeting::load_by_id($this->meetingid, $mrproject);

        $oldcnt = $DB->count_records('mrproject_task', array('meetingid' => $meeting->get_id()));
        $this->assertEquals(3, $oldcnt, "Counting number of tasks before addition");

        $newapp = $meeting->create_task();
        $newapp->studentid = $this->getDataGenerator()->create_user()->id;

        $meeting->save();

        $newcnt = $DB->count_records('mrproject_task', array('meetingid' => $meeting->get_id()));
        $this->assertEquals(4, $newcnt, "Counting number of tasks after addition");

    }

    /**
     * Test removing an task from a meeting.
     */
    public function test_remove_task() {

        global $DB;

        $mrproject = mrproject::load_by_id($this->mrprojectid);
        $meeting = meeting::load_by_id($this->meetingid, $mrproject);

        $apps = $meeting->get_tasks();
        $task = array_pop($apps);
        $delid = $task->get_id();

        $this->assert_record_present('mrproject_task', $delid);

        $meeting->remove_task($task);
        $meeting->save();

        $this->assert_record_absent('mrproject_task', $delid);
    }

    /**
     * Test presence or absence of event records when tasks are modified.
     */
    public function test_calendar_events() {
        global $DB;

        $mrproject = mrproject::load_by_id($this->mrprojectid);
        $meeting = meeting::load_by_id($this->meetingid, $mrproject);
        $meeting->save();

        $oldstart = $meeting->starttime;

        $this->assert_event_exists($this->teacherid, $meeting->starttime, "Meeting with your Students");
        foreach ($this->students as $student) {
            $this->assert_event_exists($student, $meeting->starttime, "Meeting with your Teacher");
        }

        $newstart = time() + 3 * DAYSECS;
        $meeting->starttime = $newstart;
        $meeting->save();

        foreach ($this->students as $student) {
            $this->assert_event_absent($student, $oldstart);
            $this->assert_event_exists($student, $newstart, "Meeting with your Teacher");
        }
        $this->assert_event_absent($this->teacherid, $oldstart);
        $this->assert_event_exists($this->teacherid, $newstart, "Meeting with your Students");

        // Delete one of the tasks.
        $app = $meeting->get_task($this->taskids[0]);
        $meeting->remove_task($app);
        $meeting->save();

        $this->assert_event_absent($this->students[0], $newstart);
        $this->assert_event_exists($this->students[1], $newstart, "Meeting with your Teacher");
        $this->assert_event_exists($this->teacherid, $newstart, "Meeting with your Students");

        // Delete all tasks.
        $DB->delete_records('mrproject_task', array('meetingid' => $this->meetingid));
        $meeting = meeting::load_by_id($this->meetingid, $mrproject);
        $meeting->save();

        foreach ($this->students as $student) {
            $this->assert_event_absent($student, $newstart);
        }
        $this->assert_event_absent($this->teacherid, $newstart);

    }

    /**
     * Assert that a calendar event exists in the DB.
     *
     * @param int $userid user associated with event
     * @param int $time start time of the event
     * @param string $titlestart beginning of the title of the event
     */
    private function assert_event_exists($userid, $time, $titlestart) {
        global $DB;
        $events = calendar_get_events($time - MINSECS, $time + HOURSECS, $userid, false, false);
        $this->assertEquals(1, count($events), "Expecting exactly one event at time $time for user $userid");
        $event = array_pop($events);
        $this->assertEquals($time, $event->timestart);
        $this->assertEquals('mrproject', $event->modulename);
        $this->assertTrue(strpos($event->name, $titlestart) === 0, "Checking event title start: $titlestart");
    }

    /**
     * Assert that a calendar event at a certain time is absent from the DB.
     *
     * @param int $userid user id associated with event
     * @param int $time start time of the event
     */
    private function assert_event_absent($userid, $time) {
        $events = calendar_get_events($time - MINSECS, $time + HOURSECS, $userid, false, false);
        $this->assertEquals(0, count($events), "Expecting no event at time $time for user $userid");
    }
}
