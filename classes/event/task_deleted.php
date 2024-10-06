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
 * Defines the mod_mrproject meeting added event.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_mrproject meeting added event.
 *
 * Indicates that a teacher has added a meeting.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_deleted extends task_base {

    /**
     * Create this event on a given meeting.
     *
     * @param \mod_mrproject\model\task $task
     * @return \core\event\base
     */
    public static function create_from_task(\mod_mrproject\model\task $task) {
        $event = self::create(self::base_data($task));
        $event->set_task($task);

        return $event;
    }

    

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;    
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_taskdeleted', 'mrproject');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        /*return "A new task with id '{$this->objectid}' has been assigned to the student with id '{$task->get_student()->id}' during the meeting with id  '{$this->objectid->get_meeting()->id}'"
                ." in the [MRProject activity module] with course_module id '$this->contextinstanceid'.";*/

        return "The task with id  '{$this->objectid}' assigned to the student with id '{$this->relateduserid}' has been deleted"
                ." in the [MRProject activity module] with course module id '$this->contextinstanceid'";
        
              
    }
}
