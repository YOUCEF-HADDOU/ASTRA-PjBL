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



/********************************* Force separate groups **********************************/
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

/******************************************************************************************/


// Load group restrictions.
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = false;
if ($groupmode) {
    $currentgroup = groups_get_activity_group($cm, true);
}

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
if ($groupmode) {
    $groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);
}


$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'meetingspage', 'subpage' => $subpage));

$baseurl = new moodle_url('/mod/mrproject/view.php', array(
        'id' => $mrproject->cmid,
        'subpage' => $subpage,
        'offset' => $offset
));

// The URL that is used for jumping back to the view (e.g., after an action is performed).
$viewurl = new moodle_url($baseurl, array('what' => 'meetingspage'));

$PAGE->set_url($viewurl);

if ($action != 'meetingspage') {
    require_once($CFG->dirroot.'/mod/mrproject/meetingforms.php');
    require_once($CFG->dirroot.'/mod/mrproject/teacherview.controller.php');
}


/********************************* Force separate groups **********************************/

global $DB;
$cm_record = new stdClass();
$cm_record->id = $mrproject->cmid;    // course_modules id
$cm_record->groupmode = 1;            // 1 = Separate groups
$DB->update_record('course_modules', $cm_record);    

//$DB->set_field('course_modules', 'groupmode', 1, array('id' => $mrproject->cmid));


/************************************ View : New single meeting form ****************************************/
if ($action == 'addmeeting') {
    $permissions->ensure($permissions->can_edit_own_meetings());

    $actionurl = new moodle_url($baseurl, array('what' => 'addmeeting'));


    $mform = new mrproject_editmeeting_form($actionurl, $mrproject, $cm, $groupsicansee);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {


        $meeting = $mform->save_meeting(0, $formdata);   //function in meetingforms.php

        //Event: meeting_added
        \mod_mrproject\event\meeting_added::create_from_meeting($meeting)->trigger();

        //redirect + notification
        redirect($viewurl,
                 get_string('onemeetingadded', 'mrproject'),
                 0,
                 \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $output->header();
        echo $output->heading(get_string('addsinglemeeting', 'mrproject'));
        $mform->display();
        echo $output->footer($course);
        die;
    }
}





/************************************ View : Update single meeting form ****************************************/
if ($action == 'updatemeeting') {

    $meetingid = required_param('meetingid', PARAM_INT);
    $groupid = required_param('groupid', PARAM_INT);
    

    $meeting = $mrproject->get_meeting($meetingid);
    $permissions->ensure($permissions->can_edit_meeting($meeting));


    if ($meeting->starttime % 300 !== 0 || $meeting->duration % 5 !== 0) {
        $timeoptions = array('step' => 1, 'optional' => false);
    } else {
        $timeoptions = array('step' => 5, 'optional' => false);
    }

    $actionurl = new moodle_url($baseurl, array('what' => 'updatemeeting', 'meetingid' => $meetingid, 'groupid' => $groupid));

   $mform = new mrproject_editmeeting_form($actionurl, $mrproject, $cm, $groupsicansee, array(
            'meetingid' => $meetingid,
            'timeoptions' => $timeoptions)
        );
    $data = $mform->prepare_formdata($meeting);
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {

        $mform->save_meeting($meetingid, $formdata);

        //Event: meeting_updated
        \mod_mrproject\event\meeting_updated::create_from_meeting($meeting)->trigger();

        redirect($viewurl,
                 get_string('meetingupdated', 'mrproject'),
                 0,
                 \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $output->header();
        echo $output->heading(get_string('updatesinglemeeting', 'mrproject'));

        //checkdatetime (meeting held --> expired period)
        //if ($meeting->starttime + $meeting->duration * 60 < time()) { 
        if ($meeting->starttime != 0 && $meeting->starttime < time()) {  
            $type = \core\output\notification::NOTIFY_ERROR;
            echo $output->notification(get_string('expiredmeeting', 'mrproject'), $type); 
        }

        $mform->display();
        echo $output->footer($course);
        die;
    }

}




/************************************ Schedule a whole group in form ***********************************************/
if ($action == 'schedulegroup') {

    $permissions->ensure($permissions->can_edit_own_meetings());

    //$groupid = required_param('groupid', PARAM_INT);
    $groupid = optional_param('groupid', 0, PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    echo $output->header();

    //$actionurl = new moodle_url($baseurl, array('what' => 'schedulegroup', 'groupid' => $groupid));
    $actionurl = new moodle_url($baseurl, array('what' => 'addmeeting', 'groupid' => $groupid));

    $data = array();
    $i = 0;       //number of repetitions (count students in the group)
    $teachercaps = ['mod/mrproject:managealltasks'];
    foreach ($members as $member) {
        $isteacher = has_any_capability($teachercaps, $context, $member->id);
        if (!$isteacher) {
            $data['studentid'][$i] = $member->id;
            $i++;
        }
    }
    //$data['meetingheld'] = $i;

    $mform = new mrproject_editmeeting_form($actionurl, $mrproject, $cm, $groupsicansee, array('repeats' => $i));
    $mform->set_data($data);


    echo $output->heading(get_string('schedulemeeting', 'mrproject', $group->name));
    echo $output->box_start();
    $mform->display();
    echo $output->box_end();


    echo $output->footer();
    die();
}





/************************************ Send message to students ****************************************/
if ($action == 'sendmessage') {
    $permissions->ensure($permissions->can_edit_own_meetings());

    require_once($CFG->dirroot.'/mod/mrproject/message_form.php');

    $template = optional_param('template', 'none', PARAM_ALPHA);
    $recipientids = required_param('recipients', PARAM_SEQUENCE);

    $actionurl = new moodle_url('/mod/mrproject/view.php',
            array('what' => 'sendmessage', 'id' => $cm->id, 'subpage' => $subpage,
                  'template' => $template, 'recipients' => $recipientids));

    $templatedata = array();
    if ($template != 'none') {
        $vars = mrproject_messenger::get_mrproject_variables($mrproject, null, $USER, null, $COURSE, null);
        $templatedata['subject'] = mrproject_messenger::compile_mail_template($template, 'subject', $vars);
        $templatedata['body'] = mrproject_messenger::compile_mail_template($template, 'html', $vars);
    }
    $templatedata['recipients'] = $DB->get_records_list('user', 'id', explode(',', $recipientids), 'lastname,firstname');

    $mform = new mrproject_message_form($actionurl, $mrproject, $templatedata);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        mrproject_action_dosendmessage($mrproject, $formdata, $viewurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('sendmessage', 'mrproject'));
        $mform->display();
        echo $output->footer();
        die;
    }
}






/****************** Standard view ***********************************************/


//Event: meeting_list_viewed
//\mod_mrproject\event\meeting_list_viewed::create_from_mrproject($mrproject)->trigger();


// Print top tabs.
$actionurl = new moodle_url($viewurl, array('sesskey' => sesskey()));



/**********************************************************************************************************/
// Print header
echo $output->header();


/******************************* the menu (tabs)************************************/

$inactive = array();
if ($DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id)) <=
         $DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this mrproject.
    $inactive[] = 'mymeetingssubtab';
    if ($subpage = 'mymeetingssubtab') {
        $subpage = 'mymeetingssubtab';
    }
}

