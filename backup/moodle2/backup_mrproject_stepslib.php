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
 * Define all the backup steps that will be used by the backup_mrproject_activity_task
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete mrproject structure for backup, with file and id annotations
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mrproject_activity_structure_step extends backup_activity_structure_step {

    /**
     * define_structure
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $mrproject = new backup_nested_element('mrproject', array('id'), array(
            'name', 'intro', 'introformat', 'mrprojectmode', 'maxbookings',
            'defaultmeetingduration', 'allownotifications', 'staffrolename',
            'scale', 'gradingstrategy', 'bookingrouping', 'usenotes',
            'usebookingform', 'bookinginstructions', 'bookinginstructionsformat',
            'usestudentnotes', 'requireupload', 'uploadmaxfiles', 'uploadmaxsize',
            'usecaptcha', 'timemodified'));

        $meetings = new backup_nested_element('meetings');

        $meeting = new backup_nested_element('meeting', array('id'), array(
            'starttime', 'duration', 'teacherid', 'tasklocation',
            'timemodified', 'notes', 'notesformat', 'meetingheld',
            'emaildate', 'hideuntil'));

        $tasks = new backup_nested_element('tasks');

        $task = new backup_nested_element('task', array('id'), array(
            'studentid', 'grade',
            'tasknote', 'tasknoteformat', 'teachernote', 'teachernoteformat',
            'studentnote', 'studentnoteformat', 'timemodified'));

        // Build the tree.

        $mrproject->add_child($meetings);
        $meetings->add_child($meeting);

        $meeting->add_child($tasks);
        $tasks->add_child($task);

        // Define sources.
        $mrproject->set_source_table('mrproject', array('id' => backup::VAR_ACTIVITYID));
        $mrproject->annotate_ids('grouping', 'bookingrouping');

        // Include tasks only if we back up user information.
        if ($userinfo) {
            $meeting->set_source_table('mrproject_meeting', array('mrprojectid' => backup::VAR_PARENTID));
            $task->set_source_table('mrproject_task', array('meetingid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $mrproject->annotate_ids('scale', 'scale');

        if ($userinfo) {
            $meeting->annotate_ids('user', 'teacherid');
            $task->annotate_ids('user', 'studentid');
        }

        // Define file annotations.
        $mrproject->annotate_files('mod_mrproject', 'intro', null); // Files stored in intro field.
        $mrproject->annotate_files('mod_mrproject', 'bookinginstructions', null); // Files stored in intro field.
        $meeting->annotate_files('mod_mrproject', 'meetingnote', 'id'); // Files stored in meeting notes.
        $task->annotate_files('mod_mrproject', 'tasknote', 'id'); // Files stored in task notes.
        $task->annotate_files('mod_mrproject', 'teachernote', 'id'); // Files stored in teacher-only notes.
        $task->annotate_files('mod_mrproject', 'studentfiles', 'id'); // Files uploaded by students.

        // Return the root element (mrproject), wrapped into standard activity structure.
        return $this->prepare_activity_structure($mrproject);
    }
}
