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
 * Data provider tests.
 *
 * @package    mod_mrproject
 * @category   test
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\tests\provider_testcase;
use mod_mrproject\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

require_once($CFG->dirroot.'/mod/mrproject/locallib.php');

/**
 * Data provider testcase class.
 *
 * @group      mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mrproject_privacy_testcase extends provider_testcase {

    /**
     * @var int course_module id used for testing
     */
    protected $moduleid;

    /**
     * @var the module context used for testing
     */
    protected $context;

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

    /**
     * @var int first student used in testing - a student that has an task
     */
    protected $student1;

    /**
     * @var int second student used in testing - a student that has an task
     */
    protected $student2;

    /**
     * @var array all students (only id) involved in the mrproject
     */
    protected $allstudents;

    protected function setUp() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $this->courseid  = $course->id;

        $this->student1 = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
        $this->allstudents = [$this->student1->id, $this->student2->id];

        $options = array();
        $options['meetingtimes'] = array();
        $options['meetingstudents'] = array();
        for ($c = 0; $c < 4; $c++) {
            $options['meetingtimes'][$c] = time() + ($c + 1) * DAYSECS;
            $stud = $this->getDataGenerator()->create_user()->id;
            $this->allstudents[] = $stud;
            $options['meetingstudents'][$c] = array($stud);
        }
        $options['meetingtimes'][4] = time() + 10 * DAYSECS;
        $options['meetingtimes'][5] = time() + 11 * DAYSECS;
        $options['meetingstudents'][5] = array(
                                        $this->student1->id,
                                        $this->student2->id
                                      );

        $mrproject = $this->getDataGenerator()->create_module('mrproject', array('course' => $course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $mrproject->cmid));

        $this->mrprojectid = $mrproject->id;
        $this->moduleid  = $coursemodule->id;
        $this->context = context_module::instance($mrproject->cmid);

        $recs = $DB->get_records('mrproject_meeting', array('mrprojectid' => $mrproject->id), 'id DESC');
        $this->meetingid = array_keys($recs)[0];
        $this->taskids = array_keys($DB->get_records('mrproject_task', array('meetingid' => $this->meetingid)));
    }

    /**
     * Asserts whether or not an task exists in a mrproject for a certian student.
     *
     * @param int $mrprojectid the id of the mrproject to test
     * @param int $studentid the user id of the student to test
     * @param boolean $expected whether an task is expected to exist or not
     */
    private function assert_task_status($mrprojectid, $studentid, $expected) {
        global $DB;

        $sql = "SELECT * FROM {mrproject} s
                         JOIN {mrproject_meeting} t ON t.mrprojectid = s.id
                         JOIN {mrproject_task} a ON a.meetingid = t.id
                        WHERE s.id = :mrprojectid AND a.studentid = :studentid";

        $params = array('mrprojectid' => $mrprojectid, 'studentid' => $studentid);
        $actual = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($expected, $actual, "Checking whether student $studentid has task in mrproject $mrprojectid");
    }

    /**
     * Test getting the contexts for a user.
     */
    public function test_get_contexts_for_userid() {

        // Get contexts for the first user.
        $contextids = provider::get_contexts_for_userid($this->student1->id)->get_contextids();
        $this->assertEquals([$this->context->id], $contextids, '', 0.0, 10, true);
    }

    /**
     * Test getting the users within a context.
     */
    public function test_get_users_in_context() {
        global $DB;
        $component = 'mod_mrproject';

        // Ensure userlist for context contains all users.
        $userlist = new \core_privacy\local\request\userlist($this->context, $component);
        provider::get_users_in_context($userlist);

        $expected = $this->allstudents;
        $expected[] = 2; // The teacher involved.
        $actual = $userlist->get_userids();
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }


    /**
     * Export test for teacher data.
     */
    public function test_export_teacher_data() {
        global $DB;

        // Export all contexts for the teacher.
        $contextids = [$this->context->id];
        $teacher = $DB->get_record('user', array('id' => 2));
        $appctx = new approved_contextlist($teacher, 'mod_mrproject', $contextids);
        provider::export_user_data($appctx);
        $data = writer::with_context($this->context)->get_data([]);
        $this->assertNotEmpty($data);
    }

    /**
     * Export test for student1's data.
     */
    public function test_export_user_data1() {

        // Export all contexts for the first user.
        $contextids = [$this->context->id];
        $appctx = new approved_contextlist($this->student1, 'mod_mrproject', $contextids);
        provider::export_user_data($appctx);
        $data = writer::with_context($this->context)->get_data([]);
        $this->assertNotEmpty($data);
    }

    /**
     * Test for delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        provider::delete_data_for_all_users_in_context($this->context);

        foreach ($this->allstudents as $u) {
            $this->assert_task_status($this->mrprojectid, $u, false);
        }
    }

    /**
     * Test for delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        $appctx = new approved_contextlist($this->student1, 'mod_mrproject', [$this->context->id]);
        provider::delete_data_for_user($appctx);

        $this->assert_task_status($this->mrprojectid, $this->student1->id, false);
        $this->assert_task_status($this->mrprojectid, $this->student2->id, true);

    }

    /**
     * Test for delete_data_for_users().
     */
    public function test_delete_data_for_users() {
        $component = 'mod_mrproject';

        $approveduserids = [$this->student1->id, $this->student2->id];
        $approvedlist = new approved_userlist($this->context, $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);

        $this->assert_task_status($this->mrprojectid, $this->student1->id, false);
        $this->assert_task_status($this->mrprojectid, $this->student2->id, false);
    }
}
