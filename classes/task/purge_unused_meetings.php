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

/**
 * Scheduled background task for sending automated task reminders
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_unused_meetings extends \core\task\scheduled_task {
    /**
     * get_name
     *
     * @return string
     */
    public function get_name() {
        return get_string('purgeunusedmeetings', 'mod_mrproject');
    }

    /**
     * execute
     */
    public function execute() {
        \mod_mrproject\model\mrproject::free_late_unused_meetings();
    }
}