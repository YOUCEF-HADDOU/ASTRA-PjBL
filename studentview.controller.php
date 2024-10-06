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
 * Controller for student view
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();



require_once($CFG->dirroot.'/mod/mrproject/mailtemplatelib.php');



/*******************************************Available meetings: Accept meeting button*******************************************/
/**
 * mrproject_book_meeting
 *
 * @param mrproject_instance $mrproject
 * @param int $meetingid
 * @param int $userid
 * @param int $groupid
 * @param mrproject_booking_form $mform
 * @param mixed $formdata
 * @param mixed $returnurl
 * @throws mixed moodle_exception
 */
function mrproject_book_meeting($mrproject, $meetingid, $userid, $groupid, $mform, $formdata, $returnurl) {

    global $DB, $COURSE, $output;

    $meeting = $mrproject->get_meeting($meetingid);
    if (!$meeting) {
        throw new moodle_exception('error');
    }

    if (!$meeting->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $requiredcapacity = 1;
    $userstobook = array($userid);
    if ($groupid > 0) {
        /*if (!$mrproject->is_group_scheduling_enabled()) {
            throw new moodle_exception('error');
        }*/
        $groupmembers = $mrproject->get_available_students($groupid);
        $requiredcapacity = count($groupmembers);
        $userstobook = array_keys($groupmembers);
    } /*else if ($groupid == 0) {
        if (!$mrproject->is_individual_scheduling_enabled()) {
            throw new moodle_exception('error');
        }
    }*/ else {
        // Group scheduling enabled but no group selected.
        throw new moodle_exception('error');
    }

    $errormessage = '';

    $bookinglimit = $mrproject->count_bookable_tasks($userid, false);
    if ($bookinglimit == 0) {
        $errormessage = get_string('selectedtoomany', 'mrproject', $bookinglimit);
    } else {
        // Validate our user ids.
        $existingstudents = array();
        foreach ($meeting->get_tasks() as $app) {
            $existingstudents[] = $app->studentid;
        }
        $userstobook = array_diff($userstobook, $existingstudents);

        $remaining = $meeting->count_remaining_tasks();
        // If the meeting is already overcrowded...
        if ($remaining >= 0 && $remaining < $requiredcapacity) {
            if ($requiredcapacity > 1) {
                $errormessage = get_string('notenoughplaces', 'mrproject');
            } else {
                $errormessage = get_string('meeting_is_just_in_use', 'mrproject');
            }
        }
    }

    if ($errormessage) {
        \core\notification::error($errormessage);
        redirect($returnurl);
    }

    // Create new task for each member of the group.
    foreach ($userstobook as $studentid) {
        $task = $meeting->create_task();
        $task->studentid = $studentid;
        //$task->attended = 0;
        //$task->timecreated = time();
        $task->timemodified = time();
        $task->save();

        if (($studentid == $userid) && $mform) {
            $mform->save_booking_data($formdata, $task);
        }

        \mod_mrproject\event\booking_added::create_from_meeting($meeting)->trigger();

        // Notify the teacher.
        //if ($mrproject->allownotifications) {
            $student = $DB->get_record('user', array('id' => $task->studentid), '*', MUST_EXIST);
            $teacher = $DB->get_record('user', array('id' => $meeting->teacherid), '*', MUST_EXIST);
            mrproject_messenger::send_meeting_notification($meeting, 'bookingnotification', 'applied',
                    $student, $teacher, $teacher, $student, $COURSE);
        //}
    }
    $meeting->save();
    redirect($returnurl);

}


// Route to screen.
$teachercaps = ['mod/mrproject:manage', 'mod/mrproject:managealltasks', 'mod/mrproject:canseeotherteachersbooking'];
$isteacher = has_any_capability($teachercaps, $context);


$returnurlparas = array('id' => $cm->id);
if ($isteacher) {
    $returnurl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'meetingspage', 'subpage' => 'upcomingmeetingssubtab'));
    $returnurl1 = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'deliverablespage', 'subpage' => 'collectivedeliverablessubtab'));
    $returnurl2 = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'deliverablespage', 'subpage' => 'individualdeliverablessubtab'));
} else{
    $returnurl = new moodle_url('/mod/mrproject/view.php', $returnurlparas);
    $returnurl1 = new moodle_url('/mod/mrproject/view.php', $returnurlparas);
    $returnurl2 = new moodle_url('/mod/mrproject/view.php', $returnurlparas);
}







/******************************************** Show the booking form *******************************************/

