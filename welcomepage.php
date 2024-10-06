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
 * Contains various sub-screens that a teacher can see.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;


/**
 * Print a selection box of existing meetings to be mrproject in
 *
 * @param mrproject $mrproject
 * @param int $studentid student to schedule
 * @param int $groupid group to schedule
 */



/********************************* Force separate groups + langage 'en' **********************************/
global $DB;

//course table
$course_record = new stdClass();
$course_record->id = $COURSE->id;         // course id
//$course_record->groupmode = 1;          // 1 = Separate groups  (for the course)     
$course_record->groupmodeforce = 0;       //disable Forcing group mode for all activities in this course
$course_record->lang = 'en';              //force langage in this course to 'english'
$DB->update_record('course', $course_record);    


//course_modules table
$cm_record = new stdClass();
$cm_record->id = $mrproject->cmid;      // course_modules id
$cm_record->groupmode = 1;              // 1 = Separate groups  (for the activity)
$DB->update_record('course_modules', $cm_record);  

//$DB->set_field('course_modules', 'groupmode', 1, array('id' => $mrproject->cmid));

/*****************************************************************************************/

// Load group restrictions.
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = false;
$currentgroup = groups_get_activity_group($cm, true);

// All group arrays in the following are in the format used by groups_get_all_groups.
// The special value '' (empty string) is used to signal "all groups" (no restrictions).

// Find groups which the current teacher can see ($groupsicansee, $groupsicurrentlysee).
// $groupsicansee: contains all groups that a teacher potentially has access to.
// $groupsicurrentlysee: may be restricted by the user to one group, using the drop-down box.
$userfilter = $USER->id;
if (has_capability('moodle/site:accessallgroups', $context)) {
    $userfilter = 0;
}
$groupsicansee = '';
$groupsicurrentlysee = '';
if ($groupmode) {
    if ($userfilter) {
        $groupsicansee = groups_get_all_groups($COURSE->id, $userfilter, $cm->groupingid);
    }
    $groupsicurrentlysee = $groupsicansee;
    if ($currentgroup) {
        if ($userfilter && !groups_is_member($currentgroup, $userfilter)) {
            $groupsicurrentlysee = array();
        } else {
            $cgobj = groups_get_group($currentgroup);
            $groupsicurrentlysee = array($currentgroup => $cgobj);
        }
    }
}



// Find groups which the current teacher can schedule as a group ($groupsicanschedule).
$groupsicanschedule = array();

$groupsicanschedule = groups_get_all_groups($COURSE->id, $userfilter, 0);


// Find groups which can book an task with the current teacher ($groupsthatcanseeme).
$groupsthatcanseeme = '';
$groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);



//$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'view', 'subpage' => $subpage));

$baseurl = new moodle_url('/mod/mrproject/view.php', array(
        'id' => $mrproject->cmid,
        'subpage' => $subpage,
        'offset' => $offset
));

// The URL that is used for jumping back to the view (e.g., after an action is performed).
$viewurl = new moodle_url($baseurl, array('what' => 'welcomepage', 'id' => $mrproject->cmid));
$PAGE->set_url($viewurl);

if ($action != 'welcomepage') {
    require_once($CFG->dirroot.'/mod/mrproject/meetingforms.php');
    require_once($CFG->dirroot.'/mod/mrproject/groupform.php');
    require_once($CFG->dirroot.'/mod/mrproject/teacherview.controller.php');
}





/***********************save picture of the group (logo)***********************************/

function groups_update_picture($group, $data, $editform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/gdlib.php");

    $fs = get_file_storage();
    $context = context_course::instance($group->courseid, MUST_EXIST);
    $newpicture = $group->picture;

    if (!empty($data->deletepicture)) {
        $fs->delete_area_files($context->id, 'group', 'icon', $group->id);
        $newpicture = 0;
    } else if ($iconfile = $editform->save_temp_file('imagefile')) {
        if ($rev = process_new_icon($context, 'group', 'icon', $group->id, $iconfile)) {
            $newpicture = $rev;
        } else {
            $fs->delete_area_files($context->id, 'group', 'icon', $group->id);
            $newpicture = 0;
        }
        @unlink($iconfile);
    }

    if ($newpicture != $group->picture) {
        $DB->set_field('groups', 'picture', $newpicture, array('id' => $group->id));
        $group->picture = $newpicture;

        // Invalidate the group data as we've updated the group record.
        cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($group->courseid));
    }
}

/************************************ Update student responsibilities ****************************************/

