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
 * Scheduled background task for sending automated task reminders
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\task;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../mailtemplatelib.php');

/**
 * Scheduled background task for sending automated task reminders
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_reminders extends \core\task\scheduled_task {

    /**
     * get_name
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendreminders', 'mod_mrproject');
    }

    /**
     * execute
     */
    public function execute() {

        global $DB;

        //$date = make_timestamp(date('Y'), date('m'), date('d'), date('H'), date('i'));

        // Find relevant meetings in all mrprojects.
        //$select = 'emaildate > 0 AND emaildate <= ? AND starttime > ?';
        //$meetings = $DB->get_records_select('mrproject_meeting', $select, array($date, $date), 'starttime');

        $meetings = $DB->get_records('mrproject_meeting');   //Get meetings list as an array

        foreach ($meetings as $meeting) {
            // Get teacher record.
            $teacher = $DB->get_record('user', array('id' => $meeting->teacherid));

            // Get mrproject, meeting and course.
            $mrproject = \mod_mrproject\model\mrproject::load_by_id($meeting->mrprojectid);
            $meetingm = $mrproject->get_meeting($meeting->id);
            $course = $mrproject->get_courserec();

            // Mark as sent. (Do this first for safe fallback in case of an exception.)
            //$meeting->emaildate = -1;
            //$DB->update_record('mrproject_meeting', $meeting);


            // Send reminder to the student
            foreach ($meetingm->get_tasks() as $task) {

                if ($task->duedate <= time() && $task->submissiondate == 0) {
                    if ($task->collectivetask == null) {   //Individual task
                        $student = $DB->get_record('user', array('id' => $task->studentid));
                        cron_setup_user($student, $course);
                        \mrproject_messenger::send_meeting_notification($meetingm,
                                'reminder', 'reminder', $teacher, $student, $teacher, $student, $course);

                    } else {      //Collective task
                        $studentids = explode('+', $task->collectivetask);
                        foreach ($studentids as $studid) {
                            $student = $DB->get_record('user', array('id' => $studid));
                            cron_setup_user($student, $course);
                            \mrproject_messenger::send_meeting_notification($meetingm,
                                    'reminder', 'reminder', $teacher, $student, $teacher, $student, $course);
                        }
                    }
                }
            }
        }
        cron_setup_user();
    }

}