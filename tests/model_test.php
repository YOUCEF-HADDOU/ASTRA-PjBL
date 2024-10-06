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
 * Unit tests for the MVC model classes
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\task_factory;

global $CFG;
require_once($CFG->dirroot . '/mod/mrproject/locallib.php');

/**
 * Unit tests for the MVC model classes
 *
 * @group mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mrproject_model_testcase extends advanced_testcase {

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
     * @var int User id used for testing
     */
    protected $userid;

    protected function setUp() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
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
        $this->courseid  = $coursemodule->course;
        $this->userid = 2;  // Admin user.
    }

    /**
     * Test loading a mrproject instance from the database
     */
    public function test_mrproject() {
        global $DB;

        $dbdata = $DB->get_record('mrproject', array('id' => $this->mrprojectid));

        $instance = mrproject::load_by_coursemodule_id($this->moduleid);

        $this->assertEquals( $dbdata->name, $instance->get_name());

    }

    /**
     * Test the "task" data object
     * (basic functionality, with minimal reference to meetings)
     **/
    public function test_task() {

        global $DB;

        $instance = mrproject::load_by_coursemodule_id($this->moduleid);
        $meeting = array_values($instance->get_meetings())[0];
        $factory = new task_factory($meeting);

        $user = $this->getdataGenerator()->create_user();

        $app0 = new stdClass();
        $app0->meetingid = 1;
        $app0->studentid = $user->id;
        //$app0->attended = 0;
        $app0->grade = 0;
        $app0->tasknote = 'testnote';
        $app0->teachernote = 'confidentialtestnote';
        //$app0->timecreated = time();
        $app0->timemodified = time();

        $id1 = $DB->insert_record('mrproject_task', $app0);

        $appobj = $factory->create_from_id($id1);
        $this->assertEquals($user->id, $appobj->studentid);
        $this->assertEquals(fullname($user), fullname($appobj->get_student()));
        //$this->assertFalse($appobj->is_attended());
        $this->assertEquals(0, $appobj->grade);

        //$app0->attended = 1;
        $app0->grade = -7;
        $id2 = $DB->insert_record('mrproject_task', $app0);

        $appobj = $factory->create_from_id($id2);
        $this->assertEquals($user->id, $appobj->studentid);
        $this->assertEquals(fullname($user), fullname($appobj->get_student()));
        //$this->assertTrue($appobj->is_attended());
        $this->assertEquals(-7, $appobj->grade);

    }

}