if ($action == 'bookingform') {  
    require_once($CFG->dirroot.'/mod/mrproject/bookingform.php');

    require_sesskey();
    //require_capability('mod/mrproject:appoint', $context);

    $meetingid = required_param('meetingid', PARAM_INT);
    $meeting = $mrproject->get_meeting($meetingid);

    $actionurl = new moodle_url($returnurl, array('what' => 'bookingform', 'meetingid' => $meetingid));

    $mform = new mrproject_booking_form($meeting, $actionurl);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if (($formdata = $mform->get_data()) || $appointgroup < 0) {
        // Workaround - call mrproject_book_meeting also if no group was selected, to show an error message.
        mrproject_book_meeting($mrproject, $meetingid, $USER->id, $appointgroup, $mform, $formdata, $returnurl);
        redirect($returnurl);
    } else {
        $groupinfo = null;
        /*if ($mrproject->is_group_scheduling_enabled() && $appointgroup == 0) {
            $groupinfo = get_string('myself', 'mrproject');
        } else if ($appointgroup > 0) {*/
            $groupinfo = $mygroupsforscheduling[$appointgroup]->name;
        //}

        echo $output->header();
        echo $output->heading(get_string('bookameeting', 'mrproject'), 3);
        echo $output->box(format_text($mrproject->intro, $mrproject->introformat));

        $info = mrproject_task_info::make_from_meeting($meeting, true, true, $groupinfo);
        echo $output->render($info);
        $mform->display();
        echo $output->footer();
        exit();
    }

}




/************************************************ Accept a meeting (Available meetings) ************************************************/

if ($action == 'bookmeeting') {

    require_sesskey();
    //require_capability('mod/mrproject:appoint', $context);

    // Get the request parameters.
    $meetingid = required_param('meetingid', PARAM_INT);
    $selecteddate = required_param('selecteddate', PARAM_INT);
    $groupid = required_param('groupid', PARAM_INT);

    


    //meetingaccepted = 1
    global $DB;
    $object = new stdClass();
    $object->id = $meetingid;
    $object->meetingaccepted = 1;             
    $object->meetingheld = 0;  
    $object->starttime = $selecteddate;    //selected date when accepting the meeting   
    $DB->update_record('mrproject_meeting', $object);

    
    //meeting
    $meeting = $mrproject->get_meeting($meetingid);


    /**************************** Notify the team members ******************************/
    
    include_once($CFG->dirroot.'/mod/mrproject/mailtemplatelib.php');

    //Who accepted the meeting
    $teacher = $DB->get_record('user', array('id' => $USER->id));     

    
    //inform team members (Notification)
    //$teammembers = groups_get_members($groupid);


    //get participants
    $participants = array();
    foreach ($meeting->get_tasks() as $task) {
        if ($task->studentid != 0) {   //Student
            if ($task->collectivetask == null) {       //Individual task
                if (!in_array($task->studentid, $participants)) { 
                    array_push($participants, $task->studentid);
                }

            } else {        //Collective task
                $studentids = explode('+', $task->collectivetask);
                foreach ($studentids as $studid) {
                    if (!in_array(intval($studid), $participants)) { 
                        array_push($participants, intval($studid));
                    }
                }
            }
        
        } else {    //Teacher
            if (!in_array($meeting->teacherid, $participants)) {  
                array_push($participants, $meeting->teacherid);
            }
        }
    }

    //foreach ($teammembers as $member) {
    foreach ($participants as $participantid) {
        $student = $DB->get_record('user', array('id' => $participantid));

        //enable notifications for the user (user_preferences table)
        set_user_preferences([
            'message_provider_mod_mrproject_bookingnotification_loggedin' => 'popup,email',
            'message_provider_mod_mrproject_bookingnotification_loggedoff' => 'popup,email',
        ], $participantid);

        //Send notification
        mrproject_messenger::send_meeting_notification($meeting, 'bookingnotification', 'applied',
                            $teacher, $student, $teacher, $student, $COURSE, $groupid);    // 'teachercancelled' --> meeting cancelled,  'applied' --> meeting accepted
    }
    
    /******************************************************************************/

    //Event: meeting_accepted
    \mod_mrproject\event\meeting_accepted::create_from_meeting($meeting)->trigger();


    redirect($returnurl);
}





/******************************************** See details (meeting) *******************************************/


