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
 * Install a new database fields
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * This function does anything necessary to match current functionality.
 *
 * @param int $oldversion version number to be migrated from
 * @return bool true if upgrade is successful
 */
function xmldb_mrproject_install($oldversion=0) {

    global $CFG, $DB;
    $dbman = $DB->get_manager();       // Loads ddl manager and xmldb classes.
    $result = true;


            /* ******************* Add database fields ********************** */

/*******Add a field 'multiroles' to the 'groups_members' table (teacher role, student responsibility)********/

    $table = new xmldb_table('groups_members');
    $field = new xmldb_field('multiroles', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // mrproject savepoint reached.
    //upgrade_mod_savepoint(true, 2024031400, 'mrproject');
    
    return true;
}