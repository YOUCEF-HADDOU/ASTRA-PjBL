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
 * Unit tests for the mrproject class.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;
use \mod_mrproject\model\task;

global $CFG;
require_once($CFG->dirroot . '/mod/mrproject/locallib.php');

/**
 * Unit tests for the mrproject class.
 *
 * @group mod_mrproject
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mrproject_mrproject_testcase extends advanced_testcase {

    /**
     * @var int Course_module id used for testing
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
     * @var int One of the meetings used for testing
     */
    protected $meetingid;

    protected function setUp() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $this->courseid  = $course->id;

        $options = array();
        $options['meetingtimes'] = array();
        $options['meetingstudents'] = array();
        for ($c = 0; $c < 4; $c++) {
            $options['meetingtimes'][$c] = time() + ($c + 1) * DAYSECS;
            $options['meetingstudents'][$c] = array($this->getDataGenerator()->create_user()->id);
        }
        $options['meetingtimes'][4] = time() + 10 * DAYSECS;
        $options['meetingtimes'][5] = time() + 11 * DAYSECS;
        $options['meetingstudents'][5] = array(
                                        $this->getDataGenerator()->create_user()->id,
                                        $this->getDataGenerator()->create_user()->id
                                      );

        $mrproject = $this->getDataGenerator()->create_module('mrproject', array('course' => $course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $mrproject->cmid));

        $this->mrprojectid = $mrproject->id;
        $this->moduleid  = $coursemodule->id;

        $recs = $DB->get_records('mrproject_meeting', array('mrprojectid' => $mrproject->id), 'id DESC');
        $this->meetingid = array_keys($recs)[0];
        $this->taskids = array_keys($DB->get_records('mrproject_task', array('meetingid' => $this->meetingid)));
    }

    /**
     * Create a student record and enrol him in a course.
     *
     * @param int $courseid
     * @return int user id
     */
    private function create_student($courseid = 0) {
        if ($courseid == 0) {
            $courseid = $this->courseid;
        }
        $userid = $this->getDataGenerator()->create_user()->id;
        $this->getDataGenerator()->enrol_user($userid, $courseid);
        return $userid;
    }

    /**
     * Assert a record count in the database.
     *
     * @param string $table table name to test
     * @param string $field field name
     * @param string $value value to look for
     * @param int $expect expected record count where that field has that value
     */
    private function assert_record_count($table, $field, $value, $expect) {
        global $DB;

        $act = $DB->count_records($table, array($field => $value));
        $this->assertEquals($expect, $act, "Checking whether table $table has $expect records with $field equal to $value");
    }

    /**
     * Test a mrproject instance
     */
    public function test_mrproject() {
        global $DB;

        $dbdata = $DB->get_record('mrproject', array('id' => $this->mrprojectid));

        $instance = mrproject::load_by_coursemodule_id($this->moduleid);

        $this->assertEquals($dbdata->name, $instance->get_name());

    }

    /**
     * Test the loading of meetings
     */
    public function test_load_meetings() {
        global $DB;

        $instance = mrproject::load_by_coursemodule_id($this->moduleid);

        /* test meeting retrieval */

        $meetingcount = $instance->get_meeting_count();
        $this->assertEquals(6, $meetingcount);

        $meetings = $instance->get_all_meetings(2, 3);
        $this->assertEquals(3, count($meetings));

        $meetings = $instance->get_meetings_without_task();
        $this->assertEquals(1, count($meetings));

        $allmeetings = $instance->get_all_meetings();
        $this->assertEquals(6, count($allmeetings));

        $cnt = 0;
        foreach ($allmeetings as $meeting) {
            $this->assertTrue($meeting instanceof meeting);

            if ($cnt == 5) {
                $expectedapp = 2;
            } else if ($cnt == 4) {
                $expectedapp = 0;
            } else {
                $expectedapp = 1;
            }
            $this->assertEquals($expectedapp, $meeting->get_task_count());

            $apps = $meeting->get_tasks();
            $this->assertEquals($expectedapp, count($apps));

            foreach ($apps as $app) {
                $this->assertTrue($app instanceof task);
            }
            $cnt++;
        }

    }

    /**
     * Test adding meetings to a mrproject
     */
    public function test_add_meeting() {

        $mrproject = mrproject::load_by_coursemodule_id($this->moduleid);

        $newmeeting = $mrproject->create_meeting();
        $newmeeting->teacherid = $this->getDataGenerator()->create_user()->id;
        $newmeeting->starttime = time() + MINSECS;
        $newmeeting->duration = 10;

        $allmeetings = $mrproject->get_meetings();
        $this->assertEquals(7, count($allmeetings));

        $mrproject->save();

    }

    /**
     * Test deleting a mrproject
     */
    public function test_delete_mrproject() {

        $options = array();
        $options['meetingtimes'] = array();
        $options['meetingstudents'] = array();
        for ($c = 0; $c < 10; $c++) {
            $options['meetingtimes'][$c] = time() + ($c + 1) * DAYSECS;
            $options['meetingstudents'][$c] = array($this->getDataGenerator()->create_user()->id);
        }

        $delrec = $this->getDataGenerator()->create_module('mrproject', array('course' => $this->courseid), $options);
        $delid = $delrec->id;

        $delsched = mrproject::load_by_id($delid);

        $this->assert_record_count('mrproject', 'id', $this->mrprojectid, 1);
        $this->assert_record_count('mrproject_meeting', 'mrprojectid', $this->mrprojectid, 6);
        $this->assert_record_count('mrproject_task', 'meetingid', $this->meetingid, 2);

        $this->assert_record_count('mrproject', 'id', $delid, 1);
        $this->assert_record_count('mrproject_meeting', 'mrprojectid', $delid, 10);

        $delsched->delete();

        $this->assert_record_count('mrproject', 'id', $this->mrprojectid, 1);
        $this->assert_record_count('mrproject_meeting', 'mrprojectid', $this->mrprojectid, 6);
        $this->assert_record_count('mrproject_task', 'meetingid', $this->meetingid, 2);

        $this->assert_record_count('mrproject', 'id', $delid, 0);
        $this->assert_record_count('mrproject_meeting', 'mrprojectid', $delid, 0);

    }

    /**
     * Assert that meeting times have certain values
     * @param array $expected list of expected meetings
     * @param array $actual list of actual meetings
     * @param array $options expected attributes of meetings
     * @param string $message
     */
    private function assert_meeting_times($expected, $actual, $options, $message) {
        $this->assertEquals(count($expected), count($actual), "Slot count - $message");
        $meetingtimes = array();
        foreach ($expected as $e) {
            $meetingtimes[] = $options['meetingtimes'][$e];
        }
        foreach ($actual as $a) {
            $this->assertTrue( in_array($a->starttime, $meetingtimes), "Slot at {$a->starttime} - $message");
        }
    }

    /**
     * Check meetings in the mrproject for certain patterns.
     *
     * @param int $mrprojectid id of the mrproject
     * @param unknown $studentid
     * @param array $meetingoptions expected attributes of meetings
     * @param array $expattended which meetings are expected to be "attended"
     * @param array $expupcoming which meetings are expected to be "upcoming"
     * @param unknown $expavailable which meetings are expected to be "available" (including already booked ones)
     * @param unknown $expbookable  which meetings are expected to be "bookable"
     */
    private function check_timed_meetings($mrprojectid, $studentid, $meetingoptions,
                                       $expattended, $expupcoming, $expavailable, $expbookable) {

        $sched = mrproject::load_by_id($mrprojectid);

        $attended = $sched->get_attended_meetings_for_student($studentid);
        $this->assert_meeting_times($expattended, $attended, $meetingoptions, 'Attended meetings');

        $upcoming = $sched->get_upcoming_meetings_for_student($studentid);
        $this->assert_meeting_times($expupcoming, $upcoming, $meetingoptions, 'Upcoming meetings');

        $available = $sched->get_meetings_available_to_student($studentid, false);
        $this->assert_meeting_times($expavailable, $available, $meetingoptions, 'Available meetings (incl. booked)');

        $bookable = $sched->get_meetings_available_to_student($studentid, true);
        $this->assert_meeting_times($expbookable, $bookable, $meetingoptions, 'Booked meetings');

    }

    /**
     * Test meeting timings when parameters of the mrproject are altered.
     */
    public function test_load_meeting_timing() {

        global $DB;

        $currentstud = $this->getDataGenerator()->create_user()->id;
        $otherstud   = $this->getDataGenerator()->create_user()->id;

        $options = array();
        $options['meetingtimes'] = array();
        $options['meetingstudents'] = array();
        $options['meetingattended'] = array();

        // Create meetings 0 to 5, n days in the future, booked by the student but not attended.
        for ($c = 0; $c <= 5; $c++) {
            $options['meetingtimes'][$c] = time() + $c * DAYSECS + 12 * HOURSECS;
            $options['meetingstudents'][$c] = $currentstud;
            $options['meetingattended'][$c] = false;
        }

        // Create meeting 6, located in the past, booked by the student but not attended.
        $options['meetingtimes'][6] = time() - 3 * DAYSECS;
        $options['meetingstudents'][6] = $currentstud;
        $options['meetingattended'][6] = false;

        // Create meeting 7, located in the past, booked by the student and attended.
        $options['meetingtimes'][7] = time() - 4 * DAYSECS;
        $options['meetingstudents'][7] = $currentstud;
        $options['meetingattended'][7] = true;

        // Create meeting 8, located less than one day in the future but marked attended.
        $options['meetingtimes'][8] = time() + 8 * HOURSECS;
        $options['meetingstudents'][8] = $currentstud;
        $options['meetingattended'][8] = true;

        // Create meeting 9, located in the future but already booked by another student.
        $options['meetingtimes'][9] = time() + 10 * DAYSECS + 9 * HOURSECS;
        $options['meetingstudents'][9] = $otherstud;
        $options['meetingattended'][9] = false;
        $options['meetingheld'][9] = 1;

        // Create meetings 10 to 14, (n-10) days in the future, open for booking.
        for ($c = 10; $c <= 14; $c++) {
            $options['meetingtimes'][$c] = time() + ($c - 10) * DAYSECS + 10 * HOURSECS;
        }

        $schedrec = $this->getDataGenerator()->create_module('mrproject', array('course' => $this->courseid), $options);
        $schedid = $schedrec->id;

        //$schedrec->guardtime = 0;
        $DB->update_record('mrproject', $schedrec);

        $this->check_timed_meetings($schedid, $currentstud, $options,
                     array(7, 8),
                     array(0, 1, 2, 3, 4, 5, 6),
                     array(10, 11, 12, 13, 14),
                     array(10, 11, 12, 13, 14, 9) );

        //$schedrec->guardtime = DAYSECS;
        $DB->update_record('mrproject', $schedrec);

        $this->check_timed_meetings($schedid, $currentstud, $options,
                     array(7, 8),
                     array(0, 1, 2, 3, 4, 5, 6),
                     array(11, 12, 13, 14),
                     array(11, 12, 13, 14, 9) );

        //$schedrec->guardtime = 4 * DAYSECS;
        $DB->update_record('mrproject', $schedrec);

        $this->check_timed_meetings($schedid, $currentstud, $options,
                     array(7, 8),
                     array(0, 1, 2, 3, 4, 5, 6),
                     array(14),
                     array(14, 9) );

        //$schedrec->guardtime = 20 * DAYSECS;
        $DB->update_record('mrproject', $schedrec);

        $this->check_timed_meetings($schedid, $currentstud, $options,
                     array(7, 8),
                     array(0, 1, 2, 3, 4, 5, 6),
                     array(),
                     array() );

    }

    /**
     * Assert the number of tasks for a student with certain properties.
     *
     * @param int $expectedwithchangeables expected number of bookable tasks, including changeable ones
     * @param int $expectedwithoutchangeables expected number of bookable tasks, excluding changeable ones
     * @param int $schedid mrproject id
     * @param int $studentid student id
     */
    private function assert_bookable_tasks($expectedwithchangeables, $expectedwithoutchangeables,
                                                  $schedid, $studentid) {
        $mrproject = mrproject::load_by_id($schedid);

        $actualwithchangeables = $mrproject->count_bookable_tasks($studentid, true);
        $this->assertEquals($expectedwithchangeables, $actualwithchangeables,
                        'Checking number of bookable tasks (including changeable bookings)');

        $actualwithoutchangeables = $mrproject->count_bookable_tasks($studentid, false);
        $this->assertEquals($expectedwithoutchangeables, $actualwithoutchangeables,
                        'Checking number of bookable tasks (excluding changeable bookings)');

        $studs = $mrproject->get_students_for_scheduling();
        if ($expectedwithoutchangeables != 0) {
            $this->assertTrue(is_array($studs), 'Checking that get_students_for_scheduling returns an array');
        }
        $actualnum = count($studs);
        $expectednum = ($expectedwithoutchangeables > 0) ? 3 : 2;
        $this->assertEquals($expectednum, $actualnum, 'Checking number of students available for scheduling');
    }

    /**
     * Creates a mrproject with certain settings,
     * having 10 tasks, from 1 hour in the future to 9 days, 1 hour in the future,
     * and booking a given student into these meetings - either unattended bookings ($bookedmeetings)
     * or attended bookings ($attendedmeetings).
     *
     * The mrproject is created in a new course, into which the given student is enrolled.
     * Also, two other students (without any meeting bookings) is created in the course.
     *
     * @param int $mrprojectmode mrproject mode
     * @param int $maxbookings max number of bookings per student
     * @param int $guardtime guard time
     * @param int $studentid student to book into meetings
     * @param array $bookedmeetings meetings to book the student in
     * @param array $attendedmeetings meetings which the student has attended
     */
    private function create_data_for_bookable_tasks($mrprojectmode, $maxbookings, $guardtime=0, $studentid,
                                                           array $bookedmeetings, array $attendedmeetings) {

        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($studentid, $course->id);

        $options['meetingtimes'] = array();
        for ($c = 0; $c < 10; $c++) {
            $options['meetingtimes'][$c] = time() + $c * DAYSECS + HOURSECS;
            if (in_array($c, $bookedmeetings) || in_array($c, $attendedmeetings)) {
                $options['meetingstudents'][$c] = $studentid;
            }
        }

        $schedrec = $this->getDataGenerator()->create_module('mrproject', array('course' => $course->id), $options);

        $mrproject = mrproject::load_by_id($schedrec->id);

        $mrproject->mrprojectmode = $mrprojectmode;
        $mrproject->maxbookings = $maxbookings;
        //$mrproject->guardtime = $guardtime;
        $mrproject->save();

        $meetingrecs = $DB->get_records('mrproject_meeting', array('mrprojectid' => $mrproject->id), 'starttime ASC');
        $meetingrecs = array_values($meetingrecs);

        foreach ($attendedmeetings as $id) {
            $DB->set_field('mrproject_task', 'attended', 1, array('meetingid' => $meetingrecs[$id]->id));
        }

        for ($i = 0; $i < 2; $i++) {
            $dummystud = $this->create_student($course->id);
        }

        return $mrproject->id;
    }

    /**
     * Test the retrieveal routines for bookable tasks.
     */
    public function test_bookable_tasks() {

        $studid = $this->create_student();

        $sid = $this->create_data_for_bookable_tasks('oneonly', 1, 0, $studid, array(), array());
        $this->assert_bookable_tasks(1, 1, $sid, $studid);

        $sid = $this->create_data_for_bookable_tasks('oneonly', 1, 0, $studid, array(5), array());
        $this->assert_bookable_tasks(1, 0, $sid, $studid);

        $sid = $this->create_data_for_bookable_tasks('oneonly', 1, 0, $studid, array(5, 6, 7), array());
        $this->assert_bookable_tasks(1, 0, $sid, $studid);

        $sid = $this->create_data_for_bookable_tasks('oneonly', 1, 0, $studid, array(5, 6), array(8));
        $this->assert_bookable_tasks(0, 0, $sid, $studid);

        // One booking inside guard time, cannot be rebooked.
        $sid = $this->create_data_for_bookable_tasks('oneonly', 1, 5 * DAYSECS, $studid, array(1), array());
        $this->assert_bookable_tasks(0, 0, $sid, $studid);

        // Five bookings allowed, three booked, one of which attended.
        $sid = $this->create_data_for_bookable_tasks('oneonly', 5, 0, $studid, array(2, 3), array(4));
        $this->assert_bookable_tasks(4, 2, $sid, $studid);

        // Five bookings allowed, three booked, one of which inside guard time.
        $sid = $this->create_data_for_bookable_tasks('oneonly', 5, 5 * DAYSECS, $studid, array(2, 7, 8), array());
        $this->assert_bookable_tasks(4, 2, $sid, $studid);

        // Five bookings allowed, four booked, of which two inside guard time (one attended), two outside guard time (one attended).
        $sid = $this->create_data_for_bookable_tasks('oneonly', 5, 5 * DAYSECS, $studid, array(2, 7), array(1, 8));
        $this->assert_bookable_tasks(2, 1, $sid, $studid);

        // One booking allowed at a time. Two attended already present (one inside GT, one outside GT).
        $sid = $this->create_data_for_bookable_tasks('onetime', 1, 5 * DAYSECS, $studid, array(), array(3, 7));
        $this->assert_bookable_tasks(1, 1, $sid, $studid);

        // One booking allowed at a time. One booked outside GT.
        $sid = $this->create_data_for_bookable_tasks('onetime', 1, 5 * DAYSECS, $studid, array(7), array());
        $this->assert_bookable_tasks(1, 0, $sid, $studid);

        // One booking allowed at a time. One booked inside GT.
        $sid = $this->create_data_for_bookable_tasks('onetime', 1, 5 * DAYSECS, $studid, array(2), array());
        $this->assert_bookable_tasks(0, 0, $sid, $studid);

    }

}
