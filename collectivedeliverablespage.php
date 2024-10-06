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
 * Student mrproject screen (where students choose tasks).
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;


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


//URL Parameter (appointgroup)
$appointgroup = optional_param('appointgroup', -1, PARAM_INT);    //check this parameter before including the 'studentview.controller.php' page. because the parameter 'appointgroup' is used in this page




$PAGE->set_docs_path('mod/mrproject/collectivedeliverablespage');

//URL Parameters (id, sesskey)
$urlparas = array(
        'id' => $mrproject->cmid,
        'sesskey' => sesskey()
);
//if ($appointgroup >= 0) {
    $urlparas['appointgroup'] = $appointgroup;
//}
$actionurl = new moodle_url('/mod/mrproject/view.php', $urlparas);
$actionurl2 = new moodle_url('/mod/mrproject/view.php', $urlparas);



// General permissions check.
//require_capability('mod/mrproject:viewmeetings', $context);        //View tasks that are available for booking
$canbook = has_capability('mod/mrproject:appoint', $context);   //can book an task which has been offered by a teacher
$canseefull = has_capability('mod/mrproject:viewfullmeetings', $context);    //Allows students to see meetings in the future even if they are already fully booked



//group scheduling
//if ($mrproject->is_group_scheduling_enabled()) {     //Retrieve whether group scheduling is enabled in this instance (see the funtion in .../model/mrproject.php)
    $mygroupsforscheduling = groups_get_all_groups($mrproject->courseid, $USER->id, 1, 'g.id, g.name');
    if ($appointgroup > 0 && !array_key_exists($appointgroup, $mygroupsforscheduling)) {
        throw new moodle_exception('nopermissions');
    }
//}

//if ($mrproject->is_group_scheduling_enabled()) {
    $canbook = $canbook && ($appointgroup >= 0);
/*} else {
    $appointgroup = 0;
}*/



$currentgroup = $currentgroup = groups_get_activity_group($cm, true);


// Find groups which the current teacher can see ($groupsicansee, $groupsicurrentlysee).
// $groupsicansee: contains all groups that a teacher potentially has access to.
// $groupsicurrentlysee: may be restricted by the user to one group, using the drop-down box.
$userfilter = $USER->id;
if (has_capability('moodle/site:accessallgroups', $context)) {
    $userfilter = 0;
}
$groupsicansee = '';
$groupsicurrentlysee = '';
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
    



/*****************************************/
//$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'meetingspage', 'subpage' => $subpage));
$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'deliverablespage', 'subpage' => 'collectivedeliverablessubtab'));

$baseurl = new moodle_url('/mod/mrproject/view.php', array(
    'id' => $mrproject->cmid,
    'subpage' => $subpage,
    'offset' => $offset
));
// The URL that is used for jumping back to the view (e.g., after an action is performed).
$viewurl = new moodle_url($baseurl, array('what' => 'deliverablespage'));


$PAGE->set_url($viewurl);

if ($action != 'deliverablespage') {
    require_once($CFG->dirroot.'/mod/mrproject/meetingforms.php');
    require_once($CFG->dirroot.'/mod/mrproject/teacherview.controller.php');
}



require_once($CFG->dirroot.'/mod/mrproject/studentview.controller.php');

// Load group restrictions.
$groupmode = groups_get_activity_groupmode($cm);

// Find groups which can book an task with the current teacher ($groupsthatcanseeme).
$groupsthatcanseeme = '';
if ($groupmode) {
    $groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);
}




/************************************ Action: add meeting report ****************************************/
if ($action == 'addmeetingreport') {

    $meetingid = required_param('meetingid', PARAM_INT);
    $meeting = $mrproject->get_meeting($meetingid);
    //$permissions->ensure($permissions->can_edit_meeting($meeting));

    
    if ($meeting->starttime % 300 !== 0 || $meeting->duration % 5 !== 0) {
        $timeoptions = array('step' => 1, 'optional' => false);
    } else {
        $timeoptions = array('step' => 5, 'optional' => false);
    }

    $actionurl = new moodle_url($baseurl, array('what' => 'addmeetingreport', 'meetingid' => $meetingid));


    //$groupsicansee
    $mform = new mrproject_addmeetingreport_form($actionurl, $mrproject, $cm, $groupsicurrentlysee, array(
            'meetingid' => $meetingid,
            'timeoptions' => $timeoptions)
        );

    $data = $mform->prepare_formdata($meeting);
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_meeting($meetingid, $formdata);

        //Event: meeting_report_edited
        \mod_mrproject\event\meeting_report_edited::create_from_meeting($meeting)->trigger();

        redirect($viewurl,
                 get_string('meetingreportadded', 'mrproject'),
                 0,
                 \core\output\notification::NOTIFY_SUCCESS);
    } else {
        
        echo $output->header();
        echo $output->heading(get_string('meetingreport', 'mrproject'), 3);


        //Disable meeting report and display notification
        $disablereport = false;
        foreach ($meeting->get_tasks() as $task) {
            if ($task->evaluatedby != 0 && $task->submissiondate != 0) {
                $disablereport = true;
            }
        }
        if ($disablereport) {
            //Notification
            $type = \core\output\notification::NOTIFY_ERROR;
            echo $output->notification(get_string('disablemeetingreport', 'mrproject'), $type);
        }
        

        $mform->display();
        echo $output->footer($course);
        die;
    }

}


