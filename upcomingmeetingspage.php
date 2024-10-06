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

 


$PAGE->set_docs_path('mod/mrproject/upcomingmeetingspage');

//URL Parameters (id, sesskey)
$urlparas = array(
        'id' => $mrproject->cmid,
        'sesskey' => sesskey()
);
//if ($appointgroup >= 0) {
    $urlparas['appointgroup'] = $appointgroup;
//}
$actionurl = new moodle_url('/mod/mrproject/view.php', $urlparas);



// General permissions check.
//require_capability('mod/mrproject:viewmeetings', $context);        //View tasks that are available for booking
$canbook = has_capability('mod/mrproject:appoint', $context);   //can book an task which has been offered by a teacher
$canseefull = has_capability('mod/mrproject:viewfullmeetings', $context);    //Allows students to see meetings in the future even if they are already fully booked



//group scheduling
//if ($mrproject->is_group_scheduling_enabled()) {     //Retrieve whether group scheduling is enabled in this instance (see the funtion in .../model/mrproject.php)
    $mygroupsforscheduling = groups_get_all_groups($mrproject->courseid, $USER->id, 0, 'g.id, g.name');
    if ($appointgroup > 0 && !array_key_exists($appointgroup, $mygroupsforscheduling)) {
        throw new moodle_exception('nopermissions');
    }
//}

//if ($mrproject->is_group_scheduling_enabled()) {
    $canbook = $canbook && ($appointgroup >= 0);
/*} else {
    $appointgroup = 0;
}*/


require_once($CFG->dirroot.'/mod/mrproject/studentview.controller.php');

// Load group restrictions.
$groupmode = groups_get_activity_groupmode($cm);

// Find groups which can book an task with the current teacher ($groupsthatcanseeme).
$groupsthatcanseeme = '';
if ($groupmode) {
    $groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);
}

/**********************************************************************************************************/

//Trigger event: available_meetings_viewed
\mod_mrproject\event\available_meetings_viewed::create_from_mrproject($mrproject)->trigger();


// Print header
echo $output->header();


/**************************the menu (tabs)************************************/
$inactive = array();
if ($DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id)) <=
         $DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this mrproject.
    $inactive[] = 'upcomingmeetingssubtab';
    if ($subpage = 'upcomingmeetingssubtab') {
        $subpage = 'upcomingmeetingssubtab';
    }
}

echo $output->teacherview_tabs($mrproject, $permissions, $taburl, $subpage, $inactive);




/*********************************************** Upcoming meetings **************************************************/

echo $output->heading(get_string('upcomingmeetings', 'mrproject'), 3);


$teachercaps = ['mod/mrproject:managealltasks']; 
$isteacher = has_any_capability($teachercaps, $context, $USER->id);
if (!$isteacher) {
    $upcomingmeetings = $mrproject->get_upcoming_meetings_for_student($USER->id);       //for students
} else {
    $upcomingmeetings = $mrproject->get_upcoming_meetings_for_teacher($USER->id);       //for teacher
}


if (empty($upcomingmeetings)) {
    $type = \core\output\notification::NOTIFY_SUCCESS;
    //$type = \core\output\notification::NOTIFY_WARNING;
    //$type = \core\output\notification::NOTIFY_ERROR;
    //$type = \core\output\notification::NOTIFY_INFO;
    echo $output->notification(get_string('noupcomingmeetings', 'mrproject'), $type);
}

