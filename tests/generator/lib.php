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
 * mod_mrproject data generator
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mrproject module PHPUnit data generator class
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mrproject_generator extends testing_module_generator {

    /**
     * set default
     *
     * @param stdClass $record
     * @param string $property
     * @param mixed $value
     */
    private function set_default($record, $property, $value) {
        if (!isset($record->$property)) {
            $record->$property = $value;
        }
    }

    /**
     * Create new mrproject module instance
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/mod/mrproject/lib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }
        self::set_default($record, 'name', get_string('pluginname', 'mrproject').' '.$i);
        self::set_default($record, 'intro', 'Test mrproject '.$i);
        self::set_default($record, 'introformat', FORMAT_MOODLE);
        self::set_default($record, 'mrprojectmode', 'onetime');
        //self::set_default($record, 'guardtime', 0);
        self::set_default($record, 'defaultmeetingduration', 15);
        self::set_default($record, 'staffrolename', '');
        self::set_default($record, 'scale', 0);
        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = mrproject_add_instance($record);
        $modinst = $this->post_add_instance($id, $record->coursemodule);

        if (isset($options['meetingtimes'])) {
            $meetingtimes = (array) $options['meetingtimes'];
            foreach ($meetingtimes as $meetingkey => $time) {
                $meeting = new stdClass();
                $meeting->mrprojectid = $id;
                $meeting->starttime = $time;
                $meeting->duration = 10;
                $meeting->teacherid = isset($options['meetingteachers'][$meetingkey]) ? $options['meetingteachers'][$meetingkey] : 2; // Admin user as default.
                $meeting->tasklocation = 'Test Loc';
                $meeting->timemodified = time();
                $meeting->notes = '';
                $meeting->meetingnote = '';
                $meeting->meetingheld = isset($options['meetingheld'][$meetingkey]) ? $options['meetingheld'][$meetingkey] : 0;
                $meeting->emaildate = 0;
                $meeting->hideuntil = 0;
                $meetingid = $DB->insert_record('mrproject_meeting', $meeting);

                if (isset($options['meetingstudents'][$meetingkey])) {
                    $students = (array)$options['meetingstudents'][$meetingkey];
                    foreach ($students as $studentkey => $userid) {
                        $task = new stdClass();
                        $task->meetingid = $meetingid;
                        $task->studentid = $userid;
                        $task->attended = isset($options['meetingattended'][$meetingkey]) && $options['meetingattended'][$meetingkey];
                        $task->grade = 0;
                        $task->tasknote = '';
                        $task->teachernote = '';
                        //$task->timecreated = time();
                        $task->timemodified = time();
                        $taskid = $DB->insert_record('mrproject_task', $task);
                    }
                }
            }
        }

        return $modinst;
    }
}
