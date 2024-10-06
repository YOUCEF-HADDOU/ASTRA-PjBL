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




/*********************************** Feedback form *****************************************/
/**
 * teacher-side form to book or edit an task in a selected meeting
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_feedback_form extends moodleform {

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
     * mrproject_feedback_form constructor.
     *
     * @param meeting $meeting
     * @param mixed $action
     * @param bool $existing
     */
    public function __construct(meeting $meeting, $action, $existing = false) {
        $this->meeting = $meeting;
        $this->existing = $existing;
        parent::__construct($action, null);
    }

    /**
     * Form definition
     */
    protected function definition() {

        global $CFG, $output;

        $mform = $this->_form;
        $mrproject = $this->meeting->get_mrproject();

        $this->noteoptions = array('trusttext' => false, 'maxfiles' => 0, 'maxbytes' => 0,
                                   'context' => $mrproject->get_context(),
                                   'collapsed' => true);


        $this->uploadoptions = array('subdirs' => 0,
                                     'maxbytes' => 41943040,    //40Mo = 41943040 Octets
                                     'maxfiles' => 4);

        // Text field for teacher-supplied data.
        //if ($mrproject->uses_teachernotes()) {

            //Feedback
            $mform->addElement('editor', 'feedbackbyteacher_editor', get_string('editteachernote', 'mrproject'),
                                array('rows' => 5, 'columns' => 60), $this->noteoptions);
            $mform->setType('feedbackbyteacher', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
            
            
            
            //Competencies
            /*$choices = array();
            $choices['0'] = get_string('meetingmode0', 'mrproject');
            $choices['1'] = get_string('meetingmode1', 'mrproject');
            $choices['2'] = get_string('meetingmode2', 'mrproject');
            $competencies = $mform->addElement('select', 'competency', get_string('meetingmode', 'mrproject'), $choices);
            //$competencies->setMultiple(true);
            $mform->setDefault('competency', '0');*/


            //Competency
            $mform->addElement('text', 'competency', get_string('competencies', 'mrproject'), array('size' => '90', 'placeholder' => 'Enter skills to be developed by students (Collaboration, Problem solving, Communication, ...)'));
            $mform->setType('competency', PARAM_TEXT);
            $mform->addRule('competency', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
            


            
            
            //$mform->addRule('teachernote_editor', get_string('notesrequired', 'mrproject'), 'required');
            
        //}

        // teacher file upload.
        /*if ($mrproject->uses_teacherfiles()) {
            $mform->addElement('filemanager', 'teacherfiles',
                    get_string('uploadteacherfiles', 'mrproject'),
                    null, $this->uploadoptions );
            if ($mrproject->requireupload) {
                $mform->addRule('teacherfiles', get_string('uploadrequired', 'mrproject'), 'required');
            }
        }*/

        // Captcha.
        /*if ($mrproject->uses_bookingcaptcha() && !$this->existing) {
            $mform->addElement('recaptcha', 'bookingcaptcha', get_string('security_question', 'auth'), array('https' => true));
            $mform->addHelpButton('bookingcaptcha', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('bookingcaptcha');
        }*/

        //$submitlabel = $this->existing ? null : get_string('confirmbooking', 'mrproject');
        //$this->add_action_buttons(true, $submitlabel);
        
        // action buttons.
        $this->add_action_buttons(true, 'Save feedback');
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
     * @param meeting $meeting
     * @return stdClass
     */
    public function prepare_booking_data(meeting $meeting) {
        $this->meeting = $meeting;

        $newdata = clone($meeting->get_data());
        $context = $meeting->get_mrproject()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'feedbackbyteacher', $this->noteoptions, $context);

        $draftitemid = file_get_submitted_draft_itemid('teacherfiles');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_mrproject', 'teacherfiles', $meeting->id);
        $newdata->teacherfiles = $draftitemid;

        $newdata->competency = $meeting->competency;

        return $newdata;
    }

    /**
     * save_booking_data
     *
     * @param stdClass $formdata
     * @param meeting $meeting
     */
    public function save_booking_data(stdClass $formdata, meeting $meeting) {
        $mrproject = $meeting->get_mrproject();
        if (isset($formdata->feedbackbyteacher_editor)) {
            $editor = $formdata->feedbackbyteacher_editor;
            $meeting->feedbackbyteacher = $editor['text'];             //Saved in 'teachernote' field in 'mrproject_task'
            $meeting->feedbackbyteacherformat = $editor['format'];
            
        }
        $meeting->competency = $formdata->competency;

        /*if ($mrproject->uses_teacherfiles()) {
            file_save_draft_area_files($formdata->teacherfiles, $mrproject->context->id,
                                       'mod_mrproject', 'teacherfiles', $task->id,
                                       $this->uploadoptions);
        }*/
        $meeting->save();
    }
}
