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
 * Meeting-related forms of the mrproject module (using Moodle formslib)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;

require_once($CFG->libdir.'/formslib.php');

/**
 * Base class for meeting-related forms
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mrproject_meetingform_base extends moodleform {

    /**
     * @var mrproject the mrproject that this form refers to
     */
    protected $mrproject;

    /**
     * @var array user groups to filter for
     */
    protected $usergroups;

    /**
     * @var bool does this form have a duration field?
     */
    protected $hasduration = false;

    /**
     * @var array options for note fields
     */
    protected $noteoptions;


    /**
     * Create a new form
     *
     * @param mixed $action the action attribute for the form
     * @param mrproject $mrproject
     * @param object $cm unused
     * @param array $usergroups groups to filter for
     * @param array $customdata
     */


    public function __construct($action, mrproject $mrproject, $cm, $usergroups, $customdata=null) {
        $this->mrproject = $mrproject;
        $this->usergroups = $usergroups;
        /*$this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $mrproject->get_context(), 'subdirs' => false);*/

        $this->noteoptions = array('trusttext' => false, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $mrproject->get_context(),
                                   'collapsed' => true);

        parent::__construct($action, $customdata);
    }



    /**
     * Add an input field for a number of minutes
     *
     * @param string $name field name
     * @param string $label language key for field label
     * @param int $defaultval default value
     * @param string $minuteslabel language key for suffix "minutes"
     */
    protected function add_minutes_field($name, $label, $defaultval, $minuteslabel = 'minutes') {
        $mform = $this->_form;
        $group = array();
        $group[] =& $mform->createElement('text', $name, '', array('size' => 5));
        $group[] =& $mform->createElement('static', $name.'mintext', '', get_string($minuteslabel, 'mrproject'));
        $mform->addGroup($group, $name.'group', get_string($label, 'mrproject'), array(' '), false);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $defaultval);
    }
    
    /**
     * Add theduration field to the form.
     * @param string $minuteslabel language key for the "minutes" label
     */
    protected function add_duration_field($minuteslabel = 'minutes') {
        $this->add_minutes_field('duration', 'duration', 30, $minuteslabel);  //default meeting duration = 30
        $this->hasduration = true;
    }




    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check duration for valid range.
        if ($this->hasduration) {
            $limits = array('min' => 1, 'max' => 24 * 60);
            if ($data['duration'] < $limits['min'] || $data['duration'] > $limits['max']) {
                $errors['durationgroup'] = get_string('durationrange', 'mrproject', $limits);
            }
        }

        return $errors;
    }

}



/***************************************** Add/Update meeting ********************************************/

