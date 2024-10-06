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



// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($mrproject->cm);
$currentgroupid = groups_get_activity_group($mrproject->cm, true);



//$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'view', 'subpage' => $subpage));

$baseurl = new moodle_url('/mod/mrproject/view.php', array(
        'id' => $mrproject->cmid,
        'subpage' => $subpage,
        'group' => $currentgroupid,
        'offset' => $offset
        
));

// The URL that is used for jumping back to the view (e.g., after an action is performed).
$viewurl = new moodle_url($baseurl, array('what' => 'memberspage'));
$PAGE->set_url($viewurl);

if ($action != 'memberspage') {
    require_once($CFG->dirroot.'/mod/mrproject/meetingforms.php');
    require_once($CFG->dirroot.'/mod/mrproject/teacherview.controller.php');
}




/************************************ Update student responsibilities ****************************************/

if ($action == 'editresponsibilities') {

    $permissions->ensure($permissions->can_edit_own_meetings());

    //$groupid = required_param('groupid', PARAM_INT);
    $groupid = optional_param('groupid', 0, PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    //echo $output->header();

    //$actionurl = new moodle_url($baseurl, array('what' => 'schedulegroup', 'groupid' => $groupid));
    $actionurl = new moodle_url($baseurl, array('what' => 'editresponsibilities', 'groupid' => $groupid));

    $data = array();
    $i = 0;       //number of repetitions (count students in the group)
    $teachercaps = ['mod/mrproject:managealltasks'];
    foreach ($members as $member) {
        $isteacher = has_any_capability($teachercaps, $context, $member->id);
        if (!$isteacher) {
            $data['studentid'][$i] = $member->id;
            $data['studentname'][$i] = fullname($member);
            $data['responsibility'][$i] = $DB->get_field('groups_members', 'multiroles', array('userid' => $member->id, 'groupid' => $groupid));
            $i++;
        }
    }
    //$data['meetingheld'] = $i;

    $mform = new mrproject_editresponsibilities_form($actionurl, $mrproject, $cm, $groupsicansee, array('repeats' => $i));
    $mform->set_data($data);


    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        $meeting = $mform->save_responsibilities($groupid, $formdata);   //function in meetingforms.php
        redirect($viewurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('editresponsibility', 'mrproject', $group->name), 3);
        echo $output->box_start();
        $mform->display();
        echo $output->box_end();
        echo $output->footer();
        die;
    }
}

/************************************ Update teacher roles ****************************************/

if ($action == 'editroles') {

    $permissions->ensure($permissions->can_edit_own_meetings());

    //$groupid = required_param('groupid', PARAM_INT);
    $groupid = optional_param('groupid', 0, PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    //echo $output->header();

    //$actionurl = new moodle_url($baseurl, array('what' => 'schedulegroup', 'groupid' => $groupid));
    $actionurl = new moodle_url($baseurl, array('what' => 'editroles', 'groupid' => $groupid));

    $data = array();
    $i = 0;       //number of repetitions (count students in the group)
    $teachercaps = ['mod/mrproject:managealltasks'];
    foreach ($members as $member) {
        $isteacher = has_any_capability($teachercaps, $context, $member->id);
        if ($isteacher) {

            $role = $DB->get_field('groups_members', 'multiroles', array('userid' => $member->id, 'groupid' => $groupid));
            if ($role == 'Tutor, Expert, Client') {
                $data['tutor'] = $member->id;
                $data['expert'] = $member->id;
                $data['client'] = $member->id;

            } elseif ($role == 'Tutor, Expert') {
                $data['tutor'] = $member->id;
                $data['expert'] = $member->id;

            } elseif ($role == 'Tutor, Client') {
                $data['tutor'] = $member->id;
                $data['client'] = $member->id;

            } elseif ($role == 'Expert, Client') {
                $data['expert'] = $member->id;
                $data['client'] = $member->id;

            } elseif ($role == 'Tutor') {
                $data['tutor'] = $member->id;

            } elseif ($role == 'Expert') {
                $data['expert'] = $member->id;

            } elseif ($role == 'Client') {
                $data['client'] = $member->id;
            }
            
        }
    }
    //$data['meetingheld'] = $i;

    $mform = new mrproject_editroles_form($actionurl, $mrproject, $cm, $groupsicansee, array('repeats' => $i));
    $mform->set_data($data);



    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        $meeting = $mform->save_roles($groupid, $formdata);   //function in meetingforms.php
        redirect($viewurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('editroles', 'mrproject', $group->name), 3);
        echo $output->box_start();
        $mform->display();
        echo $output->box_end();
        echo $output->footer();
        die;
    }
}



/****************** Standard view ***********************************************/



//header
echo $output->header();


// Print top tabs.
$actionurl = new moodle_url($viewurl, array('sesskey' => sesskey()));
$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid,
                         'what' => 'memberspage'));

/**************************the menu (tabs)************************************/

