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
 * Controller for all teacher-related views.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\dependency;


/**
 * Add a session (confirmed action) from data entered into the add session form
 * @param \mod_mrproject\model\mrproject $mrproject
 * @param mixed $formdata
 * @param moodle_url $returnurl the URL to redirect to after the action has been performed
 */
function mrproject_action_doaddsession($mrproject, $formdata, moodle_url $returnurl) {

    global $DB, $output;

    $data = (object) $formdata;

    $fordays = 0;
    if ($data->rangeend > 0) {
        $fordays = ($data->rangeend - $data->rangestart) / DAYSECS;
    }

    // Create as many meetings of $duration as will fit between $starttime and $endtime and that do not conflict.
    $countmeetings = 0;
    $couldnotcreatemeetings = '';
    $startfrom = $data->rangestart + ($data->starthour * 60 + $data->startminute) * 60;
    $endat = $data->rangestart + ($data->endhour * 60 + $data->endminute) * 60;
    $meeting = new stdClass();
    $meeting->mrprojectid = $mrproject->id;
    $meeting->teacherid = $data->teacherid;
    $meeting->tasklocation = $data->tasklocation;
    $meeting->meetingheld = $data->meetingheldenable ? $data->meetingheld : 0;
    if ($data->divide) {
        $meeting->duration = $data->duration;
    } else {
        $meeting->duration = $data->endhour * 60 + $data->endminute - $data->starthour * 60 - $data->startminute;
    };
    $meeting->meetingpurpose = '';
    $meeting->meetingpurposeformat = FORMAT_HTML;
    $meeting->timemodified = time();

    for ($d = 0; $d <= $fordays; $d ++) {
        $starttime = $startfrom + ($d * DAYSECS);
        $eventdate = usergetdate($starttime);
        $dayofweek = $eventdate['wday'];
        if ((($dayofweek == 1) && ($data->monday == 1)) ||
        (($dayofweek == 2) && ($data->tuesday == 1)) ||
        (($dayofweek == 3) && ($data->wednesday == 1)) ||
        (($dayofweek == 4) && ($data->thursday == 1)) ||
        (($dayofweek == 5) && ($data->friday == 1)) ||
        (($dayofweek == 6) && ($data->saturday == 1)) ||
        (($dayofweek == 0) && ($data->sunday == 1))) {
            $meeting->starttime = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'],
                                              $data->starthour, $data->startminute);
            $data->timestart = $meeting->starttime;
            $data->timeend = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'],
                                            $data->endhour, $data->endminute);

            // This corrects around midnight bug.
            if ($data->timestart > $data->timeend) {
                $data->timeend += DAYSECS;
            }
            if ($data->hideuntilrel == 0) {
                $meeting->hideuntil = time();
            } else {
                $meeting->hideuntil = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 6, 0) -
                                    $data->hideuntilrel;
            }
            if ($data->emaildaterel == -1) {
                $meeting->emaildate = 0;
            } else {
                $meeting->emaildate = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 0, 0) -
                                    $data->emaildaterel;
            }
            while ($meeting->starttime <= $data->timeend - $meeting->duration * 60) {
                $conflicts = $mrproject->get_conflicts($data->timestart, $data->timestart + $meeting->duration * 60,
                                                       $data->teacherid, 0, MRPROJECT_ALL);
                $resolvable = (boolean) $data->forcewhenoverlap;
                foreach ($conflicts as $conflict) {
                    $resolvable = $resolvable
                                     && $conflict->isself == 1       // Do not delete meetings outside the current mrproject.
                                     && $conflict->numstudents == 0; // Do not delete meetings with bookings.
                }

                if ($conflicts) {
                    $conflictmsg = '';
                    $cl = new mrproject_conflict_list();
                    $cl->add_conflicts($conflicts);
                    if (!$resolvable) {
                        $conflictmsg .= get_string('conflictingmeetings', 'mrproject', userdate($data->timestart));
                        $conflictmsg .= $output->doc_link('mod/mrproject/conflict', '', true);
                        $conflictmsg .= $output->render($cl);
                    } else { // We force, so delete all conflicting before inserting.
                        foreach ($conflicts as $conflict) {
                            $cmeeting = $mrproject->get_meeting($conflict->id);
                            \mod_mrproject\event\meeting_deleted::create_from_meeting($cmeeting, 'addsession-conflict')->trigger();
                            $cmeeting->delete();
                        }
                        $conflictmsg .= get_string('deletedconflictingmeetings', 'mrproject', userdate($data->timestart));
                        $conflictmsg .= $output->doc_link('mod/mrproject/conflict', '', true);
                        $conflictmsg .= $output->render($cl);
                    }
                    \core\notification::warning($conflictmsg);
                }
                if (!$conflicts || $resolvable) {
                    $meetingid = $DB->insert_record('mrproject_meeting', $meeting, true, true);
                    $meetingobj = $mrproject->get_meeting($meetingid);
                    \mod_mrproject\event\meeting_added::create_from_meeting($meetingobj)->trigger();
                    $countmeetings++;
                }
                $meeting->starttime += ($meeting->duration + $data->break) * 60;
                $data->timestart += ($meeting->duration + $data->break) * 60;
            }
        }
    }

    $messagetype = ($countmeetings > 0) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_INFO;
    $message = get_string('meetingsadded', 'mrproject', $countmeetings);
    \core\notification::add($message, $messagetype);

    redirect($returnurl);
}