echo $output->teacherview_tabs($mrproject, $permissions, $taburl, $subpage, $inactive);



/********************************** Groups select *************************************/

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($mrproject->cm);
$currentgroupid = groups_get_activity_group($mrproject->cm, true);
//groups_print_activity_menu($mrproject->cm, $taburl);


// Display correct type of statistics by request.
$usergroups = ($currentgroupid > 0) ? array($currentgroupid) : '';


/****************************************get meetings list********************************************/

$teacherid = $USER->id;
$meetinggroup = 0;
//$meetinggroup = $currentgroupid;
$subpage = 'mymeetingssubtab';


$sqlcount = $mrproject->count_meetings_for_teacher($teacherid, $meetinggroup);

$pagesize = 25;
if ($offset == -1) {
    if ($sqlcount > $pagesize) {
        $offsetcount = $mrproject->count_meetings_for_teacher($teacherid, $meetinggroup, true);
        $offset = floor($offsetcount / $pagesize);
    } else {
        $offset = 0;
    }
}
if ($offset * $pagesize >= $sqlcount && $sqlcount > 0) {
    $offset = floor(($sqlcount - 1) / $pagesize);
}


//get meetings
$meetings = $mrproject->get_meetings_for_teacher($teacherid, $meetinggroup, $offset * $pagesize, $pagesize);

//$meetings = $mrproject->get_meetings_for_group($meetinggroup, $offset * $pagesize, $pagesize);