$inactive = array();
if ($DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id)) <=
         $DB->count_records('mrproject_meeting', array('mrprojectid' => $mrproject->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this mrproject.
    $inactive[] = 'memberstab';
    if ($subpage = 'memberstab') {
        $subpage = 'memberstab';
    }
}

$subpage = 'memberstab';

echo $output->teacherview_tabs($mrproject, $permissions, $taburl, $subpage, $inactive);



/**************************** Groups select *****************************/



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
        //$str1 .= groups_print_activity_menu($mrproject->cm, $taburl, false, true);  //true to hide all participants in the menu

        if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {     //Separate groups
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

/*********************************************************************************************/

//get students list
$groupfilter = $groupsthatcanseeme;
$maxlistsize = 500;
/*$students = array();
$reminderstudents = array();
if ($groupfilter === '') {
    $students = $mrproject->get_students_for_scheduling('', $maxlistsize);
    
} else if (count($groupfilter) > 0) {
    $students = $mrproject->get_students_for_scheduling(array_keys($groupfilter), $maxlistsize);
}*/


//get students list
$students = array();
$currentgroupid = groups_get_activity_group($mrproject->cm, true);
if ($currentgroupid != null) {
    $students = $mrproject->get_available_students($groupsicurrentlysee);
}


//get teachers list
$teachers = array();
$currentgroupid = groups_get_activity_group($mrproject->cm, true);
if ($currentgroupid != null) {
    $teachers = $mrproject->get_available_teachers($groupsicurrentlysee);
}



if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {      //Separate groups

/*************************************** Students list *********************************************/

if ($currentgroupid == null || $currentgroupid == 0) {
    echo $output->notification(get_string('selectgroup', 'mrproject'), 'notifyproblem');
}

if ($students === 0 && $currentgroupid != null) {
    $nostudentstr = get_string('noexistingstudents', 'mrproject');
    if ($COURSE->id == SITEID) {
        $nostudentstr .= '<br/>'.get_string('howtoaddstudents', 'mrproject');
    }
    echo $output->notification($nostudentstr, 'notifyproblem');
} else if (count($students) > 0) {
                 
    $userfields = mrproject_get_user_fields(null, $context);
    $fieldtitles = array();
    foreach ($userfields as $f) {      
        $fieldtitles[] = $f->title;     //Email address (head)
    }
    $studtable = new mrproject_students_list($mrproject, $fieldtitles);
    $studtable->id = 'studentstoschedule';

    foreach ($students as $student) {
        
        $picture = $output->user_picture($student);
        $name = $output->user_profile_link($mrproject, $student);

        $userfields = mrproject_get_user_fields($student, $context);
        $fieldvals = array();
        foreach ($userfields as $f) {
            $fieldvals[] = $f->value;        ////Email address
        }

        $currentgroupid = groups_get_activity_group($mrproject->cm, true);
        $responsibility = $DB->get_field('groups_members', 'multiroles', array('userid' => $student->id, 'groupid' => $currentgroupid));
        
        $teachercaps = ['mod/mrproject:managealltasks']; 
        $isteacher = has_any_capability($teachercaps, $context, $student->id);
        if (!$isteacher) {
            $studtable->add_line($picture, $name, $responsibility, $fieldvals);
        }

    }

    $divclass = 'schedulelist '.('fullsize');
    echo html_writer::start_div($divclass);
    echo $output->heading(get_string('memberstudents', 'mrproject'), 3);

    
    /************************ commandbar: action button ***********************/
    $commandbar = new mrproject_command_bar();
    $commandbar->title = get_string('actions', 'mrproject');

    //Show 'Add meetings' button
    $addbuttons = array();
    $teachercaps = ['mod/mrproject:managealltasks']; 
    $isteacher = has_any_capability($teachercaps, $context);
    //if (!$isteacher) {
        $addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'editresponsibilities', 'groupid' => $currentgroupid)), 'editresponsibilities', 't/assignroles');
        $commandbar->add_group(get_string('responsibility', 'mrproject'), $addbuttons);
        echo $output->render($commandbar);
    //}
    


    /************************ Print table of students *************************/
    echo $output->render($studtable);
    echo html_writer::end_div();






/***************************************** Teachers list ***********************************************/

        echo html_writer::start_div('schedulelist fullsize');

        if (empty($groupsicanschedule)) {
            echo $output->notification(get_string('nogroups', 'mrproject'));
        } else {

            $userfields = mrproject_get_user_fields(null, $context);
            $fieldtitles = array();
            foreach ($userfields as $f) {
                $fieldtitles[] = $f->title;
            }
            
            $teachertable = new mrproject_supervisors_list($mrproject, $fieldtitles);
            $teachertable->id = 'studentstoschedule';

            $groupcnt = 0;
            foreach ($teachers as $teacher) {
                $picture = $output->user_picture($teacher);
                $name = $output->user_profile_link($mrproject, $teacher);
        
                $userfields = mrproject_get_user_fields($teacher, $context);
                $fieldvals = array();
                foreach ($userfields as $f) {
                    $fieldvals[] = $f->value;
                }

                $currentgroupid = groups_get_activity_group($mrproject->cm, true);
                $role = $DB->get_field('groups_members', 'multiroles', array('userid' => $teacher->id, 'groupid' => $currentgroupid));

                $teachercaps = ['mod/mrproject:managealltasks']; 
                $isteacher = has_any_capability($teachercaps, $context, $teacher->id);
                if ($isteacher) {
                    $teachertable->add_line($picture, $name, $role, $fieldvals);
                }
                $groupcnt++;
            }

            
        }
        
        $divclass = 'schedulelist '.('fullsize');
        echo html_writer::start_div($divclass);
        echo $output->heading(get_string('membersteachers', 'mrproject'), 3);

        
        /************************ commandbar: action button ***********************/
        $commandbar = new mrproject_command_bar();
        $commandbar->title = get_string('actions', 'mrproject');

        //Show 'Add meetings' button
        $addbuttons = array();
        $teachercaps = ['mod/mrproject:managealltasks']; 
        $isteacher = has_any_capability($teachercaps, $context);
        if ($isteacher) {
            $addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'editroles', 'groupid' => $currentgroupid)), 'editroles', 't/assignroles');
            $commandbar->add_group(get_string('role', 'mrproject'), $addbuttons);
            echo $output->render($commandbar);
        }
        
        


        /************************ Print table of teachers *************************/
        if (count($teachers) > 0) {
            echo $output->render($teachertable);
        } else {
            echo $output->notification(get_string('nogroups', 'mrproject'));
        }
        echo html_writer::end_div();
    

} 

}

echo $output->footer();