if ($action == 'editgroup') {


    //$groupid = required_param('groupid', PARAM_INT);
    $groupid = optional_param('groupid', 0, PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $data = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);


    //$actionurl = new moodle_url($baseurl, array('what' => 'schedulegroup', 'groupid' => $groupid));
    $actionurl = new moodle_url($baseurl, array('what' => 'editgroup', 'groupid' => $groupid));

    
    $editoroptions = array('trusttext' => false, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $context,
                                   'collapsed' => true);

    //get data
    //Prepare the description editor: We do support files for group descriptions
    $editoroptions = array('maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$course->maxbytes, 'trust'=>false, 'context'=>$context, 'noclean'=>true);
    if (!empty($group->id)) {
        $editoroptions['subdirs'] = file_area_contains_subdirs($context, 'group', 'description', $group->id);  //data->id = groupid
        $data = file_prepare_standard_editor($data, 'description', $editoroptions, $context, 'group', 'description', $group->id);
    }


    //prepare the form
    $mform = new groupform($actionurl, $mrproject);
    $data->id = $mrproject->cmid;   //course module id
    $data->a = $mrproject->id;      //mrproject id
    $data->gid = $groupid;
    $mform->set_data($data);




    //save data
    if ($mform->is_cancelled()) { 
        redirect($viewurl);
        //redirect('hhhhhhhhhh');
    } else if ($data = $mform->get_data()) {
        
        //update group
        $data->id = $groupid;    //update with group id

        //description
        if ($mform) {
            $data = file_postupdate_standard_editor($data, 'description', $editoroptions, $context, 'group', 'description', $data->id);
        }

        //picture
        groups_update_picture($group, $data, $mform);

        
        $DB->update_record('groups', $data);


        redirect($viewurl);
        /*redirect($viewurl,
                 get_string('onemeetingadded', 'mrproject'),
                 0,
                 \core\output\notification::NOTIFY_SUCCESS);*/
        
    } else {
        echo $output->header();
        /*echo '<div id="grouppicture">';
        if ($id) {
            print_group_picture($group, $course->id);
        }
        echo '</div>';*/
        echo $output->heading(get_string('editgroup', 'mrproject', $group->name), 3);
        echo $output->box_start();
        $mform->display();
        echo $output->box_end();
        echo $output->footer();
        die;
    }
}


/****************** Standard view ***********************************************/


//Trigger event: project_presentation_viewed
\mod_mrproject\event\project_presentation_viewed::create_from_mrproject($mrproject)->trigger();



//header
echo $output->header();


// Print top tabs.
$actionurl = new moodle_url($viewurl, array('sesskey' => sesskey()));
$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid,
                         'what' => 'welcomepage'));

/**************************the menu (tabs)************************************/

$inactive = array();
if ($DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id)) <=
         $DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this mrproject.
    $inactive[] = 'welcometab';
    if ($subpage = 'welcometab') {
        $subpage = 'welcometab';
    }
}

$subpage = 'welcometab';

echo $output->teacherview_tabs($mrproject, $permissions, $taburl, $subpage, $inactive);



/**************************** Groups select *****************************/

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($mrproject->cm);
$currentgroupid = groups_get_activity_group($mrproject->cm, true);
//groups_print_activity_menu($mrproject->cm, $taburl);


// Display correct type of statistics by request.
$usergroups = ($currentgroupid > 0) ? array($currentgroupid) : '';




/*********************************************************************************************/

// Find groups which can book an task with the current teacher ($groupsthatcanseeme).
$groupsthatcanseeme = '';
$groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);

//Group name
$groupname = '';
foreach ($groupsthatcanseeme as $id => $group) {
    if ($id ==  $currentgroupid) {
        //$groupname = groups_get_group_name($id);
        $groupname = $group->name;
        $grouppicture = $group->picture;
    }
}

//get a Group (object)
$selectedgroup = null;
foreach ($groupsthatcanseeme as $id => $group) {
    if ($id ==  $currentgroupid) {
        $selectedgroup = $group;
    }
}


/************************ commandbar: action button ***********************/
$commandbar = new mrproject_command_bar();
$commandbar->title = get_string('actions', 'mrproject');

//Show 'Add meetings' button
$addbuttons = array();

