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
 * deliverablespage
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid,
                         'what' => 'deliverablespage', 'subpage' => $subpage));
$PAGE->set_url($taburl);


switch ($subpage) { 
    case 'deliverablestab':
        include($CFG->dirroot.'/mod/mrproject/collectivedeliverablespage.php'); 
        break;

    case 'collectivedeliverablessubtab':
        include($CFG->dirroot.'/mod/mrproject/collectivedeliverablespage.php'); 
        break;
        
    case 'individualdeliverablessubtab':
        include($CFG->dirroot.'/mod/mrproject/individualdeliverablespage.php'); 
        break;

    case 'dependenciessubtab':
        include($CFG->dirroot.'/mod/mrproject/dependenciespage.php'); 
        break;
        
}


echo '<br/>';

exit;
