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
 * Unit tests for mrproject permissions
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;
use \mod_mrproject\permission\mrproject_permissions;

global $CFG;
require_once($CFG->dirroot . '/mod/mrproject/locallib.php');

/**
 * Unit tests for the mrproject_permissions class.
 *
 * @group      mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mrproject_permissions_testcase extends advanced_testcase {

    /**
     * @var int Course_modules id used for testing
     */
    protected $moduleid;

    /**
     * @var int Course id used for testing
     */
    protected $courseid;

    /**
     * @var mrproject mrproject used for testing
     */
    protected $mrproject;

    /**
     * @var \context context of the mrproject instance
     */
    protected $context;

    /**
     * @var int User id of teacher used for testing
     */
    protected $edteacher;
    /**
     * @var int User id of nonediting teacher used for testing
     */
    protected $nonedteacher;

    /**
     * @var int User id of administrator used for testing
     */
    protected $administ;

    /**
     * @var meeting[] meetings used for testing
     */
    protected $meetings;

    /**
     * @var \mod_mrproject\model\task[]
     */
    protected $appts;

    /**
     * @var int[] id of students used for testing
     */
    protected $students;

    /**
     * Sets up the test case. Common situation for all tests:
     *
     * - One mrproject in a course
     * - Three meetings are created for different students
     * - There is one editing teacher, with default permissions, who is assigned to meeting 1
     * - There is one nonediting teacher, with default permissions, who is assigned to meeting 2
     * - There is one "administrator", a user with a custom role that allows only viewing, but not editing, the meetings.
     */
    protected function setUp() {
        global $DB, $CFG;

        $dg = $this->getDataGenerator();

        $this->resetAfterTest(false);

        $course = $dg->create_course();

        $this->students = array();
        for ($i = 0; $i < 3; $i++) {
            $this->students[$i] = $dg->create_user()->id;
            $dg->enrol_user($this->students[$i], $course->id, 'student');
        }

        // An editing teacher.
        $this->edteacher = $dg->create_user()->id;
        $dg->enrol_user($this->edteacher, $course->id, 'editingteacher');

        // A nonediting teacher.
        $this->nonedteacher = $dg->create_user()->id;
        $dg->enrol_user($this->nonedteacher, $course->id, 'teacher');

        // An administrator.
        $adminrole = $dg->create_role();
        assign_capability('mod/mrproject:canseeotherteachersbooking', CAP_ALLOW, $adminrole, \context_system::instance()->id);
        $this->administ = $dg->create_user()->id;
        $dg->enrol_user($this->administ, $course->id, $adminrole);

        $options = array();
        $options['meetingtimes'] = [time() + DAYSECS, time() + 2 * DAYSECS, time() + 3 * DAYSECS];
        $options['meetingstudents'] = array_values($this->students);
        $options['meetingteachers'] = [$this->edteacher, $this->nonedteacher];

        $schedrec = $this->getDataGenerator()->create_module('mrproject', ['course' => $course->id], $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $schedrec->cmid));
        $this->mrproject = mrproject::load_by_coursemodule_id($coursemodule->id);

        $this->moduleid  = $coursemodule->id;
        $this->courseid  = $coursemodule->course;
        $this->context   = $this->mrproject->context;
        $meetingids = array_keys($DB->get_records('mrproject_meeting', array('mrprojectid' => $this->mrproject->id), 'starttime ASC'));
        $this->meetings = array();
        $this->appts = array();
        foreach ($meetingids as $key => $id) {
            $this->meetings[$key] = $this->mrproject->get_meeting($id);
            $this->appts[$key] = array_values($this->meetings[$key]->get_tasks())[0];
        }
    }


    public function test_teacher_can_see_meeting() {

        // Editing teacher sees all meetings.
        $p = new mrproject_permissions($this->context, $this->edteacher);
        $this->assertTrue($p->teacher_can_see_meeting($this->meetings[0]));
        $this->assertTrue($p->teacher_can_see_meeting($this->meetings[1]));
        $this->assertTrue($p->teacher_can_see_meeting($this->meetings[2]));

        // Nonediting teacher sees only his own meeting.
        $p = new mrproject_permissions($this->context, $this->nonedteacher);
        $this->assertFalse($p->teacher_can_see_meeting($this->meetings[0]));
        $this->assertTrue ($p->teacher_can_see_meeting($this->meetings[1]));
        $this->assertFalse($p->teacher_can_see_meeting($this->meetings[2]));

        // Adminstrator sees all meetings.
        $p = new mrproject_permissions($this->context, $this->administ);
        $this->assertTrue($p->teacher_can_see_meeting($this->meetings[0]));
        $this->assertTrue($p->teacher_can_see_meeting($this->meetings[1]));
        $this->assertTrue($p->teacher_can_see_meeting($this->meetings[2]));

        // Student don't ever see the teacher side of things.
        $p = new mrproject_permissions($this->context, $this->students[1]);
        $this->assertFalse($p->teacher_can_see_meeting($this->meetings[0]));
        $this->assertFalse($p->teacher_can_see_meeting($this->meetings[1]));
        $this->assertFalse($p->teacher_can_see_meeting($this->meetings[2]));

    }

    public function test_can_edit_meeting() {

        // Editing teacher can edit all meetings.
        $p = new mrproject_permissions($this->context, $this->edteacher);
        $this->assertTrue($p->can_edit_meeting($this->meetings[0]));
        $this->assertTrue($p->can_edit_meeting($this->meetings[1]));
        $this->assertTrue($p->can_edit_meeting($this->meetings[2]));

        // Nonediting teacher can only edit his own meeting.
        $p = new mrproject_permissions($this->context, $this->nonedteacher);
        $this->assertFalse($p->can_edit_meeting($this->meetings[0]));
        $this->assertTrue ($p->can_edit_meeting($this->meetings[1]));
        $this->assertFalse($p->can_edit_meeting($this->meetings[2]));

        // Adminstrator cannot edit any meetings.
        $p = new mrproject_permissions($this->context, $this->administ);
        $this->assertFalse($p->can_edit_meeting($this->meetings[0]));
        $this->assertFalse($p->can_edit_meeting($this->meetings[1]));
        $this->assertFalse($p->can_edit_meeting($this->meetings[2]));

        // Student can't ever edit meetings.
        $p = new mrproject_permissions($this->context, $this->students[1]);
        $this->assertFalse($p->can_edit_meeting($this->meetings[0]));
        $this->assertFalse($p->can_edit_meeting($this->meetings[1]));
        $this->assertFalse($p->can_edit_meeting($this->meetings[2]));

    }

    public function test_can_edit_own_meetings() {

        // Both teachers can edit their own meetings.
        $p = new mrproject_permissions($this->context, $this->edteacher);
        $this->assertTrue($p->can_edit_own_meetings());
        $p = new mrproject_permissions($this->context, $this->nonedteacher);
        $this->assertTrue($p->can_edit_own_meetings());

        // Adminstrator and student cannot edit any meetings.
        $p = new mrproject_permissions($this->context, $this->administ);
        $this->assertFalse($p->can_edit_own_meetings());
        $p = new mrproject_permissions($this->context, $this->students[1]);
        $this->assertFalse($p->can_edit_own_meetings());

    }

    public function test_can_edit_all_meetings() {

        // Editing teachers can edit all meetings.
        $p = new mrproject_permissions($this->context, $this->edteacher);
        $this->assertTrue($p->can_edit_all_meetings());

        // Nonediting teacher, adminstrator and student cannot edit all meetings.
        $p = new mrproject_permissions($this->context, $this->nonedteacher);
        $this->assertFalse($p->can_edit_all_meetings());
        $p = new mrproject_permissions($this->context, $this->administ);
        $this->assertFalse($p->can_edit_all_meetings());
        $p = new mrproject_permissions($this->context, $this->students[1]);
        $this->assertFalse($p->can_edit_all_meetings());

    }


    public function test_can_see_all_meetings() {

        // Editing teachers can see all meetings.
        $p = new mrproject_permissions($this->context, $this->edteacher);
        $this->assertTrue($p->can_see_all_meetings());

        // Nonediting teacher cannot see all meetings.
        $p = new mrproject_permissions($this->context, $this->nonedteacher);
        $this->assertFalse($p->can_see_all_meetings());

        // Administrator can see (though not edit) all meetings.
        $p = new mrproject_permissions($this->context, $this->administ);
        $this->assertTrue($p->can_see_all_meetings());

        // Students cannot see all meetings.
        $p = new mrproject_permissions($this->context, $this->students[1]);
        $this->assertFalse($p->can_see_all_meetings());

    }


    public function test_can_see_task() {

        // Editing teacher can all tasks.
        $p = new mrproject_permissions($this->context, $this->edteacher);
        $this->assertTrue($p->can_see_task($this->appts[0]));
        $this->assertTrue($p->can_see_task($this->appts[1]));
        $this->assertTrue($p->can_see_task($this->appts[2]));

        // Nonediting teacher can only see his own task.
        $p = new mrproject_permissions($this->context, $this->nonedteacher);
        $this->assertFalse($p->can_see_task($this->appts[0]));
        $this->assertTrue ($p->can_see_task($this->appts[1]));
        $this->assertFalse($p->can_see_task($this->appts[2]));

        // Administrator can see all tasks.
        $p = new mrproject_permissions($this->context, $this->administ);
        $this->assertTrue($p->can_see_task($this->appts[0]));
        $this->assertTrue($p->can_see_task($this->appts[1]));
        $this->assertTrue($p->can_see_task($this->appts[2]));

        // Student can see only his own task.
        for ($i = 0; $i < 3; $i++) {
            $p = new mrproject_permissions($this->context, $this->students[$i]);
            for ($j = 0; $j < 3; $j++) {
                $actual = $p->can_see_task($this->appts[$j]);
                $expected = ($i == $j);
                $msg = "Student $i with id {$this->students[$i]} tested on task $j booked by {$this->appts[$j]->studentid}";
                $this->assertEquals($expected, $actual, $msg);
            }
        }

    }


}
