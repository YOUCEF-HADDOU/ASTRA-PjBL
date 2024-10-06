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
 * Global configuration settings for the mrproject module.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once($CFG->dirroot.'/mod/mrproject/lib.php');

    //"admin_category"
    //$ADMIN->add('localplugins', new admin_category('mod_mrproject_category', get_string('pluginname', 'mod_mrproject')));

    $settings->add(new admin_setting_configtext('mod_mrproject/uploadedfiles',
                     get_string('uploadmaxfilesglobal', 'mrproject'),
                     get_string('uploadmaxfilesglobal_desc', 'mrproject'),
                     5, PARAM_INT));

                     
    //$ADMIN->add('localplugins', $settings);


    //Add an "admin_externalpage" to the "admin_root" object    (Manage broadcast messages)
    /*$ADMIN->add('mod_mrproject_category', new admin_externalpage('mod_mrproject_settings', get_string('pluginname', 'mod_mrproject'),
        $CFG->wwwroot . '/mod/mrproject/???.php'));*/

}