/**
 * Send a message (confirmed action) after filling the message form
 *
 * @param \mod_mrproject\model\mrproject $mrproject
 * @param mixed $formdata
 * @param moodle_url $returnurl the URL to redirect to after the action has been performed
 */
function mrproject_action_dosendmessage($mrproject, $formdata, $returnurl) {

    global $DB, $USER;

    $data = (object) $formdata;

    $recipients = $data->recipient;
    if ($data->copytomyself) {
        $recipients[$USER->id] = 1;
    }
    $rawmessage = $data->body['text'];
    $format = $data->body['format'];
    $textmessage = format_text_email($rawmessage, $format);
    $htmlmessage = null;
    if ($format == FORMAT_HTML) {
        $htmlmessage = $rawmessage;
    }

    $cnt = 0;
    foreach ($recipients as $recipientid => $value) {
        if ($value) {
            $message = new \core\message\message();
            $message->component = 'mod_mrproject';
            $message->name = 'invitation';
            $message->userfrom = $USER;
            $message->userto = $recipientid;
            $message->subject = $data->subject;
            $message->fullmessage = $textmessage;
            $message->fullmessageformat = $format;
            if ($htmlmessage) {
                $message->fullmessagehtml = $htmlmessage;
            }
            $message->notification = '1';

            message_send($message);
            $cnt++;
        }
    }

    $message = get_string('messagesent', 'mrproject', $cnt);
    $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    redirect($returnurl, $message, 0, $messagetype);
}

/**
 * Delete meetings (after UI button has been pushed)
 *
 * @param \mod_mrproject\model\meeting[] $meetings list of meetings to be deleted
 * @param string $action description of the action
 * @param moodle_url $returnurl the URL to redirect to after the action has been performed
 */
function mrproject_action_delete_meetings(array $meetings, $action, moodle_url $returnurl) {

    $cnt = 0;
    foreach ($meetings as $meeting) {
        
        $meeting->delete_meeting();
        $cnt++;

        //Event: meeting_deleted
        \mod_mrproject\event\meeting_deleted::create_from_meeting($meeting, $action)->trigger();
    }

    if ($cnt == 1) {
        $message = get_string('onemeetingdeleted', 'mrproject');
    } else {
        $message = get_string('meetingsdeleted', 'mrproject', $cnt);
    }
    $messagetype = ($cnt > 0) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_INFO;
    \core\notification::add($message, $messagetype);
    redirect($returnurl);
}

// Require valid session key for all actions.
require_sesskey();