if (count($upcomingmeetings) > 0) {
    $meetingtable = new mrproject_meeting_table($mrproject, false, $actionurl);   //See, renderable.php  
    $meetingfilter = null;
    foreach ($upcomingmeetings as $meeting) {

        $studenttask = $meeting->get_student_task($USER->id);
        
        if ($meeting->is_groupmeeting()) {
            $showothergrades = has_capability('mod/mrproject:seeotherstudentsresults', $context);

            
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

                if ($otherapp->studentid != 0) {
                    //$gradehidden = !$mrproject->uses_grades() || ($mrproject->get_gradebook_info($otherapp->studentid)->hidden <> 0) || (!$showothergrades && $otherapp->studentid <> $USER->id);
                    //$others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
                    
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

        //$cancancel = $meeting->is_in_bookable_period();
        $teachercaps = ['mod/mrproject:managealltasks']; 
        $isteacher = has_any_capability($teachercaps, $context);
        $isnotstudent = has_any_capability($teachercaps, $context);   //isnotstudent = cancancel in add_meeting function
        
        $canedit = $mrproject->uses_studentdata();
        $canview = $mrproject->uses_studentdata();
        
        //$cancancel = $cancancel && ($appointgroup >= 0);
        
        foreach ($meeting->get_tasks() as $task) {
            foreach ($groupsthatcanseeme as $id => $group) {
                if (groups_is_member($id, $USER->id) && groups_is_member($id, $meeting->teacherid) && groups_is_member($id, $task->studentid) && $meetingfilter != $meeting) {

                    $meetwithteacher = false;
                    foreach ($meeting->get_tasks() as $task) {
                        if (has_any_capability($teachercaps, $context, $task->studentid)) {
                            $meetwithteacher = true;
                            break;
                        }
                    }

                    if (!has_any_capability($teachercaps, $context)) {   //student

                        if (!$meetwithteacher) {  //between student
                            $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, $canedit, $canview);    //function in renderable.php
                            $meetingfilter = $meeting;
                            
                        } else {
                            $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, false, $canview);    //function in renderable.php
                            $meetingfilter = $meeting;
                        }

                    } else {   //teacher
                        $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, $canedit, $canview);    //function in renderable.php
                        $meetingfilter = $meeting;
                    }
                }
            }
        }
    }

    
    echo $output->render($meetingtable);
}



/************************************ Available meetings **************************************************/

echo $OUTPUT->box('<br/>');
echo $output->heading(get_string('availablemeetings', 'mrproject'), 3);
echo html_writer::div(get_string('acceptmeetingnote', 'mrproject'));


$availablemeetings = $mrproject->get_vailable_meetings_for_student($USER->id);       //function in model/mrproject.php
if (empty($availablemeetings)) {
    $type = \core\output\notification::NOTIFY_SUCCESS;
    //$type = \core\output\notification::NOTIFY_WARNING;
    //$type = \core\output\notification::NOTIFY_ERROR;
    //$type = \core\output\notification::NOTIFY_INFO;
    echo $output->notification(get_string('nomeetingsavailable', 'mrproject'), $type);
}


if (count($availablemeetings) > 0) {
    $meetingtable = new mrproject_availablemeeting_table($mrproject, false, $actionurl);   //See, renderable.php  
    $meetingfilter = null;
    foreach ($availablemeetings as $meeting) {
        $studenttask = $meeting->get_student_task($USER->id);

        if ($meeting->is_groupmeeting()) {
            $showothergrades = has_capability('mod/mrproject:seeotherstudentsresults', $context);


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

                if ($otherapp->studentid != 0) {

                    //$gradehidden = !$mrproject->uses_grades() || ($mrproject->get_gradebook_info($otherapp->studentid)->hidden <> 0) || (!$showothergrades && $otherapp->studentid <> $USER->id);
                    //$others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
                    
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
        
        //$cancancel = $meeting->is_in_bookable_period();
        $canedit = $mrproject->uses_studentdata();
        $canview = $mrproject->uses_studentdata();
        //$cancancel = $cancancel && ($appointgroup >= 0);

        foreach ($groupsthatcanseeme as $id => $group) {
            if (groups_is_member($id, $USER->id) && groups_is_member($id, $meeting->teacherid) && $meetingfilter != $meeting) {
                
                $meetwithteacher = false;
                foreach ($meeting->get_tasks() as $task) {
                    if (has_any_capability($teachercaps, $context, $task->studentid)) {
                        $meetwithteacher = true;
                        break;
                    }
                }

                //$meeting->teacherid != $USER->id  

                if (!has_any_capability($teachercaps, $context)) {   //student
                    if (!$meetwithteacher) {   //between student
                        $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, $canedit, $canview);    //function in renderable.php
                        $meetingfilter = $meeting;

                    } else {
                        $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, false, $canview);    //function in renderable.php
                        $meetingfilter = $meeting;
                    }
                } else {   //teacher
                    $meetingtable->add_meeting($meeting, $studenttask, $others, $USER->id, $id, $isnotstudent, $canedit, $canview);    //function in renderable.php
                    $meetingfilter = $meeting;
                }
            }
        }
      
    }

    
    echo $output->render($meetingtable);
}



/************************************************************************************************/


//echo footer
echo $output->footer();