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
 * Form to edit one task: seen, grade, notes, ...
 * Appointment-related forms of the mrproject module (using Moodle formslib)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\task;
use \mod_mrproject\permission\mrproject_permissions;

require_once($CFG->libdir.'/formslib.php');


class mrproject_edittask_form extends moodleform {

    /**
     * @var task the task being edited
     */
    protected $task;

    /**
     * @var bool whether to distribute grade to all group members
     */
    protected $distribute;

    /**
     * @var array permissions of the student
     */
    protected $permissions;

    /**
     * @var array options for notes fields
     */
    public $noteoptions;

    /**
     * Create a new edit task form
     *
     * @param task $task the task to edit
     * @param mixed $action the action attribute for the form
     * @param mrproject_permissions $permissions
     * @param bool $distribute whether to distribute grades to all group members
     */
    public function __construct(task $task, $action, mrproject_permissions $permissions, $distribute) {
        $this->task = $task;
        $this->distribute = $distribute;
        $this->permissions = $permissions;
        /*$this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $permissions->get_context(),
                                   'subdirs' => false, 'collapsed' => true);*/

        $this->noteoptions = array('trusttext' => false, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $permissions->get_context(),
                                   'collapsed' => true);
        parent::__construct($action, null);
    }

    /**
     * Form definition
     */
    protected function definition() {

        global $output;

        $mform = $this->_form;
        $mrproject = $this->task->get_mrproject();

        $candistribute = false;

        // Seen tickbox.
        /*$mform->addElement('checkbox', 'attended', get_string('attended', 'mrproject'));
        if (!$this->permissions->can_edit_attended($this->task)) {
            $mform->freeze('attended');
        }*/



        // Grade.
        if ($mrproject->uses_grades()) {
            if ($this->permissions->can_edit_grade($this->task)) {
                $gradechoices = $output->grading_choices($mrproject);
                $mform->addElement('select', 'grade', get_string('grade', 'mrproject'), $gradechoices);
                $candistribute = true;
            } else {
                $gradetext = $output->format_grade($mrproject, $this->task->grade);
                $mform->addElement('static', 'gradedisplay', get_string('grade', 'mrproject'), $gradetext);
            }
        }


        // student note.
        if ($this->permissions->can_edit_notes($this->task)) {
            $mform->addElement('editor', 'studentnote_editor', get_string('studentnote', 'mrproject'),
                               array('rows' => 3, 'columns' => 60), $this->noteoptions);     //'studentnote_editor' or 'teachernote_editor'
            $mform->setType('studentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
            //$candistribute = true;
            //$mform->addRule('studentnote_editor', get_string('notesrequired', 'mrproject'), 'required');

            //--------------
            /*$mform->addElement('editor', 'teachernote_editor', get_string('studentnote', 'mrproject'),
                                array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('studentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
            $mform->addRule('teachernote_editor', get_string('notesrequired', 'mrproject'), 'required');*/

        } else {
            $note = $output->format_notes($this->task->studentnote, $this->task->studentnoteformat,
                                          $mrproject->get_context(), 'studentnote', $this->task->id);
            $mform->addElement('static', 'studentnote_display', get_string('studentnote', 'mrproject'), $note);
        }

        

        // Checkbox: collective evaluation to student team
        /*if ($this->distribute && $candistribute) {
            $mform->addElement('checkbox', 'distribute', get_string('distributedgrade', 'mrproject'));
            $mform->setDefault('distribute', false);
        }*/
        
        
        // Action buttons
        $this->add_action_buttons();

        
        //display grade
        //$gradetext = $output->format_grade($mrproject, $this->task->grade);
        //$mform->addElement('static', 'gradedisplay', get_string('gradeingradebook', 'mrproject'), '<strong>'.$this->task->grade.'</strong>');
    
    }



    /**
     * Form validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    /**
     * Prepare form data from an task record
     *
     * @param task $task task to edit
     * @return stdClass form data
     */
    public function prepare_task_data(task $task) {
        $newdata = clone($task->get_data());
        $context = $this->task->get_mrproject()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'studentnote', $this->noteoptions, $context,
                                                'mod_mrproject', 'studentnote', $this->task->id);


        return $newdata;
    }

    /**
     * Save form data into task record
     *
     * @param stdClass $formdata data extracted from form
     * @param task $task task to update
     */
    public function save_task_data(stdClass $formdata, task $task) {
        global $USER;

        $mrproject = $task->get_mrproject();
        $cid = $mrproject->context->id;
        $task->set_data($formdata);
        //$task->attended = isset($formdata->attended);

        if (isset($formdata->studentnote_editor)) {
            $editor = $formdata->studentnote_editor;
            $task->studentnote = file_save_draft_area_files($editor['itemid'], $cid,
                    'mod_mrproject', 'studentnote', $task->id,
                    $this->noteoptions, $editor['text']);
            $task->studentnoteformat = $editor['format'];
        }

        $task->evaluatedby = $USER->id;

        
        /*if ($mrproject->uses_studentnotes() && isset($formdata->studentnote_editor)) {
            $editor = $formdata->studentnote_editor;
            $task->studentnote = file_save_draft_area_files($editor['itemid'], $cid,
                    'mod_mrproject', 'studentnote', $task->id,
                    $this->noteoptions, $editor['text']);
            $task->studentnoteformat = $editor['format'];
        }*/

        
        //save evaluation (garde)
        $task->save();

        //Event: grade_added
        \mod_mrproject\event\grade_added::create_from_task($task)->trigger();

        
        //Collective evaluation
        /*if (isset($formdata->distribute)) {
            $meeting = $task->get_meeting();
            $meeting->distribute_task_data($task);
        }*/
    }
}

