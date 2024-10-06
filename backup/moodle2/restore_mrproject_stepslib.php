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
 * Define all the restore steps that will be used by the restore_mrproject_activity_task
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one mrproject activity
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mrproject_activity_structure_step extends restore_activity_structure_step {

    /**
     * define_structure
     *
     * @return array
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $mrproject = new restore_path_element('mrproject', '/activity/mrproject');
        $paths[] = $mrproject;

        if ($userinfo) {
            $meeting = new restore_path_element('mrproject_meeting', '/activity/mrproject/meetings/meeting');
            $paths[] = $meeting;

            $task = new restore_path_element('mrproject_task',
                                                    '/activity/mrproject/meetings/meeting/tasks/task');
            $paths[] = $task;
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * process_mrproject
     *
     * @param stdClass $data
     */
    protected function process_mrproject($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->scale < 0) { // Scale found, get mapping.
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }

        if (is_null($data->gradingstrategy)) { // Catch inconsistent data created by pre-1.9 DB schema.
            $data->gradingstrategy = 0;
        }

        if ($data->bookingrouping > 0) {
            $data->bookingrouping = $this->get_mappingid('grouping', $data->bookingrouping);
        }

        // Insert the mrproject record.
        $newitemid = $DB->insert_record('mrproject', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * process_mrproject_meeting
     *
     * @param stdClass $data
     */
    protected function process_mrproject_meeting($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mrprojectid = $this->get_new_parentid('mrproject');
        $data->starttime = $this->apply_date_offset($data->starttime);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->emaildate = $this->apply_date_offset($data->emaildate);
        $data->hideuntil = $this->apply_date_offset($data->hideuntil);

        $data->teacherid = $this->get_mappingid('user', $data->teacherid);

        $newitemid = $DB->insert_record('mrproject_meeting', $data);
        $this->set_mapping('mrproject_meeting', $oldid, $newitemid, true);
    }

    /**
     * process_mrproject_task
     *
     * @param stdClass $data
     */
    protected function process_mrproject_task($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->meetingid = $this->get_new_parentid('mrproject_meeting');

        //$data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $data->studentid = $this->get_mappingid('user', $data->studentid);

        $newitemid = $DB->insert_record('mrproject_task', $data);
        $this->set_mapping('mrproject_task', $oldid, $newitemid, true);
    }

    /**
     * after_execute
     */
    protected function after_execute() {
        // Add mrproject related files.
        $this->add_related_files('mod_mrproject', 'intro', null);
        $this->add_related_files('mod_mrproject', 'bookinginstructions', null);
        $this->add_related_files('mod_mrproject', 'meetingnote', 'mrproject_meeting');
        $this->add_related_files('mod_mrproject', 'tasknote', 'mrproject_task');
        $this->add_related_files('mod_mrproject', 'teachernote', 'mrproject_task');
        $this->add_related_files('mod_mrproject', 'studentfiles', 'mrproject_task');
    }
}
