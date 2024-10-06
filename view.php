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
 * This page prints a particular instance of mrproject and handles top level interactions
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_mrproject\model\mrproject;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/mrproject/lib.php');
require_once($CFG->dirroot.'/mod/mrproject/locallib.php');
require_once($CFG->dirroot.'/mod/mrproject/renderable.php');




// Read common request parameters.
$id = optional_param('id', '', PARAM_INT);    // Course Module ID - if it's not specified, must specify 'a', see below. (ex. open the activity instance ---> ...?id=144)
$action = optional_param('what', 'welcomepage', PARAM_ALPHA);        // (ex. open the subpage 'All tasks' ---? ...?id=144&what=view&subpage=alltasks)
$subaction = optional_param('subaction', '', PARAM_ALPHA);
$offset = optional_param('offset', -1, PARAM_INT);            // (ex. cliking on 'Add single meeting' ---> ...?id=144&subpage=alltasks&offset=-1&what=addmeeting&sesskey=fejPE7hXhW )



// Create a mrproject instance from a database
if ($id) {  // Course-Module ID 
    $cm = get_coursemodule_from_id('mrproject', $id, 0, false, MUST_EXIST);
    $mrproject = mrproject::load_by_coursemodule_id($id);
} else {
    $a = required_param('a', PARAM_INT);     // mrproject ID.
    $mrproject = mrproject::load_by_id($a);
    $cm = $mrproject->get_cm();
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);



require_login($course->id, true, $cm);
$context = context_module::instance($cm->id);
$permissions = new \mod_mrproject\permission\mrproject_permissions($context, $USER->id);   //See, /permission/mrproject_permissions.php



/********************************* Force separate groups **********************************/
global $DB;

//course table
$course_record = new stdClass();
$course_record->id = $course->id;         // course id
//$course_record->groupmode = 1;          // 1 = Separate groups  (for the course)     
$course_record->groupmodeforce = 0;       //disable Forcing group mode for all activities in this course
$course_record->lang = 'en';              //force langage in this course to 'english'
$DB->update_record('course', $course_record);    


//course_modules table
$cm_record = new stdClass();
$cm_record->id = $cm->id;      // course_modules id
$cm_record->groupmode = 1;              // 1 = Separate groups  (for the activity)
$DB->update_record('course_modules', $cm_record);  

//$DB->set_field('course_modules', 'groupmode', 1, array('id' => $mrproject->cmid));

/******************************************************************************************/


// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/mrproject/view.php', array('id' => $cm->id));

// get a renderer object for my plugin (instantiate my plugin's renderer). $output --> lowercase
$output = $PAGE->get_renderer('mod_mrproject');

// default subpage
if (groups_get_activity_groupmode($cm) || !$permissions->can_see_all_meetings()) {
    //$defaultsubpage = 'mytasks';     //Menu --> 'My tasks' (what=view & subpage=mytasks)
    $defaultsubpage = 'welcometab';     
    
} else {
    //$defaultsubpage = 'alltasks';    //Menu --> 'All tasks' (what=view & subpage=alltasks)
    $defaultsubpage = 'welcometab';     
}
$subpage = optional_param('subpage', $defaultsubpage, PARAM_ALPHA);



// Print the page header.
$title = $course->shortname . ': ' . format_string($mrproject->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);



// Route to screen.
$teachercaps = ['mod/mrproject:manage', 'mod/mrproject:managealltasks', 'mod/mrproject:canseeotherteachersbooking'];
$isteacher = has_any_capability($teachercaps, $context);
$isstudent = has_capability('mod/mrproject:viewmeetings', $context);
//$isstudent = has_capability('mod/mrproject:isstudent', $context);

