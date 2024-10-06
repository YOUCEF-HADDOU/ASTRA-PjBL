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
 * Defines the mod_mrproject meeting deleted event.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_mrproject meeting deleted event.
 *
 * Indicates that a teacher has deleted a meeting.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_deleted extends meeting_base {

    /**
     * Create this event on a given meeting.
     *
     * @param \mod_mrproject\model\meeting $meeting
     * @param string $action
     * @return \core\event\base
     */
    public static function create_from_meeting(\mod_mrproject\model\meeting $meeting, $action) {
        $data = self::base_data($meeting);
        $data['other'] = array('action' => $action);
        $event = self::create($data);
        $event->set_meeting($meeting);
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
        return get_string('event_meetingdeleted', 'mrproject');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $desc = "The user with id '$this->userid' deleted the meeting with id  '{$this->objectid}'"
                ." in the [MRProject activity module] with course module id '$this->contextinstanceid'";
        /*if ($act = $this->other['action']) {
            $desc .= " during action '$act'";
        }*/
        $desc .= '.';
        return $desc;
    }
}