if ($action == 'viewmeeting') {
    require_once($CFG->dirroot.'/mod/mrproject/feedbackmeetingform.php');

    require_sesskey();
    //require_capability('mod/mrproject:appoint', $context);

    /*if (!$mrproject->uses_studentdata()) {
        throw new moodle_exception('error');
    }*/

    //$taskid = required_param('taskid', PARAM_INT);
    //list($meeting, $task) = $mrproject->get_meeting_task($taskid);

    $meetingid = required_param('meetingid', PARAM_INT);
    $meeting = $mrproject->get_meeting($meetingid);


    /*if ($task->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }*/
    /*if (!$meeting->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }*/

    $actionurl = new moodle_url($returnurl1, array('what' => 'addfeedback', 'meetingid' => $meetingid));

    
    $teachercaps = ['mod/mrproject:managealltasks']; 
    $isteacher = has_any_capability($teachercaps, $context);
    if ($isteacher) {

        $mform = new mrproject_feedback_form($meeting, $actionurl, true);
        $mform->set_data($mform->prepare_booking_data($meeting));
    
        if ($mform->is_cancelled()) {
            redirect($returnurl1);
        } else if ($formdata = $mform->get_data()) {
            $mform->save_booking_data($formdata, $meeting);
            redirect($returnurl1);
        } else {
            echo $output->header();
            echo $output->heading(get_string('meetingreport', 'mrproject'), 3);
            //echo $output->box(format_text($mrproject->intro, $mrproject->introformat));
            //$info = mrproject_task_info::make_from_meeting($meeting);
            $info = mrproject_meeting_report::make_from_meeting($meeting);

            //Event: meeting_report_viewed
            \mod_mrproject\event\meeting_report_viewed::create_from_meeting($meeting)->trigger();

            echo $output->render($info);
            $mform->display();
            echo $output->footer();
            exit();
        }
    } else {
        echo $output->header();
        echo $output->heading(get_string('meetingreport', 'mrproject'), 3);
        //echo $output->box(format_text($mrproject->intro, $mrproject->introformat));
        //$info = mrproject_task_info::make_from_meeting($meeting);
        $info = mrproject_meeting_report::make_from_meeting($meeting);

        //Event: meeting_report_viewed
        \mod_mrproject\event\meeting_report_viewed::create_from_meeting($meeting)->trigger();
        
        echo $output->render($info);
        echo $output->footer();
        exit();
    }

}




/******************************************** Feedback: Meeting report *******************************************/

if ($action == 'addfeedback') {
    require_once($CFG->dirroot.'/mod/mrproject/feedbackmeetingform.php');

    require_sesskey();
    //require_capability('mod/mrproject:appoint', $context);

    /*if (!$mrproject->uses_studentdata()) {
        throw new moodle_exception('error');
    }*/

    //$taskid = required_param('taskid', PARAM_INT);
    //list($meeting, $task) = $mrproject->get_meeting_task($taskid);

    $meetingid = required_param('meetingid', PARAM_INT);
    $meeting = $mrproject->get_meeting($meetingid);

    /*if ($task->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }*/
    /*if (!$meeting->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }*/

    $actionurl = new moodle_url($returnurl1, array('what' => 'addfeedback', 'meetingid' => $meetingid));

    $mform = new mrproject_feedback_form($meeting, $actionurl, true);
    $mform->set_data($mform->prepare_booking_data($meeting));

    if ($mform->is_cancelled()) {
        redirect($returnurl1);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_booking_data($formdata, $meeting);
        redirect($returnurl1);
    } else {
        echo $output->header();
        echo $output->heading(get_string('meetingreport', 'mrproject'), 3);
        //echo $output->box(format_text($mrproject->intro, $mrproject->introformat));
        //$info = mrproject_task_info::make_from_meeting($meeting);, $task
        $info = mrproject_task_info::make_from_meeting($meeting);
        echo $output->render($info);
        $mform->display();
        echo $output->footer();
        exit();
    }

}




/******************************************** Meeting report *******************************************/