/**
 * Add single meeting
 * Meeting edit form: When clicking to 'Add single meeting' or edit button
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_editmeeting_form extends mrproject_meetingform_base {

    /**
     * @var int id of the meeting being edited
     */
    protected $meetingid;

    

    /**
     * Form definition: Add a meeting
     */
    protected function definition() {

        global $DB, $output, $USER;

        $mform = $this->_form;
        $this->meetingid = 0;
        if (isset($this->_customdata['meetingid'])) {                 //the parameter '_customdata' allows you to pass extra data into a form.
            $this->meetingid = $this->_customdata['meetingid'];       //pass the data "$meetingid" to the Form's class for use inside the definition.
        }
        $timeoptions = null;
        if (isset($this->_customdata['timeoptions'])) {
            $timeoptions = $this->_customdata['timeoptions'];
        }

        
        
        //--------------------------------------------------------------------------------------------
        //Add a meeting
        
        //to hide elements (hideIf)
        $mform->addElement('hidden', 'hideelement', 0);
        $mform->setType('hideelement', PARAM_RAW);


        // Start date/time of the meeting.
        /*$mform->addElement('date_time_selector', 'starttime', get_string('date', 'mrproject'), $timeoptions);
        $mform->setDefault('starttime', time());
        $mform->disabledIf('starttime', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)   
        $mform->disabledIf('starttime', 'enrolperiod[enabled]', 'notchecked');*/



        // proposed date 1
        $mform->addElement('date_time_selector', 'proposeddate1', get_string('proposeddates', 'mrproject'), array('optional'  => false));
        $mform->disabledIf('proposeddate1', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)
        

        // proposed date 2
        $mform->addElement('date_time_selector', 'proposeddate2', '', array('optional'  => true));
        $mform->disabledIf('proposeddate2', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)


        // proposed date 3
        $mform->addElement('date_time_selector', 'proposeddate3', '', array('optional'  => true));
        $mform->disabledIf('proposeddate3', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)


        // Duration of the meeting.
        $this->add_duration_field();
        $mform->disabledIf('duration', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)


        // Meeting mode
        $choices = array();
        $choices['0'] = get_string('meetingmode0', 'mrproject');
        $choices['1'] = get_string('meetingmode1', 'mrproject');
        $choices['2'] = get_string('meetingmode2', 'mrproject');
        $mform->addElement('select', 'meetingmode', get_string('meetingmode', 'mrproject'), $choices);
        $mform->setDefault('meetingmode', '0');
        $mform->disabledIf('meetingmode', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)


        // Location of the task.
        $mform->addElement('text', 'tasklocation', get_string('location', 'mrproject'), array('size' => '30'));
        $mform->setType('tasklocation', PARAM_TEXT);
        $mform->addRule('tasklocation', get_string('error'), 'maxlength', 255);
        //$mform->setDefault('tasklocation', $this->mrproject->get_last_location($USER));   //function in classes/model/mrproject.php
        $mform->disabledIf('tasklocation', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)
        

        //Meeting with (Teachers list)
        $teachercaps = ['mod/mrproject:managealltasks'];
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context());
        $groupid = optional_param('groupid', null, PARAM_INT);  //required param when update & optional when add new meeting (when oppening the form --> received from renderer.php  &&  when saving the form --> received from mymeetingspage.php)

        $teachers = $this->mrproject->get_available_teachers($groupid);
        $teachersmenu = array();
        $teachersmenu[0] = get_string('students', 'mrproject');   //Student team
        if (!$isteacher) {   //for student
            $multiroles = null;
            
            //select
            foreach ($teachers as $teacher) {
                if ($teacher->id > 0) {
                    $multiroles = $DB->get_field('groups_members', 'multiroles', array('userid' => $teacher->id, 'groupid' => $groupid));
                }

                if ($multiroles != null && $multiroles != '') {
                    $teachersmenu[$teacher->id] = fullname($teacher). ' ('. $multiroles .')' ;
                } else {
                    $teachersmenu[$teacher->id] =  fullname($teacher). ' (Supervisor)';
                }
            }
        }

        /*$options = [];
        $options['multiple'] = optional_param('multiple', true, PARAM_BOOL);
        $options['teacherid']['disabledif'] = array('checkdatetime', 'eq', 1);*/
        
        $teacherselect = $mform->addElement('searchableselector', 'teacherid', get_string('meetingwith', 'mrproject'), $teachersmenu, ['multiple' => true]);
        //$teacherselect->removeOption ('');      //remove 'No selection' from the select
        //$mform->setDefault('teacherid', 0);    //set 'Student team' a default value
        $mform->disabledIf('teacherid', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)



        // Meeting purpose.
        $mform->addElement('editor', 'meetingpurpose_editor', get_string('meetingpurpose', 'mrproject'),
                            array('rows' => 5, 'columns' => 60), $this->noteoptions);
        $mform->setType('meetingpurpose', PARAM_RAW);    // Must be PARAM_RAW for rich text editor content.
        $mform->disabledIf('meetingpurpose_editor', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)


        /*if ($isteacher) {
            // Display meeting from this date.
            $mform->addElement('date_selector', 'hideuntil', get_string('displayfrom', 'mrproject'));
            $mform->setDefault('hideuntil', time());
            $mform->disabledIf('hideuntil', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)


            // Send e-mail reminder?
            $mform->addElement('date_selector', 'emaildate', get_string('emailreminderondate', 'mrproject'),
                                array('optional'  => true));
            $mform->disabledIf('emaildate', 'checkdatetime', 'eq', 1);    //if expired period (meeting held)
            $mform->setDefault('remindersel', -1);
        }*/


        //checkbox to Disable editing the meeting (expired meeting --> held)
        $mform->addElement('advcheckbox', 'checkdatetime', '', get_string('enable'));
        $mform->setDefault('checkdatetime', 0);
        $mform->hideIf('checkdatetime', 'hideelement', 'eq', '0');

        
        

        //---------------------------------------- Define Tasks ----------------------------------------------
        // Tasks (repeated form)

        //element to repeat
        $repeatarray = array();

        // element 1: Student
        $grouparray = array();
        // Choose student
        $students = $this->mrproject->get_available_students($this->usergroups);
        $studentchoices = array();
        if ($students) {
            foreach ($students as $astudent) {
                $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context(), $astudent->id);
                if (!$isteacher) {
                    $studentchoices[$astudent->id] = fullname($astudent);
                }
            }
        }
        $grouparray[] = $mform->createElement('searchableselector', 'studentid', '', $studentchoices);
        $grouparray[] = $mform->createElement('hidden', 'appointid', 0);


        //element to repeat
        $repeatarray[] = $mform->createElement('group', 'studgroup', '', $grouparray, null, false);
        

        //--------------------------------------------------------------------------------------------
        //number of repetitions of the task form
        if (isset($this->_customdata['repeats'])) {
            $repeatno = $this->_customdata['repeats'];
        } else if ($this->meetingid) {
            $repeatno = $DB->count_records('mrproject_task', array('meetingid' => $this->meetingid));

            //$repeatno -= 1;    //remove task for teacher

            //remove the task for teacher
            $teachercaps = ['mod/mrproject:managealltasks'];
            $meetwithteacher = null;
            $meeting = $this->mrproject->get_meeting($this->meetingid);
            foreach ($meeting->get_tasks() as $task) {
                if (has_any_capability($teachercaps, $meeting->get_mrproject()->get_context(), $task->studentid) || $task->studentid == 0) {
                    $meetwithteacher = true;
                    break;
                }
            }
            if ($meetwithteacher) {
                $repeatno -= 1;     
            }
        } else {
            $repeatno = 1;
        }

        
        //repeate Task form
        $repeateloptions = array();
        $repeateloptions['appointid']['type'] = PARAM_INT;
        $repeateloptions['studentid']['disabledif'] = array('appointid', 'neq', 0);
        $nostudcheck = array('studentid', 'eq', 0);
        

        //--------------------------------------------------------------------------------------------
        //Action buttons.
        $this->add_action_buttons();

        //--------------------------------------------------------------------------------------------
        // repeat elements
        $mform->addElement('html', '<start_div class="repeatelements">');
        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                            'task_repeats', 'task_add', 1, '');
        $mform->addElement('html', '<end_div>');

    }



    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $output;

        $errors = parent::validation($data, $files);

        // Avoid empty meetings starting in the past.
        /*if ($data['starttime'] < time()) {
            $errors['starttime'] = get_string('startpast', 'mrproject');
        }*/

        if ($data['proposeddate1'] < time()) {
            $errors['proposeddate1'] = get_string('startpast', 'mrproject');
        }

        //Pb
        /*if ($data['proposeddate2']['optional'] == false && $data['proposeddate2'] < time()) {
            $errors['proposeddate2'] = get_string('startpast', 'mrproject');
        }
        if ($data['proposeddate2']['optional'] == false && $data['proposeddate3'] < time()) {
            $errors['proposeddate3'] = get_string('startpast', 'mrproject');
        }*/
        

        return $errors;
    }


    /**
     * Fill the form data from an existing meeting
     *
     * @param meeting $meeting
     * @return stdClass form data
     */
    public function prepare_formdata(meeting $meeting) {

        $context = $meeting->get_mrproject()->get_context();

        $data = $meeting->get_data();
        //$data->meetingheldenable = ($data->meetingheld > 0);

        $data = file_prepare_standard_editor($data, "meetingpurpose", $this->noteoptions, $context,
                'mod_mrproject', 'meetingpurpose', $meeting->id);
        $data->meetingpurpose = array();
        $data->meetingpurpose['text'] = $meeting->meetingpurpose;
        $data->meetingpurpose['format'] = $meeting->meetingpurposeformat;

        //teacherid
        $teachercaps = ['mod/mrproject:managealltasks'];
        $meetwithteacher = null;
        $teacherid = array();
        foreach ($meeting->get_tasks() as $task) {
            if (has_any_capability($teachercaps, $context, $task->studentid)) {
                $meetwithteacher = true;
                array_push($teacherid, $task->studentid);
                //$teacherid = $task->studentid;
                //break;
            }
        }

        
        if ($meetwithteacher) {
            $data->teacherid = $teacherid;    //with a teacher
        } else {
            $data->teacherid = 0;    //between student
        }
        
        //foreach ($data->teacherid as $teacherid) {
        

        //checkdatetime (meeting held --> expired period)
        //if ($meeting->starttime + $meeting->duration * 60 < time()) { 
        if ($meeting->starttime != 0 && $meeting->starttime < time()) {       
            $data->checkdatetime = 1;
            
        }


        $i = 0;
        $teachercaps = ['mod/mrproject:managealltasks'];
        foreach ($meeting->get_tasks() as $task) {

            $isteacher = has_any_capability($teachercaps, $context, $task->studentid);
            if (!$isteacher) {
                $data->appointid[$i] = $task->id;
                $data->studentid[$i] = $task->studentid;

                $i++;
            }
        }

        return $data;
    }

    /**
     * Save a meeting object, updating it with data from the form
     * @param int $meetingid
     * @param mixed $data form data
     * @return meeting the updated meeting
     */
    public function save_meeting($meetingid, $data) {
        global $USER, $DB;

        $context = $this->mrproject->get_context();

        if ($meetingid) {
            $meeting = meeting::load_by_id($meetingid, $this->mrproject);
        } else {
            $meeting = new meeting($this->mrproject);
        }

        $teachercaps = ['mod/mrproject:managealltasks'];
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context());

    
    
        // Set data fields from input form.
        //$meeting->starttime = $data->starttime;
        if (isset($data->proposeddate1)) {
            $meeting->proposeddate1 = $data->proposeddate1;
        }
        if (isset($data->proposeddate2)) {
            $meeting->proposeddate2 = $data->proposeddate2;
        }
        if (isset($data->proposeddate3)) {
            $meeting->proposeddate3 = $data->proposeddate3;
        }
        $meeting->duration = $data->duration;
        $meeting->meetingmode = $data->meetingmode;
        $meeting->tasklocation = $data->tasklocation;
        
        $meeting->teacherid = $USER->id;            //the creator of the meeting
        
        
        $meeting->timemodified = time();

        if (!$meetingid) {
            $meeting->save();       // Make sure that a new meeting has a meeting id before proceeding.
        }

        //meeting purpose
        $editor = $data->meetingpurpose_editor;
        $meeting->meetingpurpose = file_save_draft_area_files($editor['itemid'], $context->id, 'mod_mrproject', 'meetingpurpose', $meetingid,
                $this->noteoptions, $editor['text']);
        $meeting->meetingpurposeformat = $editor['format'];


        //meetingpurpose
        /*if (isset($data->meetingpurpose_editor)) {
            $editor = $data->meetingpurpose_editor;
            $meeting->meetingpurpose = $editor['text'];             //Saved in 'teachernote' field in 'mrproject_task'
            $meeting->meetingpurposeformat = $editor['format'];
        }*/

        //Students: add tasks records
        $currentapps = $meeting->get_tasks();
        for ($i = 0; $i < $data->task_repeats; $i++) {

            if ($data->studentid[$i] > 0) {
                $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context(), $data->studentid[$i]);
                if (!$isteacher) {
                    $app = null;
                    if ($data->appointid[$i]) {
                        $app = $meeting->get_task($data->appointid[$i]);
                    } else {
                        $app = $meeting->create_task();
                        $app->studentid = $data->studentid[$i];   //selected student
                        $app->timemodified = time();         //timemodified
                        $app->savenewtask();
                    }
                }
            }
        }



        //add a record for teacher in mrproject_task table
        $teachercaps = ['mod/mrproject:managealltasks'];
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context());   //who create the meeting
        if ($isteacher) {   //teacher view

            $taskzeroexist = false;
            foreach ($meeting->get_tasks() as $task) {
                if ($task->studentid == 0) {
                    $taskzeroexist = true;
                }
            }

            if (!$taskzeroexist ) {
                $teacher = $meeting->create_task();
                $teacher->studentid = 0;                    //when a teacher create a meeting --> studentid = 0 (student team)
                $teacher->timemodified = time();            //timemodified
                $teacher->savenewtask();
            }
            
        } else {    //student view

            //delete all existing teacher tasks
            $teachercaps = ['mod/mrproject:managealltasks'];
            $taskteacherexist = false; 
            foreach ($meeting->get_tasks() as $task) {
                $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context(), $task->studentid);
                if ($isteacher) {
                    $transaction = $DB->start_delegated_transaction();
                    $deletedrecord = $DB->delete_records('mrproject_task', ['id' => $task->id]);
                    if ($deletedrecord) {
                        $DB->commit_delegated_transaction($transaction);
                    }
                }
            }

            //add tasks for selected teachers
            foreach ($data->teacherid as $teacherid) {     //$data->teacherid ---> array of selected teachers
                if (intval($teacherid) != 0) {    
                    $teacher = $meeting->create_task();
                    $teacher->studentid = $teacherid;         //when a student create a meeting
                    $teacher->timemodified = time();          //timemodified
                    $teacher->savenewtask();
                }
            }   
            
        }
        

        //save a meeting
        $meeting->save();
        $meeting = $this->mrproject->get_meeting($meeting->id);

    

        return $meeting;
    }
}

