/************************************************************************/
//Title
$groupname = '';
foreach ($groupsthatcanseeme as $id => $group) {
    if ($id ==  $currentgroupid) {
        $groupname = $group->name;
    }
}
//echo $output->heading(get_string('membersheader', 'mrproject'). $groupname);


//get a Group (object)
$selectedgroup = null;
foreach ($groupsthatcanseeme as $id => $group) {
    if ($id ==  $currentgroupid) {
        $selectedgroup = $group;
    }
}

/*************************print groups select + Team Logo *******************************/
$str1 = '';
$str1 .= '<table class="teamlogo">';
$str1 .= '<tr>';
$str1 .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';


//select groups
$str1 .= groups_print_activity_menu($mrproject->cm, $taburl);

/*foreach ($groupsthatcanseeme as $id => $group) {
    $groupnames[] = $group->name;
}*/

if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {    //Separate groups

    //group logo
    if ($currentgroupid != 0) {
        if ($pictureurl = get_group_picture_url($selectedgroup, $COURSE->id, true, false)) {
            $str1 .= html_writer::img($pictureurl, '', ['title' => $groupname, 'class' => 'imglogo']);
        } else {
            $str1 .= html_writer::div(get_string('grouplogo', 'mrproject'), 'grouplogo');
        }
    }

} else {
    echo $output->notification(get_string('separategroups', 'mrproject'), 'notifyproblem');
}



//group name
//$str1 .= '<span class="team"><strong>'. $groupname .'</strong></span>';


$str1 .= '</tr>';
$str1 .= '</table>';

echo $str1;


if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {    //Separate groups

/**************************Title= 'Meetings'************************************/

echo $output->heading(get_string('meetings', 'mrproject'), 3);

//Print instructions (ex. You can add additional task meetings at any time.)
//$key = ($meetings) ? 'addmeeting' : 'welcomenewteacher';
//echo html_writer::div(get_string($key, 'mrproject'));



/****************************Actions: button to add meetings.*****************************/

$commandbar = new mrproject_command_bar();
$commandbar->title = get_string('actions', 'mrproject');

//Show 'Add meetings' button
$addbuttons = array();
$teachercaps = ['mod/mrproject:managealltasks']; 
$isteacher = has_any_capability($teachercaps, $context);
//if (!$isteacher && $currentgroupid != 0 && $currentgroupid != null) {
if ($currentgroupid != 0 && $currentgroupid != null) {
    $addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'schedulegroup', 'groupid' => $currentgroupid)), 'addcommands', 't/add');
    $commandbar->add_group(get_string('add', 'mrproject'), $addbuttons);
    echo $output->render($commandbar);
}





/*****************************************My meetings tab: Meetings table********************************************/