if ($action == 'submittaskreport') {
    require_once($CFG->dirroot.'/mod/mrproject/submittaskform.php');

    require_sesskey();
    //require_capability('mod/mrproject:appoint', $context);

    if (!$mrproject->uses_studentdata()) {
        throw new moodle_exception('error');
    }

    $taskid = required_param('taskid', PARAM_INT);
    list($meeting, $task) = $mrproject->get_meeting_task($taskid);

    /*if ($task->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }*/
    /*if (!$meeting->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }*/

    $actionurl = new moodle_url($returnurl2, array('what' => 'submittaskreport', 'taskid' => $taskid));

    

        $mform = new mrproject_submittask_form($meeting, $taskid, $actionurl, true);
        $mform->set_data($mform->prepare_booking_data($task));

    if (time() < $task->duedate) {    //Due date of the task
        
        if ($mform->is_cancelled()) {
            redirect($returnurl2);
        } else if ($formdata = $mform->get_data()) {
            $mform->save_booking_data($formdata, $task);

            //Event: task_reports_submitted
            \mod_mrproject\event\task_reports_submitted::create_from_meeting($meeting)->trigger();

            redirect($returnurl2);
        } else {
            echo $output->header();
            echo $output->heading(get_string('taskreport', 'mrproject'), 3);
            //echo $output->box(format_text($mrproject->intro, $mrproject->introformat));
            //$info = mrproject_task_info::make_from_meeting($meeting);
            $info = mrproject_task_info::make_from_task($meeting, $task);
            echo $output->render($info);
            $mform->display();
            echo $output->footer();
            exit();
        }
    } else {    //display notification

        if ($mform->is_cancelled()) {
            redirect($returnurl2);
        } else if ($formdata = $mform->get_data()) {
            $mform->save_booking_data($formdata, $task);

            //Event: task_reports_submitted
            \mod_mrproject\event\task_reports_submitted::create_from_meeting($meeting)->trigger();

            redirect($returnurl2);
        } else {
            echo $output->header();
            echo $output->heading(get_string('taskreport', 'mrproject'), 3);

            if ($task->submissiondate == 0) {   //Late submission
                //Notification
                $type = \core\output\notification::NOTIFY_ERROR;
                echo $output->notification(get_string('expiredtask', 'mrproject'), $type);
            }

            if ($task->evaluatedby != 0) {
              //Notification
              $type = \core\output\notification::NOTIFY_ERROR;
              echo $output->notification(get_string('evaluatedtask', 'mrproject'), $type);
            }

            //echo $output->box(format_text($mrproject->intro, $mrproject->introformat));
            //$info = mrproject_task_info::make_from_meeting($meeting);
            $info = mrproject_task_info::make_from_task($meeting, $task);
            echo $output->render($info);
            $mform->display();
            echo $output->footer();
            exit();
        }
    }

}




/******************************** Cancel a meeting (Upcoming meetings) ******************************/

if ($action == 'cancelbooking') {

    require_sesskey();
    //require_capability('mod/mrproject:appoint', $context);

    //Get the request parameters.
    $meetingid = required_param('meetingid', PARAM_INT);
    $groupid = required_param('groupid', PARAM_INT);

    
    //meeting
    $meeting = $mrproject->get_meeting($meetingid);

    
    /**************************** Notify the team members ******************************/
    
    include_once($CFG->dirroot.'/mod/mrproject/mailtemplatelib.php');

    //Who cancelled the meeting
    $teacher = $DB->get_record('user', array('id' => $USER->id));     

    //inform team members (Notification)
    //$teammembers = groups_get_members($groupid);


    //get participants
    $participants = array();
    foreach ($meeting->get_tasks() as $task) {
        if ($task->studentid != 0) {   //Student
            if ($task->collectivetask == null) {       //Individual task
                if (!in_array($task->studentid, $participants)) { 
                    array_push($participants, $task->studentid);
                }

            } else {        //Collective task
                $studentids = explode('+', $task->collectivetask);
                foreach ($studentids as $studid) {
                    if (!in_array(intval($studid), $participants)) { 
                        array_push($participants, intval($studid));
                    }
                }
            }
        
        } else {    //Teacher
            if (!in_array($meeting->teacherid, $participants)) {  
                array_push($participants, $meeting->teacherid);
            }
        }
    }


    //foreach ($teammembers as $member) {
    foreach ($participants as $participantid) {
        $student = $DB->get_record('user', array('id' => $participantid));

        //enable notifications for the user (user_preferences table)
        set_user_preferences([
            'message_provider_mod_mrproject_bookingnotification_loggedin' => 'popup,email',
            'message_provider_mod_mrproject_bookingnotification_loggedoff' => 'popup,email',
        ], $participantid);

        //Send notification
        mrproject_messenger::send_meeting_notification($meeting, 'bookingnotification', 'teachercancelled',
                            $teacher, $student, $teacher, $student, $COURSE, $groupid);   // 'teachercancelled' --> meeting cancelled,  'applied' --> meeting accepted
    }
    
    /******************************************************************************/

    //meetingaccepted = 0
    global $DB;
    $object = new stdClass();
    $object->id = $meetingid;
    $object->meetingaccepted = 0;             
    $object->meetingheld = 0;
    $object->starttime = 0;           
    $DB->update_record('mrproject_meeting', $object);

    
    //Event: meeting_canceled
    \mod_mrproject\event\meeting_canceled::create_from_meeting($meeting)->trigger();

    
    redirect($returnurl);

}