/***************************************** Meeting report ********************************************/

/**
 * Add single meeting
 * Meeting edit form: When clicking to 'Add single meeting' or edit button
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_addmeetingreport_form extends mrproject_meetingform_base {

    /**
     * @var int id of the meeting being edited
     */
    protected $meetingid;

    /**
     * Form definition: Add single meeting
     */
    protected function definition() {

        global $DB, $output;

        $mform = $this->_form;
        $this->meetingid = 0;
        if (isset($this->_customdata['meetingid'])) {                 //the parameter '_customdata' allows you to pass extra data into a form.
            $this->meetingid = $this->_customdata['meetingid'];       //pass the data "$meetingid" to the Form's class for use inside the definition.
        }
        $timeoptions = null;
        if (isset($this->_customdata['timeoptions'])) {
            $timeoptions = $this->_customdata['timeoptions'];
        }


        //-------------------------------------Add a meeting report----------------------------------------
        
        //header
        $mform->addElement('header', 'meeting_title', get_string('meeting', 'mrproject'));  //header
        $mform->setExpanded('meeting_title');
        
        // Start date/time of the meeting.
        $mform->addElement('date_time_selector', 'starttime', get_string('date', 'mrproject'), $timeoptions);
        $mform->setDefault('starttime', time());


        // Duration of the meeting.
        $this->add_duration_field();


        // Meeting mode
        $choices = array();
        $choices['0'] = get_string('meetingmode0', 'mrproject');
        $choices['1'] = get_string('meetingmode1', 'mrproject');
        $choices['2'] = get_string('meetingmode2', 'mrproject');
        $mform->addElement('select', 'meetingmode', get_string('meetingmode', 'mrproject'), $choices);
        $mform->setDefault('meetingmode', '0');


        // Location of the task.
        $mform->addElement('text', 'tasklocation', get_string('location', 'mrproject'), array('size' => '30'));
        $mform->setType('tasklocation', PARAM_TEXT);
        $mform->addRule('tasklocation', get_string('error'), 'maxlength', 255);
        //$mform->setDefault('tasklocation', $this->mrproject->get_last_location($USER));   //function in classes/model/mrproject.php

        
        //Meeting with (Teachers list)
        /*$teachercaps = ['mod/mrproject:managealltasks'];
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context());
        //$groupid = required_param('groupid', 0, PARAM_INT);
        $groupid = optional_param('groupid', 0, PARAM_INT);
        $teachers = $this->mrproject->get_available_teachers($groupid);
        $teachersmenu = array();
        $teachersmenu[0] = get_string('students', 'mrproject');   //Student team
        if (!$isteacher) {
            foreach ($teachers as $teacher) {
                $teachersmenu[$teacher->id] = 'Expert: '.fullname($teacher);
            }
        }
        $mform->addElement('select', 'teacherid', get_string('meetingwith', 'mrproject'), $teachersmenu);
        $mform->setDefault('teacherid', 0);   //student team
        */


        //Meeting with
        $teachercaps = ['mod/mrproject:managealltasks']; 
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context(), $this->mrproject->get_meeting($this->meetingid)->teacherid);
        
        /*if ($isteacher) {   //meeting created by a teacher
            $teachername = fullname($this->mrproject->get_userbyid($this->mrproject->get_meeting($this->meetingid)->teacherid));   //get the name of teacherid in the meeting
        
        } else {     //meeting created by a student
            $meetwithteacher = null;
            $studentids = array();
            $meeting = $this->mrproject->get_meeting($this->meetingid);
            foreach ($meeting->get_tasks() as $task) {
                if (has_any_capability($teachercaps, $this->mrproject->get_context(), $task->studentid) && $task->studentid != 0) {
                    $meetwithteacher = true;
                    array_push($studentids, $task->studentid);
                    //$studentid = $task->studentid;
                    //break;
                }
            }
            //if ($meetwithteacher) {    //with a teacher
                $teachername = '';
                foreach ($studentids as $studentid) {
                    $teachername .= fullname($this->mrproject->get_userbyid($studentid)).'; ';    
                }
                $teachername .= get_string('students', 'mrproject').'. '; 
            /*} else {   //between students
                $teachername = get_string('students', 'mrproject'); 
            }*/
        //}

        //Meeting with
        //$mform->addElement('static', 'meetingwith', get_string('meetingwith', 'mrproject'), $teachername);



        // Meeting purpose.
        $mform->addElement('editor', 'meetingpurpose_editor', get_string('meetingpurpose', 'mrproject'),
                            array('rows' => 5, 'columns' => 60), $this->noteoptions);
        $mform->setType('meetingpurpose', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.



        //line (separation)
        //$mform->addElement('html', '<br/><hr style="height:1.5px;border-width:0;color:#0071c5;background-color:#0071c5">');



        


        //Attendees (participants)
        $mform->addElement('header', 'attendees_title', get_string('attendees', 'mrproject'));  //header
        $mform->setExpanded('attendees_title');


        $participants = array();
        $meeting = $this->mrproject->get_meeting($this->meetingid);
        $filterarray = array();
        foreach ($meeting->get_tasks() as $task) {
            if ($task->studentid != 0) {

                if ($task->collectivetask == null || $task->collectivetask == '0') {     //Individual task
                    //array_push($participants, $task->studentid);
                    if (!in_array($task->studentid, $filterarray)) {    //meet launched by a student
                        $participants[] = $mform->createElement('advcheckbox', 'attended['.$task->studentid.']', '', fullname($this->mrproject->get_userbyid($task->studentid)));
                        array_push($filterarray, $task->studentid);
                    }
                } else {     //collective task
                    $studentids = explode('+' ,$task->collectivetask);
                    foreach ($studentids as $student) {
                        if (!in_array($student, $filterarray)) {    //meet launched by a student
                            $participants[] = $mform->createElement('advcheckbox', 'attended['.$student.']', '', fullname($this->mrproject->get_userbyid($student)));
                            array_push($filterarray, $student);
                        }
                    }
                }

            } else {       //meet launched by a teacher
                if (!in_array($meeting->teacherid, $filterarray)) {     
                    $participants[] = $mform->createElement('advcheckbox', 'attended['.$meeting->teacherid.']', '', fullname($this->mrproject->get_userbyid($meeting->teacherid)));
                    array_push($filterarray, $task->studentid);
                }
            }

        }

        $attendeesarray = array();
        $attendeesarray[] = $mform->addElement('group', 'attendees', get_string('selectattendees', 'mrproject'), $participants, null, false);




        //Meeting outcomes (header)
        $mform->addElement('header', 'editor_title', get_string('meetingoutcomes', 'mrproject'));  //header
        $mform->setExpanded('editor_title');

        //Meeting outcomes (editor)
        $mform->addElement('editor', 'meetingoutcomes_editor', '',
                            array('rows' => 7, 'columns' => 60), $this->noteoptions);
        $mform->setType('meetingoutcomes', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.



        



        // Meeting outcomes.
       /* $mform->addElement('editor', 'meetingoutcomes_editor', get_string('meetingoutcomes', 'mrproject'),
                            array('rows' => 3, 'columns' => 60), $this->noteoptions);
        $mform->setType('meetingoutcomes', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        */
        

        


        //---------------------------------------- Define Tasks ----------------------------------------------
        // Tasks (repeated form)

        //header
        //$mform->addElement('header', 'tasks_section', get_string('definetasks', 'mrproject')); 
        
        //line
        /*$mform->addElement('html',
        '<hr style="height:1px;border-width:0;color:#0071c5;background-color:#0071c5">'
        );*/



        //Define tasks (header)
        $mform->addElement('header', 'tasks', get_string('definetasks', 'mrproject'));
        $mform->addElement('html', '<fieldset class="clearfix">
				<div class="fcontainer clearfix">
				<div id="fitem_id_option_0" class="fitem fitem_fselect ">
                ');

        $mform->setExpanded('tasks');


        //define tasks
        /*$mform->addElement('html',
        '<div>
            <h3 id="tasks" style="border-width:0;color:blue;font-weight: 400;">Define tasks </h3>
            <hr style="height:1px;border-width:0;color:#0071c5;background-color:#0071c5">
            
        </div>'
        );*/


        
        //element to repeat
        $repeatarray = array();

        // element 0: Header ---> Task 1 
        $repeatarray[] = $mform->createElement('header', 'appointhead', get_string('taskno', 'mrproject', '{no}'));
        


        // element 1: Student (group of elements)
        $grouparray = array();
        $students = $this->mrproject->get_available_students($this->usergroups);  //$this->usergroups = $currentgroup (see, collective delivrables.php)

        $studentchoices = array();
        $teachercaps = ['mod/mrproject:managealltasks'];
        if ($students) {
            foreach ($students as $astudent) {

                $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context(), $astudent->id);
                if (!$isteacher) {
                    $studentchoices[$astudent->id] = fullname($astudent);
                }
            }
        }

        $studentsselect = $mform->createElement('searchableselector', 'studentid', '', $studentchoices, ['multiple' => true]);
        //$studentsselect->removeOption ('');   //remove 'No selection' from the select
        $grouparray[] = $studentsselect;

        
        //$grouparray[] = $mform->createElement('searchableselector', 'studentid', '', $studentchoices);
        $grouparray[] = $mform->createElement('hidden', 'appointid', 0);


        $repeatarray[] = $mform->createElement('group', 'studgroup', get_string('student', 'mrproject'), $grouparray, null, false);
        


        // element 2: Task notes
        //if ($this->mrproject->uses_tasknotes()) {
            $repeatarray[] = $mform->createElement('editor', 'tasknote_editor', get_string('tasknote', 'mrproject'),
                                                   array('rows' => 5, 'columns' => 60), $this->noteoptions);
        //}
        

        // element 3: Starting date
        //$repeatarray[] = $mform->createElement('static', 'startingdatelabel', '', get_string('startingdate', 'mrproject'));
        $repeatarray[] = $mform->createElement('date_time_selector', 'startingdate', get_string('startingdate', 'mrproject'), $timeoptions);
        $mform->setDefault('startingdate', time());

        
        // element 4: Due date
        //$repeatarray[] = $mform->createElement('static', 'duedatelabel', '', get_string('duedate', 'mrproject'));
        $repeatarray[] = $mform->createElement('date_time_selector', 'duedate', get_string('duedate', 'mrproject'), $timeoptions);
        $mform->setDefault('duedate', time());



        // element 5: Tickbox to remove the student.
        $repeatarray[] = $mform->createElement('advcheckbox', 'deletestudent', '', get_string('deleteonsave', 'mrproject'));


        //--------------------------------------------------------------------------------------------
        //number of repetitions of the task form
        if (isset($this->_customdata['repeats'])) {
            $repeatno = $this->_customdata['repeats'];
        } else if ($this->meetingid) {
            $repeatno = $DB->count_records('mrproject_task', array('meetingid' => $this->meetingid));
            
            //remove the task for teacher
            $teachercaps = ['mod/mrproject:managealltasks'];
            $meetwithteacher = null;
            $meeting = $this->mrproject->get_meeting($this->meetingid);
            foreach ($meeting->get_tasks() as $task) {
                if (has_any_capability($teachercaps, $meeting->get_mrproject()->get_context(), $task->studentid) || $task->studentid == 0) {
                    $meetwithteacher = true;
                    $repeatno -= 1; 
                    //break;
                }
            }
            /*if ($meetwithteacher) {
                $repeatno -= 1;     
            }*/

        } else {
            $repeatno = 1;
        }

        
        //repeate Task form
        $repeateloptions = array();
        $repeateloptions['appointid']['type'] = PARAM_INT;
        $repeateloptions['studentid']['disabledif'] = array('appointid', 'neq', 0);
        $nostudcheck = array('studentid', 'eq', 0);
        $repeateloptions['tasknote_editor']['disabledif'] = $nostudcheck;
        $repeateloptions['deletestudent']['disabledif'] = $nostudcheck;
        $repeateloptions['appointhead']['expanded'] = true;

        
        // button ---> Add another student
        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                        'task_repeats', 'task_add', 1, get_string('addtask', 'mrproject'));

        
        //--------------------------------------------------------------------------------------------
        // action buttons.
        $this->add_action_buttons($cancel=true, $submitlabel='Save report');

    }




    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $output;

        $errors = parent::validation($data, $files);

        // Check number of tasks vs meetingheld.
        /*$numtasks = 0;
        for ($i = 0; $i < $data['task_repeats']; $i++) {
            if ($data['studentid'][$i] > 0 && $data['deletestudent'][$i] == 0) {
                $numtasks++;
            }
        }
        if ($data['meetingheldenable'] && $data['meetingheld'] <= 0) {
            $errors['meetingheldgroup'] = get_string('meetingheldpositive', 'mrproject');
        } else if ($data['meetingheldenable'] && $numtasks > $data['meetingheld']) {
            $errors['meetingheldgroup'] = get_string('meetingheldoverload', 'mrproject', $numtasks);
        }*/

        // Avoid empty meetings starting in the past.
        /*if ($data['starttime'] < time()) {
            $errors['starttime'] = get_string('startpast', 'mrproject');
        }*/

        // Check whether students have been selected several times.
        /*for ($i = 0; $i < $data['task_repeats']; $i++) {
            for ($j = 0; $j < $i; $j++) {
                if ($data['deletestudent'][$j] == 0 && $data['studentid'][$i] > 0
                        && $data['studentid'][$i] == $data['studentid'][$j]) {
                    $errors['studgroup['.$i.']'] = get_string('studentmultiselect', 'mrproject');
                    $errors['studgroup['.$j.']'] = get_string('studentmultiselect', 'mrproject');
                }
            }
        }*/

        /*if (!isset($data['ignoreconflicts'])) {
            // Avoid overlapping meetings by warning the user
            $conflicts = $this->mrproject->get_conflicts(
                            $data['starttime'], $data['starttime'] + $data['duration'] * 60,
                            $data['teacherid'], 0, MRPROJECT_ALL, $this->meetingid);

            if (count($conflicts) > 0) {

                $cl = new mrproject_conflict_list();
                $cl->add_conflicts($conflicts);

                $msg = get_string('meetingwarning', 'mrproject');
                $msg .= $output->render($cl);
                $msg .= $output->doc_link('mod/mrproject/conflict', '', true);

                $errors['starttime'] = $msg;
            }
        }*/
        return $errors;
    }


    /**
     * Fill the form data from an existing meeting
     *
     * @param meeting $meeting
     * @return stdClass form data
     */
    public function prepare_formdata(meeting $meeting) {

        $context = $meeting->get_mrproject()->get_context();

        $data = $meeting->get_data();
        //$data->meetingheldenable = ($data->meetingheld > 0);

        //meetingpurpose
        $data = file_prepare_standard_editor($data, "meetingpurpose", $this->noteoptions, $context,
                'mod_mrproject', 'meetingpurpose', $meeting->id);
        $data->meetingpurpose = array();
        $data->meetingpurpose['text'] = $meeting->meetingpurpose;
        $data->meetingpurpose['format'] = $meeting->meetingpurposeformat;


        //meetingoutcomes
        $data = file_prepare_standard_editor($data, "meetingoutcomes", $this->noteoptions, $context,
                'mod_mrproject', 'meetingoutcomes', $meeting->id);
        $data->meetingoutcomes = array();
        $data->meetingoutcomes['text'] = $meeting->meetingoutcomes;
        $data->meetingoutcomes['format'] = $meeting->meetingoutcomesformat;

        /*if ($meeting->emaildate < 0) {
            $data->emaildate = 0;
        }*/

        
        //Tasks
        $i = 0;
        $teachercaps = ['mod/mrproject:managealltasks'];
        //$filterarray = array();
        foreach ($meeting->get_tasks() as $task) {
            $isteacher = has_any_capability($teachercaps, $context, $task->studentid);
            if (!$isteacher && $task->studentid != 0 ) {    //&& !in_array($task->collectivetask, $filterarray)
                $data->appointid[$i] = $task->id;
                //$data->studentid[$i] = $task->studentid;

                $draftid = file_get_submitted_draft_itemid('tasknote');
                $currenttext = file_prepare_draft_area($draftid, $context->id,
                        'mod_mrproject', 'tasknote', $task->id,
                        $this->noteoptions, $task->tasknote);
                $data->tasknote_editor[$i] = array('text' => $currenttext,
                        'format' => $task->tasknoteformat,
                        'itemid' => $draftid);

                $data->startingdate[$i] = $task->startingdate;
                $data->duedate[$i] = $task->duedate;


                //selected students for this task
                if ($task->collectivetask != '0' && $task->collectivetask != null) {   //Collective task 

                    //$taskids = array();
                    $studentids = array();
                    $studentids = explode('+' ,$task->collectivetask);

                    //set data
                    $data->studentid[$i] = $studentids;    //array of student
  
                } else {   //Individual task
                    $data->studentid[$i] = $task->studentid;
                }

                $i++;
            }
        }



        //Attendees
        foreach ($meeting->get_tasks() as $task) {
            if ($task->studentid != 0) { 

                if ($task->collectivetask == null) {   //Individual task
                    $data->attended[$task->studentid] = 0;
                    if ($task->attended != null) {
                        $data->attended[$task->studentid] = 1;
                    }

                } else {   //Collective task
                    $studentids = explode('+', $task->collectivetask);
                    foreach ($studentids as $studid) {
                        $data->attended[$studid] = 0;
                        if (strpos($task->attended, '+'.$studid.'+') !== false) {   //!== false
                            $data->attended[$studid] = 1;
                        }
                    }
                }

            } else {     //meet launched by a teacher
                $data->attended[$meeting->teacherid] = 0;
                if ($task->attended != null) {
                    $data->attended[$meeting->teacherid] = 1; 
                }
            }
        }

        

        return $data;
    }



    /**
     * Save a meeting object, updating it with data from the form
     * @param int $meetingid
     * @param mixed $data form data
     * @return meeting the updated meeting
     */
    public function save_meeting($meetingid, $data) {

        $context = $this->mrproject->get_context();

        if ($meetingid) {
            $meeting = meeting::load_by_id($meetingid, $this->mrproject);
        } else {
            $meeting = new meeting($this->mrproject);
        }


        //Disable meeting report and display notification
        $disablereport = false;
        foreach ($meeting->get_tasks() as $task) {
            if ($task->evaluatedby != 0 && $task->submissiondate != 0) {
                $disablereport = true;
            }
        }

        if (!$disablereport) {       //evaluation has not yet started

            // Set data fields from input form.
            $meeting->starttime = $data->starttime;
            $meeting->duration = $data->duration;
            $meeting->meetingheld = 1;              //meeting held
            $meeting->tasklocation = $data->tasklocation;
            $meeting->meetingmode = $data->meetingmode;
            $meeting->timemodified = time();

            if (!$meetingid) {
                $meeting->save(); // Make sure that a new meeting has a meeting id before proceeding.
            }

            //meetingpurpose
            $editor = $data->meetingpurpose_editor;
            $meeting->meetingpurpose = file_save_draft_area_files($editor['itemid'], $context->id, 'mod_mrproject', 'meetingpurpose', $meetingid,
                    $this->noteoptions, $editor['text']);
            $meeting->meetingpurposeformat = $editor['format'];

            //meetingpurpose
            /*if (isset($data->meetingpurpose_editor)) {
                $editor = $data->meetingpurpose_editor;
                $meeting->meetingpurpose = $editor['text'];             //Saved in 'teachernote' field in 'mrproject_task'
                $meeting->meetingpurposeformat = $editor['format'];
                
            }*/

            //meetingoutcomes
            $editor = $data->meetingoutcomes_editor;
            $meeting->meetingoutcomes = file_save_draft_area_files($editor['itemid'], $context->id, 'mod_mrproject', 'meetingoutcomes', $meetingid,
                    $this->noteoptions, $editor['text']);
            $meeting->meetingoutcomesformat = $editor['format'];


            //meetingoutcomes
            /*if (isset($data->meetingoutcomes_editor)) {
                $editor = $data->meetingoutcomes_editor;
                $meeting->meetingoutcomes = $editor['text'];             //Saved in 'teachernote' field in 'mrproject_task'
                $meeting->meetingoutcomesformat = $editor['format'];  
            }*/


            


            //Tasks
            $currentapps = $meeting->get_tasks();
            for ($i = 0; $i < $data->task_repeats; $i++) {
                if ($data->deletestudent[$i] != 0) {     //delete task
                    if ($data->appointid[$i]) {
                        $app = $meeting->get_task($data->appointid[$i]);
                        $meeting->remove_task($app);

                        //Event: task_deleted
                        \mod_mrproject\event\task_deleted::create_from_task($app)->trigger();

                    }
                } else {
                    $studentids = array();
                    $nb = 0;
                    $createdtaskid = null;
                    foreach ($data->studentid[$i] as $studentid) {   //assigned students
                        $app = null;
                        
                        if ($data->appointid[$i]) {       //appointid = task id
                            //update an existing task
                            $app = $meeting->get_task($data->appointid[$i]);

                            //collective task (assign it for another student)
                            /*if ($studentid != $app->studentid) {
                                $app = $meeting->create_task();
                                $app->studentid = $studentid;   //selected student
                                $app->savenewtask();

                                //Event: task_added
                                \mod_mrproject\event\task_added::create_from_task($app)->trigger();
                            }*/

                        } else {
                            
                            if ($createdtaskid == null) {        //for the first student
                                $app = $meeting->create_task();
                                $app->studentid = $studentid;    //selected student
                                $app->savenewtask();
                                $createdtaskid = $app->id;

                            } else {     //collective task, update the created task
                                $app = $meeting->get_task($createdtaskid);
                            }
                            

                            //Event: task_added
                            \mod_mrproject\event\task_added::create_from_task($app)->trigger();
                        }
                    


                        //selected student
                        $app->studentid = $data->studentid[$i][0];   //the first student

                        //starting date
                        $app->startingdate = $data->startingdate[$i];

                        //due date
                        $app->duedate = $data->duedate[$i];

                        //tasknote
                        if ($this->mrproject->uses_tasknotes()) {
                            $editor = $data->tasknote_editor[$i];
                            $app->tasknote = file_save_draft_area_files($editor['itemid'], $context->id,
                                    'mod_mrproject', 'tasknote', $app->id,
                                    $this->noteoptions, $editor['text']);
                            $app->tasknoteformat = $editor['format'];
                        }
                        //timemodified
                        $app->timemodified = time(); 
                        


                        //collectivetask (put task ids for selected students in an array)
                        array_push($studentids, $studentid);

                        $nb++;


                        //tasknote
                        /*if (isset($data->tasknote_editor[$i])) {
                            $editor = $data->tasknote_editor[$i];
                            $meeting->tasknote = $editor['text'];             
                            $meeting->tasknoteformat = $editor['format']; 
                        }*/

                    }

                    //collectivetask (save an array as string)
                    if ($nb > 1 ) {
                        //$app->collectivetask = implode("+", $taskids);
                        $app->collectivetask = implode("+", $studentids);     //'6+7'  ----> student id 6 and 7
                        //$app->studentid = 0;
                    } else {
                        $app->collectivetask = null;
                    }
                }
            }



            //Attendees
            foreach ($meeting->get_tasks() as $task) {
                if ($task->studentid != 0) {     //meet launched by a student

                    if ($task->collectivetask == null) {     //Individual task
                        $task->attended = null;  
                        if ($data->attended[$task->studentid]) {
                            $task->attended = $task->studentid;       
                        }
                    } else {       //Collective task
                        $studentids = explode('+' ,$task->collectivetask);
                        $task->attended = null; 
                        foreach ($studentids as $studid) {
                            if ($data->attended[$studid]) {
                                $task->attended = $task->attended .'+'. $studid .'+';       
                            }
                        }
                    }
                } else {   //meet launched by a teacher
                    if ($data->attended[$meeting->teacherid]) {
                        $task->attended = $meeting->teacherid;       
                    } else {
                        $task->attended = null;       
                    }
                }
            }


            //save meeting report
            $meeting->save();
            $meeting = $this->mrproject->get_meeting($meeting->id);
        }
        

        return $meeting;
    }
}