/**********************************************************************************************************/
// Print header
echo $output->header();



/**************************the menu (tabs)************************************/
$inactive = array();
if ($DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id)) <=
         $DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this mrproject.
    $inactive[] = 'collectivedeliverablessubtab';
    if ($subpage = 'collectivedeliverablessubtab') {
        $subpage = 'collectivedeliverablessubtab';
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

/****************************************** Meetings held **************************************************/

//--------------------------- Heading ----------------------------
echo $output->heading(get_string('attendedmeetings', 'mrproject'), 3);
echo html_writer::div(get_string('feedbacknote', 'mrproject'));



/***************************************************************************************/


// Get past (attended) meetings.

$teachercaps = ['mod/mrproject:managealltasks']; 
$isteacher = has_any_capability($teachercaps, $context, $USER->id);
if (!$isteacher) {
    $pastmeetings = $mrproject->get_attended_meetings_for_student($USER->id);      //for students
} else {
    $pastmeetings = $mrproject->get_attended_meetings_for_teacher($USER->id);       //for teacher
}


if (empty($pastmeetings)) {
    $type = \core\output\notification::NOTIFY_SUCCESS;
    //$type = \core\output\notification::NOTIFY_WARNING;
    //$type = \core\output\notification::NOTIFY_ERROR;
    //$type = \core\output\notification::NOTIFY_INFO;
    echo $output->notification(get_string('noheldmeetings', 'mrproject'), $type);
}

if (count($pastmeetings) > 0) {
    
    $meetingtable = new mrproject_attendedmeeting_table($mrproject, false, $actionurl2);
    $meetingfilter = null;
    foreach ($pastmeetings as $meeting) {
        $studenttask = $meeting->get_student_task($USER->id);

        if ($meeting->is_groupmeeting()) {

            //group name
            $teachercaps = ['mod/mrproject:managealltasks']; 
            $name = '';
            foreach ($groupsthatcanseeme as $group) {
                foreach ($meeting->get_tasks() as $task) {
                    //$userfilter = $USER->id;
                    $userfilter = $task->studentid;
                    $isteacher = has_any_capability($teachercaps, $context, $userfilter);

                    if ($userfilter && groups_is_member($group->id, $userfilter) && !$isteacher) {
                        $name = $group->name;   
                    }
                }
            }


            $others = new mrproject_student_list($mrproject, $name);
            $others->expandable = true;
            $others->expanded = true;
            $others->editable = $permissions->can_edit_meeting($meeting);
            $others->linktask = false;



            $teachercaps = ['mod/mrproject:managealltasks'];
            $studentids = array();
            foreach ($meeting->get_tasks() as $otherapp) {
                
                $othermark = $mrproject->get_gradebook_info($otherapp->studentid);
                $gradehidden = !is_null($othermark) && ($othermark->hidden <> 0);
                //$others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
                
                if ($otherapp->studentid != 0) {
                    $isteacher = has_any_capability($teachercaps, $context, $otherapp->studentid);
                    if (!$isteacher) {

                        if (!in_array($otherapp->studentid, $studentids)) {    //to eliminate duplicates students
                            array_push($studentids, $otherapp->studentid);

                            //add a student
                            $others->add_student($otherapp, false, false, false, false, false);    //see, renderable.php
                        
                        }
                        
                    }
                }
            }

        } else {
            $others = null;
        }
        $teachercaps = ['mod/mrproject:managealltasks']; 
        $isteacher = has_any_capability($teachercaps, $context);
        $isnotstudent = has_any_capability($teachercaps, $context);   //isnotstudent = cancancel in add_meeting function
        $canedit = !$isteacher;
        $hasdetails = $mrproject->uses_studentdata();


        //Display meetings with group filter
        $teachercaps = ['mod/mrproject:managealltasks']; 
        foreach ($meeting->get_tasks() as $task) {
            if ($task->studentid != 0) {
                $userfilter = $task->studentid;
                $isteacher = has_any_capability($teachercaps, $context, $userfilter);

                //Display meetings with group filter
                if (groups_is_member($currentgroupid, $task->studentid) && groups_is_member($currentgroupid, $meeting->teacherid) && groups_is_member($currentgroupid, $USER->id) && !$isteacher && $meetingfilter != $meeting) {
                    
                    $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $currentgroupid, $isnotstudent, $canedit, $hasdetails);
                    $meetingfilter = $meeting;

                } else {  //Display all meetings
                    if ($currentgroupid == 0 && $meetingfilter != $meeting) {

                        foreach ($groupsthatcanseeme as $id => $group) {
                            if (groups_is_member($id, $meeting->teacherid) && groups_is_member($id, $task->studentid) && groups_is_member($id, $USER->id) && !$isteacher && $meetingfilter != $meeting) {
                                $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, $canedit, $hasdetails);
                                $meetingfilter = $meeting;
                            }
                        }
                    }
                }
            }
        }


    }

    echo $output->render($meetingtable);
} 



/************************************************************************************************/

}

//echo footer
echo $output->footer();