$teachercaps = ['mod/mrproject:managealltasks']; 
$isteacher = has_any_capability($teachercaps, $context);
if ($isteacher) {
    //project presentation
    $addbuttons[] = $commandbar->action_link(new moodle_url('/course/modedit.php', array('update' => $mrproject->cmid, 'return' => 0, 'sr' => 0)), 'editprojectpresentation', 'i/courseevent');
    //groups
    $addbuttons[] = html_writer::link(new moodle_url($CFG->wwwroot .'/group/index.php?id='. $COURSE->id), $output->pix_icon('i/group', '') . get_string('editgroups', 'mrproject'), array('target' => '_blank', 'rel' => 'noopener'));   //target= '_blank' ---> Open the link in a new tab
    //$addbuttons[] = $commandbar->action_link(new moodle_url($CFG->wwwroot .'/group/index.php?id='. $COURSE->id), 'editgroups', 'i/group');

} else {
    //global $USER, $DB;
    //$contextid = $DB->get_field('context', 'id', array('contextlevel' => 50));

    //add permission 'managegroups' for 'student' role (to update group name and logo) 
    /*$permission = new stdClass();
    $permission->contextid = 2;   //the whole site
    $permission->roleid  = 5;    //student      
    $permission->capability  = 'moodle/course:managegroups';   
    $permission->permission   = 1;   
    $permission->modifierid   = $USER->id;   
    $permission->timemodified   = time();       
    try {
        $DB->insert_record('role_capabilities', $permission, false);
    } catch (dml_exception $e) {
        false;
    }*/

    /*$transaction = $DB->start_delegated_transaction();
        $deletedrecord = $DB->delete_records('mrproject_task', ['id' => $taskteacherid]);
        if ($deletedrecord) {
            $DB->commit_delegated_transaction($transaction);
        }*/

    //redirect to the page
    //$addbuttons[] = $commandbar->action_link(new moodle_url('/group/group.php', array('courseid' => $COURSE->id, 'id' => $currentgroupid)), 'editgroupnamelogo', 't/add');
    

    $addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'editgroup', 'groupid' => $currentgroupid)), 'editgroupnamelogo', 't/add');
    
}


$commandbar->add_group(get_string('editproject', 'mrproject'), $addbuttons);
echo $output->render($commandbar);



/*************************print groups select + Team Logo *******************************/
if (!$isteacher) {    //for student view
    $str1 = '';
    $str1 .= '<table class="teamlogo">';
    $str1 .= '<tr>';
    $str1 .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';


    //select groups
    //$str1 .= groups_print_activity_menu($mrproject->cm, $taburl);

    //group logo
    if ($currentgroupid != 0) {
        if ($pictureurl = get_group_picture_url($selectedgroup, $COURSE->id, true, false)) {
            $str1 .= html_writer::img($pictureurl, '', ['title' => $groupname, 'class' => 'imglogo']);
        } else {
            $str1 .= html_writer::div(get_string('grouplogo', 'mrproject'), 'grouplogo');
        }
    }

    //group name
    $str1 .= '<span class="team"><strong>'. $groupname .'</strong></span>';


    $str1 .= '</tr>';
    $str1 .= '</table>';

    echo $str1;
}

/************************************* Project presention box ********************************************/

            //$project = $DB->get_record('mrproject', ['id' => $mrproject->id]);
            $project = $mrproject->load_by_id($mrproject->id);

            $str = '';
            $str .= '<table class="userinfobox1">';
            $str .= '<tr>';
            $str .= '<td>';

            //project title
            $str .= '</strong></u><div class="projecttitle">'. $mrproject->get_name() .'</div><br/><br/>';

            //project presentation
            $str .= '<div class="title0"><strong>'.get_string('projectpresentation', 'mrproject').'</strong></div><br/>';

            //context, problem, objective
            $str .= '<div class="title2"><strong>'.get_string('context', 'mrproject').'</strong></div><br/>';
            $str .= '<div class="contentwelcome">'. $project->intro .'</div>'.'<br/><br/>';

            $str .= '<div class="title2"><strong>'.get_string('problem', 'mrproject').'</strong></div><br/>';
            $str .= '<div class="contentwelcome">'. $project->problem .'</div>'.'<br/><br/>';

            $str .= '<div class="title2"><strong>'.get_string('objective', 'mrproject').'</strong></div><br/>';
            $str .= '<div class="contentwelcome">'. $project->objective .'</div>'.'<br/><br/>';


            //start date, end date
            $str .= '<span class="title2"><strong><u>'.get_string('projectstartdate', 'mrproject').':</u></strong></span>'.'<span class="contentwelcome">'. userdate($project->startdate, get_string('strftimedate', 'core_langconfig')) .'</span><br/>';
            $str .= '<h1>'.'  '.'</h1>';
            $str .= '<span class="title2"><strong><u>'.get_string('projectenddate', 'mrproject').':</u>&nbsp;&nbsp;</strong></span>'.'<span class="contentwelcome">'. userdate($project->enddate, get_string('strftimedate', 'core_langconfig')) .'</span>';
            $str .= '<h1>'.'  '.'</h1>';
            
            $str .= '</td></tr>';

            $str .= '</table>';

            echo $str;
            //echo $output->box($str);

        echo html_writer::div(get_string('copyright', 'mrproject'), 'copyright');
        echo html_writer::div(get_string('madewith', 'mrproject'), 'madewith');
        

echo '<br/>';
// Finish the page.
echo $output->footer();
exit;