/*********************************** Edit responsibilities **********************************************/

/**
 * Edit responsibilities
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_editresponsibilities_form extends mrproject_meetingform_base {

    /**
     * @var int id of the meeting being edited
     */
    protected $meetingid;

    

    /**
     * Form definition: Add a meeting
     */
    protected function definition() {

        global $DB, $output, $USER;

        $mform = $this->_form;

        //---------------------------------------- Define responsibilities ----------------------------------------------
        //repeated form

        //element to repeat
        $repeatarray = array();

        //hidden: studentid
        $repeatarray[] = $mform->createElement('hidden', 'studentid', '', '');
        $mform->setType('studentid', PARAM_RAW);


        //element 1: Student fullname
        $repeatarray[] = $mform->createElement('header', 'studentname', '');


        //element 2: responsibility
        $options = [];
        //options for 'autocomplete element'
        $options['tags'] = optional_param('tags',false,PARAM_BOOL);
        $options['placeholder'] = optional_param('placeholder','',PARAM_TEXT);
        $options['casesensitive'] = optional_param('casesensitive','',PARAM_TEXT);
        $options['noselectionstring'] = optional_param('noselectionstring','',PARAM_TEXT);
        $options['showsuggestions'] = optional_param('showsuggestions',true,PARAM_BOOL);
        $options['default'] = '0';
        //$options['multiple'] = optional_param('multiple', true, PARAM_BOOL);

        //data
        $data = [' '=>' ', 'Project leader'=>'Project leader', 'Project Manager'=>'Project Manager', 'Project Coordinator'=>'Project Coordinator', 
                'Sponsor'=>'Sponsor','Project Analyst'=>'Project Analyst', 'Project Scheduler'=>'Project Scheduler', 'Stakeholder'=>'Stakeholder', 
                'Team Leader'=>'Team Leader', 'Client'=>'Client', 'Project planning'=>'Project planning', 'Resource manager'=>'Resource manager', 
                'Budget control'=>'Budget control', 'Meeting manager'=>'Meeting manager', 'Documentation and reporting'=>'Documentation and reporting', 
                'Risk analysis and management'=>'Risk analysis and management', 'Quality manager'=>'Quality manager', 'Ordinary member'=>'Ordinary member', 
                'Project designer'=>'Project designer', 'System architect'=>'System architect', 'Test manager'=>'Test manager', 'Scrum master'=>'Scrum master', 
                'Software developer'=>'Software developer', 'Web developer'=>'Web developer', 'Back-end developer'=>'Back-end developer', 'Front-end developer'=>'Front-end developer', 
                'Full-stack developer'=>'Full-stack developer', 'Security developer'=>'Security developer', 'Mobile developer'=>'Mobile developer', 'CRM developer'=>'CRM developer', 
                'UX designer'=>'UX designer', 'UI designer'=>'UI designer', 'Data analyst'=>'Data analyst', 'Data scientist'=>'Data scientist', 
                'Database administrator'=>'Database administrator', 'Operating system manager'=>'Operating system manager', 'AI Engineer'=>'AI Engineer', 'BI Engineer'=>'BI Engineer', 
                'Machine learning engineer'=>'Machine learning engineer', 'Research scientist'=>'Research scientist', 'Analytics Manager'=>'Analytics Manager', 'Integrator'=>'Integrator' ]; 

        //sort an array in ascending order
        asort($data);

        //add the element
        $repeatarray[] = $mform->createElement('autocomplete', 'responsibility', get_string('responsibility', 'mrproject'), $data, $options);
        


        //element 2: responsibility 
        /*$repeatarray[] = $mform->createElement('text', 'responsibility', get_string('responsibility', 'mrproject'), array('size' => '30'));
        $mform->setType('responsibility', PARAM_TEXT);*/


        //--------------------------------------------------------------------------------------------
        //number of repetitions of the task form
        if (isset($this->_customdata['repeats'])) {
            $repeatno = $this->_customdata['repeats'];
        } else if ($this->meetingid) {
            $repeatno = $DB->count_records('mrproject_task', array('meetingid' => $this->meetingid));

            //$repeatno -= 1;    //remove task for teacher

            //remove the task for teacher
            $teachercaps = ['mod/mrproject:managealltasks'];
            $meetwithteacher = null;
            $meeting = $this->mrproject->get_meeting($this->meetingid);
            foreach ($meeting->get_tasks() as $task) {
                if (has_any_capability($teachercaps, $meeting->get_mrproject()->get_context(), $task->studentid) || $task->studentid == 0) {
                    $meetwithteacher = true;
                    break;
                }
            }
            if ($meetwithteacher) {
                $repeatno -= 1;     
            }

        } else {
            $repeatno = 1;
        }

        
        //repeate form
        $repeateloptions = array();
        

        //--------------------------------------------------------------------------------------------
        // repeat elements
        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                            'task_repeats', 'task_add', 1, '↓');

        //--------------------------------------------------------------------------------------------
        //Action buttons.
        $this->add_action_buttons();

    }



    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $output;
        $errors = parent::validation($data, $files);


        return $errors;
    }


    /**
     * Fill the form data from an existing meeting
     *
     * @param meeting $meeting
     * @return stdClass form data
     */
    public function prepare_formdata(meeting $meeting) {
        
        $context = $meeting->get_mrproject()->get_context();
        $data = $meeting->get_data();


        return $data;
    }

    /**
     * Save a meeting object, updating it with data from the form
     * @param int $meetingid
     * @param mixed $data form data
     * @return meeting the updated meeting
     */
    public function save_responsibilities($groupid, $data) {
        global $USER, $DB;
        $context = $this->mrproject->get_context();


        $teachercaps = ['mod/mrproject:managealltasks'];
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context());

        //Save responsibilities
        for ($i = 0; $i < $data->task_repeats; $i++) {
            if ($data->studentid[$i] > 0) {
                if (isset($data->responsibility[$i])) {
                    if ( $data->responsibility[$i] != ' ' && $data->responsibility[$i] != null && $data->responsibility[$i] != '' ) {
                        $DB->set_field('groups_members', 'multiroles', $data->responsibility[$i], array('userid' => $data->studentid[$i], 'groupid' => $groupid));
                
                    } else {
                        $DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->studentid[$i], 'groupid' => $groupid));
                    }
                } else {
                    $DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->studentid[$i], 'groupid' => $groupid));
                    
                }
            }
        }

        //Event: student_responsibilities_modified
        \mod_mrproject\event\student_responsibilities_modified::create_from_mrproject($this->mrproject)->trigger();

        return true;
    }
}