// We first have to check whether some action needs to be performed.
// Any of the following actions must issue a redirect when finished.
switch ($action) {
    /************************************ Deleting a meeting ***********************************************/
    case 'deletemeeting':
        $meetingid = required_param('meetingid', PARAM_INT);
        $meeting = $mrproject->get_meeting($meetingid);
        $permissions->ensure($permissions->can_edit_meeting($meeting));
        if ($meeting->meetingaccepted == 1) {    //logical deletion
            $meeting->isdeleted = 1;
            $meeting->save();
            redirect($viewurl);
            
        } else {     //physical deletion
            mrproject_action_delete_meetings(array($meeting), $action, $viewurl);
        }
        break;
    /************************************ Deleting multiple meetings ***********************************************/
    case 'deletemeetings':
        $meetingids = required_param('items', PARAM_SEQUENCE);
        $meetingids = explode(",", $meetingids);
        $meetings = array();
        foreach ($meetingids as $meetingid) {
            if ($meetingid > 0) {
                $meeting = $mrproject->get_meeting($meetingid);
                $permissions->ensure($permissions->can_edit_meeting($meeting));
                $meetings[] = $meeting;
            }
        }
        mrproject_action_delete_meetings($meetings, $action, $viewurl);
        break;
    /************************************ Students were seen ***************************************************/
    case 'saveseen':
        $meetingid = required_param('meetingid', PARAM_INT);
        $meeting = $mrproject->get_meeting($meetingid);
        $seen = optional_param_array('seen', array(), PARAM_INT);

        if (is_array($seen)) {
            foreach ($meeting->get_tasks() as $app) {
                $permissions->ensure($permissions->can_edit_attended($app));
                //$app->attended = (in_array($app->id, $seen)) ? 1 : 0;
                $app->timemodified = time();
            }
        }
        $meeting->save();
        redirect($viewurl);
        break;
    /************************************ Revoking all tasks to a meeting ***************************************/
    case 'revokeall':
        $meetingid = required_param('meetingid', PARAM_INT);
        $meeting = $mrproject->get_meeting($meetingid);
        $permissions->ensure($permissions->can_edit_meeting($meeting));

        $oldstudents = array();
        foreach ($meeting->get_tasks() as $app) {
            $oldstudents[] = $app->studentid;
            $meeting->remove_task($app);
        }
        // Notify the student.
        //if ($mrproject->allownotifications) {
            foreach ($oldstudents as $oldstudent) {
                include_once($CFG->dirroot.'/mod/mrproject/mailtemplatelib.php');

                $student = $DB->get_record('user', array('id' => $oldstudent));
                $teacher = $DB->get_record('user', array('id' => $meeting->teacherid));

                mrproject_messenger::send_meeting_notification($meeting, 'bookingnotification', 'teachercancelled',
                                        $teacher, $student, $teacher, $student, $COURSE);
            }
        //}

        $meeting->save();
        redirect($viewurl);
        break;

    /************************************ Toggling to unlimited group ***************************************/
    case 'allowgroup':
        $meetingid = required_param('meetingid', PARAM_INT);
        $meeting = $mrproject->get_meeting($meetingid);
        $permissions->ensure($permissions->can_edit_meeting($meeting));

        $meeting->meetingheld = 0;
        $meeting->save();
        redirect($viewurl);
        break;

    /************************************ Toggling to single student ******************************************/
    case 'forbidgroup':
        $meetingid = required_param('meetingid', PARAM_INT);
        $meeting = $mrproject->get_meeting($meetingid);
        $permissions->ensure($permissions->can_edit_meeting($meeting));

        $meeting->meetingheld = 1;
        $meeting->save();
        redirect($viewurl);
        break;

    /************************************ Deleting all meetings ***************************************************/
    case 'deleteall':
        $permissions->ensure($permissions->can_edit_all_meetings());
        $meetings = $mrproject->get_all_meetings();
        mrproject_action_delete_meetings($meetings, $action, $viewurl);
        break;
    /************************************ Deleting unused meetings *************************************************/
    case 'deleteunused':
        $permissions->ensure($permissions->can_edit_own_meetings());
        $meetings = $mrproject->get_meetings_without_task($USER->id);
        mrproject_action_delete_meetings($meetings, $action, $viewurl);
        break;
    /************************************ Deleting unused meetings (all teachers) ************************************/
    case 'deleteallunused':
        $permissions->ensure($permissions->can_edit_all_meetings());
        $meetings = $mrproject->get_meetings_without_task();
        mrproject_action_delete_meetings($meetings, $action, $viewurl);
        break;
    /************************************ Deleting current teacher's meetings ***************************************/
    case 'deleteonlymine':
        $permissions->ensure($permissions->can_edit_own_meetings());
        $meetings = $mrproject->get_meetings_for_teacher($USER->id);
        mrproject_action_delete_meetings($meetings, $action, $viewurl);
        break;
    /************************************ Mark as seen now *******************************************************/
    case 'markasseennow':
        $permissions->ensure($permissions->can_edit_own_meetings());

        $meeting = new stdClass();
        $meeting->mrprojectid = $mrproject->id;
        $meeting->teacherid = $USER->id;
        $meeting->starttime = time();
        $meeting->duration = $mrproject->defaultmeetingduration;
        $meeting->meetingheld = 1;
        $meeting->meetingpurpose = '';
        $meeting->meetingpurposeformat = FORMAT_HTML;
        $meeting->hideuntil = time();
        $meeting->tasklocation = '';
        $meeting->emaildate = 0;
        $meeting->timemodified = time();
        $meetingid = $DB->insert_record('mrproject_meeting', $meeting);

        $task = new stdClass();
        $task->meetingid = $meetingid;
        $task->studentid = required_param('studentid', PARAM_INT);
        //$task->attended = 1;
        $task->tasknote = '';
        $task->tasknoteformat = FORMAT_HTML;
        $task->teachernote = '';
        $task->teachernoteformat = FORMAT_HTML;
        //$task->timecreated = time();
        $task->timemodified = time();
        $DB->insert_record('mrproject_task', $task);

        $meeting = $mrproject->get_meeting($meetingid);
        \mod_mrproject\event\meeting_added::create_from_meeting($meeting)->trigger();

        redirect($viewurl);
        break;

    /***************************************** Recommend this dependency *************************************/

    //viewstudent: this task ----> teacher view
    case 'recommendthisdependency':
        require_sesskey();

        // Get the request parameters.
        $dependencyid = required_param('dependencyid', PARAM_INT);
        $taskid = required_param('taskid', PARAM_INT);
        $studentid = required_param('studentid', PARAM_INT);

        $returnurl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'viewstudent', 'taskid' => $taskid, 'studentid' => $studentid));


        global $DB;
        $object = new stdClass();
        $recommended_id = $DB->get_field('mrproject_recommendation', 'id', array('dependencyid' => $dependencyid, 'recommendedby' => $USER->id));
        $object->dependencyid = $dependencyid;   
        $object->recommendedby = $USER->id;         
        $object->timemodified = time();   

        if ($recommended_id) {   //cancel recommendation
            $transaction = $DB->start_delegated_transaction();
            $deleterecommendation = $DB->delete_records('mrproject_recommendation', ['id' => $recommended_id]);
            if ($deleterecommendation) {
                $DB->commit_delegated_transaction($transaction);
            }

        } else {       //Recommend this dependency
            try {
                $DB->insert_record('mrproject_recommendation', $object, false);
            } catch (dml_exception $e) {
                return false;
            }
        }


        //$dependency = $DB->get_record('mrproject_dependency', ['id' => $dependencyid]);
        //Event: dependency_recommended
        //\mod_mrproject\event\dependency_recommended::create_from_dependency($dependency)->trigger();

        redirect($returnurl);


        
    /***************************************** Recommend *************************************/

    //dependencies tab
    case 'recommend':
        require_sesskey();
        
        // Get the request parameters.
        $dependencyid = required_param('dependencyid', PARAM_INT);
        
        $returnurl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'deliverablespage', 'subpage' => 'dependenciessubtab'));
    
    
        global $DB;
        $object = new stdClass();
        $recommended_id = $DB->get_field('mrproject_recommendation', 'id', array('dependencyid' => $dependencyid, 'recommendedby' => $USER->id));
        $object->dependencyid = $dependencyid;   
        $object->recommendedby = $USER->id;         
        $object->timemodified = time();   
    
        if ($recommended_id) {   //cancel recommendation
            $transaction = $DB->start_delegated_transaction();
            $deleterecommendation = $DB->delete_records('mrproject_recommendation', ['id' => $recommended_id]);
            if ($deleterecommendation) {
                $DB->commit_delegated_transaction($transaction);
            }
    
        } else {       //Recommend this dependency
            try {
                $DB->insert_record('mrproject_recommendation', $object, false);
            } catch (dml_exception $e) {
                return false;
            }
        }
    
    
        //Event: dependency_recommended
        //\mod_mrproject\event\dependency_recommended::create_from_dependency($dependency)->trigger();
    
        redirect($returnurl);  

}

/*************************************************************************************************************/