// Some meetings already exist - prepare the table of meetings.
if ($meetings) {

    $meetingman = new mrproject_meeting_manager($mrproject, $actionurl, $currentgroupid);    //see, renderable.php
    //$meetingman->showteacher = ($subpage == 'mymeetingssubtab');

    $meetingfilter = null;
    foreach ($meetings as $meeting) {

            $editable = $permissions->can_edit_meeting($meeting);

            $teachercaps = ['mod/mrproject:managealltasks']; 
            $name = '';
            foreach ($groupsthatcanseeme as $group) {
                foreach ($meeting->get_tasks() as $task) {
                    //$userfilter = $USER->id;
                    $userfilter = $task->studentid;
                    $isteacher = has_any_capability($teachercaps, $context, $userfilter);
                    $isnotstudent = has_any_capability($teachercaps, $context);

                    if ($userfilter && groups_is_member($group->id, $userfilter) && !$isteacher) {
                        $name = $group->name;   
                    }
                }
            }

            $studlist = new mrproject_student_list($meetingman->mrproject, $name, $group->id);
            $studlist->expandable = true;
            $studlist->expanded = true;
            $studlist->editable = $editable;
            $studlist->linktask = false;
            //$studlist->checkboxname = 'seen[]';
            //$studlist->buttontext = get_string('saveseen', 'mrproject');
            //$studlist->actionurl = new moodle_url($actionurl, array('what' => 'saveseen', 'meetingid' => $meeting->id));
                
            $teachercaps = ['mod/mrproject:managealltasks']; 
            $studentids = array();
            foreach ($meeting->get_tasks() as $app) {
                /*$studlist->add_student($app, false, $app->is_attended(), true, $mrproject->uses_studentdata(),
                                    $permissions->can_edit_attended($app)); */ //4th parameter --> to show grades. 5th parameter --> to show file icon
        
                if ($app->studentid != 0) {     
                    $isteacher = has_any_capability($teachercaps, $context, $app->studentid);

                    if ($isnotstudent) {      //teacher view

                        if (!$isteacher) {    //is student record
                            if (!in_array($app->studentid, $studentids)) {    //to eliminate duplicates students
                                array_push($studentids, $app->studentid);
    
                                //add a student
                                $studlist->add_student($app, false, false, false, false, false);    //see, renderable.php
                            }
                        }
                    } else {      //student view
                        
                        if (!$isteacher) {     //is student record
                            if (!in_array($app->studentid, $studentids)) {    //to eliminate duplicates students
                                array_push($studentids, $app->studentid);
    
                                //add a student
                                $studlist->add_student($app, false, false, false, false, false);    //see, renderable.php
                            }
                        } else {     //is teacher record ($highlight = true)
                            if (!in_array($app->studentid, $studentids)) {    //to eliminate duplicates students
                                array_push($studentids, $app->studentid);
    
                                //add a student
                                $studlist->add_student($app, true, false, false, false, false);    //see, renderable.php
                            }
                        }
                    }
                    
                }
            }


            //Display meetings with group filter
            $teachercaps = ['mod/mrproject:managealltasks']; 
            foreach ($meeting->get_tasks() as $task) {
                if ($task->studentid != 0) {
                    $userfilter = $task->studentid;
                    $isteacher = has_any_capability($teachercaps, $context, $userfilter);
                    if ($userfilter && groups_is_member($currentgroupid, $userfilter) && !$isteacher && $meetingfilter != $meeting) {
                        
                        $meetingman->add_meeting($meeting, $studlist, $editable);
                        $meetingfilter = $meeting;
                    } else {  //Display all meetings
                        if ($currentgroupid == 0 && $meetingfilter != $meeting) {
                            $meetingman->add_meeting($meeting, $studlist, $editable);
                            $meetingfilter = $meeting;
                        }
                    }
                }
            }
    }

    echo $output->render($meetingman);

    if ($sqlcount > $pagesize) {
        echo $output->paging_bar($sqlcount, $offset, $pagesize, $actionurl, 'offset');
    }

    
}




/******************************************My meetings tab: Plan student team meetings*******************************************/

echo $OUTPUT->box('<br/>');

$teachercaps = ['mod/mrproject:managealltasks']; 
$isteacher = has_any_capability($teachercaps, $context);

if ($isteacher) {

    echo html_writer::start_div('schedulelist');
    echo $output->heading(get_string('schedulegroups', 'mrproject'), 3);
    echo html_writer::div(get_string('welcomenewteacher', 'mrproject'));

    if (empty($groupsthatcanseeme)) {
        echo $output->notification(get_string('nogroups', 'mrproject'));
    } else {
        $grouptable = new mrproject_scheduling_list($mrproject, array());   //see, renderable.php
        $grouptable->id = 'groupstoschedule';

        $groupcnt = 0;
        foreach ($groupsthatcanseeme as $group) {
            $members = groups_get_members($group->id, user_picture::fields('u'), 'u.lastname, u.firstname');
            if (empty($members)) {
                continue;
            }

                if (!($picture = print_group_picture($group, $course->id, false, true, true))) {
                    $picture = html_writer::div(get_string('smallgrouplogo', 'mrproject'), 'smallgrouplogo');
                }

                $name = $group->name;
                $groupmembers = array();
                foreach ($members as $member) {

                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    $isteacher = has_any_capability($teachercaps, $context, $member);
                    if (!$isteacher) {
                        $groupmembers[] = fullname($member);
                    }             

                }
                //$name .= ' ['. implode(', ', $groupmembers) . ']';
                $memberslist = implode(', ', $groupmembers);
                $actions = array();
                $actions[] = new action_menu_link_secondary(
                                new moodle_url($actionurl, array('what' => 'schedulegroup', 'groupid' => $group->id)),
                                new pix_icon('e/insert_date', '', 'moodle'),
                                get_string('scheduleinmeeting', 'mrproject') );

                $grouptable->add_line($picture, $name, $memberslist, array(), $actions);   //see, renderable.php
                $groupcnt++;

        }
        // Print table of groups that still need to make tasks.
        if ($groupcnt > 0) {
            echo $output->render($grouptable);
        } else {
            echo $output->notification(get_string('nogroups', 'mrproject'));
        }
    }
    
    echo html_writer::end_div();
}


}

echo $output->footer();