/*********************************** Edit teachers roles **********************************************/

/**
 * Edit teachers roles 
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_editroles_form extends mrproject_meetingform_base {

    /**
     * @var int id of the meeting being edited
     */
    protected $meetingid;

    

    /**
     * Form definition: Add a meeting
     */
    protected function definition() {

        global $DB, $output, $USER;

        $mform = $this->_form;

        //---------------------------------------- Define roles ----------------------------------------------


        //Teachers list
        $teachercaps = ['mod/mrproject:managealltasks'];
        $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context());
        $groupid = required_param('groupid', PARAM_INT);  //required param 

        $teachers = $this->mrproject->get_available_teachers($groupid);
        $teachersmenu = array();
        foreach ($teachers as $teacher) {
            if ($teacher->id > 0) {
                $teachersmenu[$teacher->id] = fullname($teacher);
            }
        }

        //element 1: Tutor
        $mform->addElement('header', 'tutorheader', 'Tutor');
        $mform->addElement('select', 'tutor', get_string('assignrole', 'mrproject'), $teachersmenu);
        $mform->setExpanded('tutorheader');

        //element 2: Expert
        $mform->addElement('header', 'expertheader', 'Expert');
        $mform->addElement('select', 'expert', get_string('assignrole', 'mrproject'), $teachersmenu);
        $mform->setExpanded('expertheader');

        //element 3: Client
        $mform->addElement('header', 'clientheader', 'Client');
        $mform->addElement('select', 'client', get_string('assignrole', 'mrproject'), $teachersmenu);
        $mform->setExpanded('clientheader');


        //--------------------------------------------------------------------------------------------
        //Action buttons.
        $this->add_action_buttons();

    }



    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $output;
        $errors = parent::validation($data, $files);


        return $errors;
    }


    /**
     * Fill the form data from an existing meeting
     *
     * @param meeting $meeting
     * @return stdClass form data
     */
    public function prepare_formdata(meeting $meeting) {

        $context = $meeting->get_mrproject()->get_context();
        $data = $meeting->get_data();

        /*$data = new stdClass();
        $data->tutor = 1;

        $data->expert = 3;
        
        $data->client = 3;*/

        return $data;
    }

    /**
     * Save a meeting object, updating it with data from the form
     * @param int $meetingid
     * @param mixed $data form data
     * @return meeting the updated meeting
     */
    public function save_roles($groupid, $data) {
        global $USER, $DB;
        $context = $this->mrproject->get_context();


        $teachercaps = ['mod/mrproject:managealltasks'];
        

        //reset roles to empty
        $members = groups_get_members($groupid);
        foreach ($members as $member) {
            $isteacher = has_any_capability($teachercaps, $this->mrproject->get_context(), $member->id);
            if ($isteacher) {
                $DB->set_field('groups_members', 'multiroles', '', array('userid' => $member->id, 'groupid' => $groupid));
            }
        }

        //teacher with 3 roles
        if ($data->tutor == $data->expert && $data->expert == $data->client) {
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->tutor, 'groupid' => $groupid));   
            $DB->set_field('groups_members', 'multiroles', 'Tutor, Expert, Client', array('userid' => $data->tutor, 'groupid' => $groupid));
        }
        //teacher with 2 roles 
        elseif ($data->tutor == $data->expert && $data->expert != $data->client) {
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->tutor, 'groupid' => $groupid));
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->client, 'groupid' => $groupid));
            $DB->set_field('groups_members', 'multiroles', 'Tutor, Expert', array('userid' => $data->tutor, 'groupid' => $groupid));
            $DB->set_field('groups_members', 'multiroles', 'Client', array('userid' => $data->client, 'groupid' => $groupid));
        }
        elseif ($data->tutor == $data->client && $data->client != $data->expert) {
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->tutor, 'groupid' => $groupid));
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->expert, 'groupid' => $groupid));
            $DB->set_field('groups_members', 'multiroles', 'Tutor, Client', array('userid' => $data->tutor, 'groupid' => $groupid));
            $DB->set_field('groups_members', 'multiroles', 'Expert', array('userid' => $data->expert, 'groupid' => $groupid));
        } 
        elseif ($data->expert == $data->client && $data->client != $data->tutor) {
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->expert, 'groupid' => $groupid));
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->tutor, 'groupid' => $groupid));
            $DB->set_field('groups_members', 'multiroles', 'Expert, Client', array('userid' => $data->expert, 'groupid' => $groupid));
            $DB->set_field('groups_members', 'multiroles', 'Tutor', array('userid' => $data->tutor, 'groupid' => $groupid));
        } 
        //teacher with 1 role 
        elseif ($data->tutor != $data->expert && $data->tutor != $data->client && $data->expert != $data->client) {
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->tutor, 'groupid' => $groupid));
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->expert, 'groupid' => $groupid));
            //$DB->set_field('groups_members', 'multiroles', '', array('userid' => $data->client, 'groupid' => $groupid));
            //Tutor
            $DB->set_field('groups_members', 'multiroles', 'Tutor', array('userid' => $data->tutor, 'groupid' => $groupid));
            //Expert
            $DB->set_field('groups_members', 'multiroles', 'Expert', array('userid' => $data->expert, 'groupid' => $groupid));
            //Client
            $DB->set_field('groups_members', 'multiroles', 'Client', array('userid' => $data->client, 'groupid' => $groupid));
        }


        //Event: supervisor_roles_modified
        \mod_mrproject\event\supervisor_roles_modified::create_from_mrproject($this->mrproject)->trigger();

        return true;
    }
}