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
 * Defines the mod_mrproject booking form viewed event.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_mrproject booking form viewed event.
 *
 * Indicates that a student has viewed the booking form.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class project_presentation_viewed extends mrproject_base {

    /**
     * Create this event on a given mrproject.
     *
     * @param \mod_mrproject\model\mrproject $mrproject
     * @return \core\event\base
     */
    public static function create_from_mrproject(\mod_mrproject\model\mrproject $mrproject) {
        $event = self::create(self::base_data($mrproject));
        $event->set_mrproject($mrproject);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';     //read
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_projectpresentationviewed', 'mrproject');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has viewed the project presentation in the [MRProject activity module] " .
            "with course module id '$this->contextinstanceid'.";
    }
}