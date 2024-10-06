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
 * Appointment booking form of the mrproject module (using Moodle formslib)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\meeting;
use \mod_mrproject\model\task;

require_once($CFG->libdir.'/formslib.php');

/**
 * Student-side form to book or edit an task in a selected meeting
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mrproject_submittask_form extends moodleform {

    /** @var mixed */
    protected $meeting;
    /** @var mixed */
    protected $task = null;
    /** @var mixed */
    protected $uploadoptions;
    /** @var mixed */
    protected $existing;
    
    protected $noteoptions;

    /**
     * @var bool does this form have a duration field?
     */
    protected $hasduration = false;

    /**
     * @var int id of the task being edited
     */
    protected $taskid;


    /**
     * mrproject_booking_form constructor.
     *
     * @param meeting $meeting
     * @param mixed $action
     * @param bool $existing
     */
    public function __construct(meeting $meeting, $taskid, $action, $existing = false) {
        $this->meeting = $meeting;
        $this->existing = $existing;
        $this->taskid = $taskid;
        parent::__construct($action, null);
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
        $this->add_minutes_field('duration', 'consumedtime', 15, $minuteslabel);   //default meeting duration = 30
        $this->hasduration = true;
    }

    /**********************************************************************/


    /**
     * Form definition
     */
    protected function definition() {

        global $CFG, $output, $DB, $USER;

        $mform = $this->_form;
        $mrproject = $this->meeting->get_mrproject();


        $mform = $this->_form;
        /*$this->taskid = 0;
        if (isset($this->_customdata['taskid'])) {                 //the parameter '_customdata' allows you to pass extra data into a form.
            $this->taskid = $this->_customdata['taskid'];          //pass the data "$taskid" to the Form's class for use inside the definition.
        }*/
        


        $this->noteoptions = array('trusttext' => false, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $mrproject->get_context(),
                                   'collapsed' => true);

        $this->uploadoptions = array('subdirs' => 0,
                                     'maxbytes' => 41943040,    //40Mo = 41943040 Octets
                                     'maxfiles' => 4);



            //Completion rate (function 1)
            function emojiPercentBar($done,$total=100)
            {
                $green=html_entity_decode('&#x1F7E9;', 0, 'UTF-8');
                $white=html_entity_decode('&#x2B1C;', 0, 'UTF-8');

                $perc = round(($done * 100) / $total);
                $bar = round((10 * $perc) / 100);

                return sprintf("%s%s", str_repeat($green, $bar), str_repeat($white, 10-$bar));

            }

            //Completion rate (function 2)
            function progress_bar($done, $total, $info="", $width=30) {
                $perc = round(($done * 100) / $total);
                $bar = round(($width * $perc) / 100);
                return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
            }


        /***********************************************************************************/

            //File submissions (header).
            $mform->addElement('header', 'filesubmissions', get_string('filesubmissions', 'mrproject'));


            //Upload file 
            $mform->addElement('filemanager', 'studentfiles', get_string('uploadstudentfiles', 'mrproject'),
                                     null, $this->uploadoptions );



            //Completion rate element
            $choices = array();
            $choices['10'] = ' 10 % ' . emojiPercentBar(10, 100);
            $choices['20'] = ' 20 % ' . emojiPercentBar(20, 100);
            $choices['30'] = ' 30 % ' . emojiPercentBar(30, 100);
            $choices['40'] = ' 40 % ' . emojiPercentBar(40, 100);
            $choices['50'] = ' 50 % ' . emojiPercentBar(50, 100);
            $choices['60'] = ' 60 % ' . emojiPercentBar(60, 100);
            $choices['70'] = ' 70 % ' . emojiPercentBar(70, 100);
            $choices['80'] = ' 80 % ' . emojiPercentBar(80, 100);
            $choices['90'] = ' 90 % ' . emojiPercentBar(90, 100);
            $choices['100'] = '100 % ' . emojiPercentBar(100, 100);


            $mform->addElement('select', 'completionrate', get_string('completionrate', 'mrproject'), $choices);
            $mform->setDefault('completionrate', '50');




            //taskdependencies (header)
            $mform->addElement('header', 'taskdependencies', get_string('taskdependencies', 'mrproject'));
            $mform->addElement('html', '<fieldset class="clearfix">
                    <div class="fcontainer clearfix">
                    <div id="fitem_id_option_0" class="fitem fitem_fselect ">
                    ');
            $mform->setExpanded('taskdependencies');

            
            //Note
            $mform->addElement('static', 'note', '', get_string('dependencynote', 'mrproject'));


            //element to repeat
            $repeatarray = array();

            //Header ---> Dependency (1) 
            $repeatarray[] = $mform->createElement('header', 'appointhead', get_string('dependencyno', 'mrproject', '{no}'));
            

            //Dependency
            $repeatarray[] = $mform->createElement('text', 'dependency', get_string('dependency', 'mrproject'), array('size' => '90', 'maximumchars' => 255, 'maxlength' => 255, 'placeholder' => 'YouTube, ChatGPT, Wiki, GitHub, Gantt, Slack, Udemy, Documentation, Website, ...'));
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('dependency', PARAM_TEXT);
            } else {
                $mform->setType('dependency', PARAM_CLEANHTML);
            }
            


            //Link
            $repeatarray[] = $mform->createElement('text', 'link', get_string('link', 'mrproject'), array('size' => '90', 'maximumchars' => 255, 'maxlength' => 255, 'placeholder' => 'E.g.   https://chatgpt.com/'));
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('link', PARAM_TEXT);
            } else {
                $mform->setType('link', PARAM_CLEANHTML);
            }
            


            //Consumed time
            $grouparray = array();
            $grouparray[] = $mform->createElement('text', 'consumedtime', '', array('size' => 5, 'placeholder' => '30'));
            $grouparray[] = $mform->createElement('static', 'consumedtime'.'mintext', '', get_string('minutes', 'mrproject'));
            $mform->setType('consumedtime', PARAM_INT);
            

            $repeatarray[] = $mform->createElement('group', 'duration', get_string('consumedtime', 'mrproject'), $grouparray, null, false);
            


            //hidden (dependency id)
            $repeatarray[] = $mform->createElement('hidden', 'appointid', 0);
            //$repeatarray[] = $mform->createElement('static', 'appointid', 'appointid', 0);

            //Tickbox to remove a dependency.
            $repeatarray[] = $mform->createElement('advcheckbox', 'deletedependency', '', get_string('deletedependency', 'mrproject'));


        //-------------------------------------------------------
            //number of repetitions of the task form
            if ($this->taskid) {
                $repeatno = $DB->count_records('mrproject_dependency', array('taskid' => $this->taskid));
                if ($repeatno == 0) {
                    $repeatno = 1;
                }
            }



            //repeate Task form
            $repeateloptions = array();
            $repeateloptions['appointid']['type'] = PARAM_INT;
            $repeateloptions['appointhead']['expanded'] = true;

            // repeat elements
            $this->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                                'dependency_repeats', 'dependency_add', 1, 'Add another dependency');



            





        // Captcha.
        /*if ($mrproject->uses_bookingcaptcha() && !$this->existing) {
            $mform->addElement('recaptcha', 'bookingcaptcha', get_string('security_question', 'auth'), array('https' => true));
            $mform->addHelpButton('bookingcaptcha', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('bookingcaptcha');
        }*/

        //$submitlabel = $this->existing ? null : get_string('confirmbooking', 'mrproject');

        //Action buttons
        $this->add_action_buttons(true);
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

        /*if (!$this->existing && $this->meeting->get_mrproject()->uses_bookingcaptcha()) {
            $recaptcha = $this->_form->getElement('bookingcaptcha');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (true !== ($result = $recaptcha->verify($response))) {
                    $errors['bookingcaptcha'] = $result;
                }
            } else {
                $errors['bookingcaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }*/

        return $errors;
    }

    /**
     * prepare_booking_data
     *
     * @param task $task
     * @return stdClass
     */
    public function prepare_booking_data(task $task) {
        global $DB;
        $this->task = $task;

        $formdata = $task->get_data();
        $context = $task->get_mrproject()->get_context();

        //$formdata = file_prepare_standard_editor($formdata, 'studentnote', $this->noteoptions, $context);

        $draftitemid = file_get_submitted_draft_itemid('studentfiles');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_mrproject', 'studentfiles', $task->id);
        $formdata->studentfiles = $draftitemid;


        //dependencies
        $i = 0;
        $dependencies = $DB->get_records('mrproject_dependency', ['taskid' => $task->id]);   //Get a number of records as an array
        if ($dependencies) {
            foreach ($dependencies as $dependency) {
                $formdata->appointid[$i] = $dependency->id;
                $formdata->dependency[$i] = $dependency->dependency;
                $formdata->link[$i] = $dependency->link;
                $formdata->consumedtime[$i] = $dependency->consumedtime;

                $i++;
            }
        }

        return $formdata;
    }

    /**
     * save_booking_data
     *
     * @param stdClass $formdata
     * @param task $task
     */
    public function save_booking_data(stdClass $formdata, task $task) {
        global $DB;
        $mrproject = $task->get_mrproject();

        
        /*if ($mrproject->uses_studentnotes() && isset($formdata->studentnote_editor)) {
            $editor = $formdata->studentnote_editor;
            $task->studentnote = $editor['text'];
            $task->studentnoteformat = $editor['format'];
        }*/

        

        if ($task->evaluatedby == 0) {   //not yet graded
            //completion rate
            $task->completionrate = $formdata->completionrate;

            //submissiondate
            $task->submissiondate = time();

            

            //save the uploaded file
            if ($mrproject->uses_studentfiles()) {
                file_save_draft_area_files($formdata->studentfiles, $mrproject->context->id,
                                        'mod_mrproject', 'studentfiles', $task->id,
                                        $this->uploadoptions);
            }


            //Dependencies
            for ($i = 0; $i < $formdata->dependency_repeats; $i++) {
                if ($formdata->deletedependency[$i] != 0) {     //delete dependency
                    if ($formdata->appointid[$i]) {
                        $transaction = $DB->start_delegated_transaction();
                        $deletedrecord = $DB->delete_records('mrproject_dependency', ['id' => $formdata->appointid[$i]]);
                        if ($deletedrecord) {
                            $DB->commit_delegated_transaction($transaction);
                        }

                        //Event: dependency_deleted
                        \mod_mrproject\event\dependency_deleted::create_from_task($task)->trigger();
                    }
                } else if ($formdata->dependency[$i] != '' && $formdata->dependency[$i] != null) {
                    $app = null;
                    if ($formdata->appointid[$i]) {
                        $app = new stdClass();
                        $app->id = $formdata->appointid[$i];
                        $app->taskid = $task->id;
                        $app->dependency = $formdata->dependency[$i];
                        $app->link = $formdata->link[$i];
                        $app->consumedtime = $formdata->consumedtime[$i];
                        $app->timemodified = time();       //timemodified
                        $DB->update_record('mrproject_dependency', $app);

                        
                    } else {
                        $app = new stdClass();
                        $app->taskid = $task->id;
                        $app->dependency = $formdata->dependency[$i];
                        $app->link = $formdata->link[$i];
                        $app->consumedtime = $formdata->consumedtime[$i];
                        $app->timemodified = time();       //timemodified
                        try {
                            $DB->insert_record('mrproject_dependency', $app, false, true);
                        } catch (dml_exception $e) {
                            return false;
                        }

                        //Event: dependency_added
                        \mod_mrproject\event\dependency_added::create_from_task($task)->trigger();
                        
                    }
                }
            }

        
            //save task submission
            $task->savenewtask();
        }

    }

}
