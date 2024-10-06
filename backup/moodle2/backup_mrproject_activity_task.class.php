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
 * Backup activity task for the mrproject module
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mrproject/backup/moodle2/backup_mrproject_stepslib.php');

/**
 * mrproject backup task that provides all the settings and steps to perform one
 *
 * complete backup of the activity.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mrproject_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // mrproject only has one structure step.
        $this->add_step(new backup_mrproject_activity_structure_step('mrproject_structure', 'mrproject.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of mrproject.
        $search = "/(".$base."\/mod\/mrproject\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MRPROJECTINDEX*$2@$', $content);

        // Link to mrproject view by coursemoduleid.
        $search = "/(".$base."\/mod\/mrproject\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MRPROJECTVIEWBYID*$2@$', $content);

        return $content;
    }
}