if ($isteacher || $isstudent) {    // Teacher side.
    
    if ($action == 'meetingspage') {
        include($CFG->dirroot.'/mod/mrproject/meetingspage.php');             //Menu --> 'Meetings'
    } else if ($action == 'updatemeeting') {
        include($CFG->dirroot.'/mod/mrproject/mymeetingspage.php');      //When we click on the 'Update button' in 'My meetings' tab
    } else if ($action == 'deletemeeting') {
        include($CFG->dirroot.'/mod/mrproject/mymeetingspage.php');      //When we click on the 'Delete button' in 'My meetings' tab
    } else if ($action == 'addmeeting') {
        include($CFG->dirroot.'/mod/mrproject/mymeetingspage.php');      //When we click on 'request meeting with an expert' in 'My meetings' tab
    } else if ($action == 'addsession') {
        include($CFG->dirroot.'/mod/mrproject/mymeetingspage.php');      //When we click on 'student team meeting' in 'My meetings' tab
    } else if ($action == 'schedulegroup') {
        include($CFG->dirroot.'/mod/mrproject/mymeetingspage.php');      //When we click on the 'Plan a meeting' in 'My meetings' tab
    } 
    
    else if ($action == 'bookmeeting') {
        include($CFG->dirroot.'/mod/mrproject/upcomingmeetingspage.php');      //When we click on 'Accept meeting' in 'upcomingmeetingspage' tab
    } else if ($action == 'cancelbooking') {
        include($CFG->dirroot.'/mod/mrproject/upcomingmeetingspage.php');      //When we click on 'Cancel meeting' in 'upcomingmeetingspage' tab
    } 
    
    
    else if ($action == 'deliverablespage') {
        include($CFG->dirroot.'/mod/mrproject/deliverablespage.php');         //Menu --> 'Deliverables'
    } else if ($action == 'addmeetingreport') {
        include($CFG->dirroot.'/mod/mrproject/collectivedeliverablespage.php');         //Menu --> 'Deliverables'
    } else if ($action == 'viewmeeting') {
        include($CFG->dirroot.'/mod/mrproject/collectivedeliverablespage.php');      //When the teacher click on 'See details' button
    } else if ($action == 'addfeedback') {
        include($CFG->dirroot.'/mod/mrproject/collectivedeliverablespage.php');      //When the teacher click on 'add feedback' in 'See details' page
    }


    else if ($action == 'individualdeliverablespage') {
        include($CFG->dirroot.'/mod/mrproject/individualdeliverablespage.php');        //Menu --> 'individual deliverables'
    } else if ($action == 'submittaskreport') {
        include($CFG->dirroot.'/mod/mrproject/collectivedeliverablespage.php');        //When the student add a task report
    } else if ($action == 'viewstudent') {
        include($CFG->dirroot.'/mod/mrproject/viewstudent.php');                       //'This task' subpage (grade, note)
    }


    else if ($action == 'dependenciespage') {
        include($CFG->dirroot.'/mod/mrproject/dependenciespage.php');        //Menu --> 'dependencies'
    } else if ($action == 'recommenddependency') {
        include($CFG->dirroot.'/mod/mrproject/dependenciespage.php');        
    }
    

    else if ($action == 'export') {
        include($CFG->dirroot.'/mod/mrproject/export.php');                            //export grades
    }


    else if ($action == 'memberspage') {
        include($CFG->dirroot.'/mod/mrproject/memberspage.php');          //Menu --> 'Members'
    } else if ($action == 'editroles') {
        include($CFG->dirroot.'/mod/mrproject/memberspage.php');          //Action: Edit supervisorroles
    } else if ($action == 'editresponsibilities') {
        include($CFG->dirroot.'/mod/mrproject/memberspage.php');          //Action: Edit responsibilities
    } 
    

    
    else if ($action == 'welcomepage') {
        include($CFG->dirroot.'/mod/mrproject/welcomepage.php');           //Menu --> 'Welcome'
    } else if ($action == 'editgroup') {
        include($CFG->dirroot.'/mod/mrproject/welcomepage.php');          //Action: Edit responsibilities
    }
    else {
        include($CFG->dirroot.'/mod/mrproject/welcomepage.php');      //When the teacher open the activity instance (Menu --> 'welcometab')
    }
    

    //--------------------------Student view-----------------------------------


//} else if ($isstudent) {    // Student side.
    
    /*if ($action == 'bookmeeting') {
        include($CFG->dirroot.'/mod/mrproject/studentview.php');      //When we click on 'Accept meeting' in 'Available meetings' tab
    } else {*/
    //include($CFG->dirroot.'/mod/mrproject/studentview.php');          //When the student open the activity instance
    //}




} else {     // For guests.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('guestscantdoanything', 'mrproject'), 'generalbox');
    echo $OUTPUT->footer($course);
}
