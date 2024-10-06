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
 * Defines the mrproject module settings form.
 * This file contains the forms to create and edit an instance of this module
 * 
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');


class mod_mrproject_mod_form extends moodleform_mod {

    /** @var array */
    protected $editoroptions;

    protected $noteoptions;

    /**
     * Form definition
     */
    public function definition() {

        global $CFG, $COURSE, $OUTPUT;
        $mform    =& $this->_form;

        /*$this->editoroptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                     'context' => $this->context, 'collapsed' => true);*/

        $timeoptions = null;
        if (isset($this->_customdata['timeoptions'])) {
            $timeoptions = $this->_customdata['timeoptions'];
        }

        $this->editoroptions = array('trusttext' => false, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $this->context,
                                   'collapsed' => true);

        //--------------------------------------------------------------------------------------------
        
        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        //Name
        $mform->addElement('text', 'name', get_string('projectname', 'mrproject'), array('size' => '90'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');


        // Start date/time
        $mform->addElement('date_time_selector', 'startdate', get_string('projectstartdate', 'mrproject'), $timeoptions);
        $mform->setDefault('startdate', time());

        
        // End date/time
        $mform->addElement('date_time_selector', 'enddate', get_string('projectenddate', 'mrproject'), $timeoptions);
        $mform->setDefault('enddate', time());

        
        //Context
        $this->standard_intro_elements(get_string('context', 'mrproject'));
        


        //Problem
        $mform->addElement('editor', 'problem_editor', get_string('problem', 'mrproject'),
                array('rows' => 6, 'columns' => 60), $this->editoroptions);
        $mform->setType('problem', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.


        //Objective
        $mform->addElement('editor', 'objective_editor', get_string('objective', 'mrproject'),
                array('rows' => 6, 'columns' => 60), $this->editoroptions);
        $mform->setType('objective', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.

        
        //--------------------------------------------------------------------------------------------

        /* Standard elements are added at the bottom of the settings (Common module settings, ...., Competencies). */
        $this->standard_coursemodule_elements();

        //$mform->setDefault('groupmode', NOGROUPS);
        $mform->setDefault('groupmode', SEPARATEGROUPS);
        $mform->setDefault('groupmodeforce', 1);
       

        //--------------------------------------------------------------------------------------------
        /* Add action buttons. */
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

        // Avoid empty meetings starting in the past.
        /*if ($data['startdate'] < time() || $data['enddate'] < time() || $data['enddate'] < $data['startdate']) {
            $errors['startdate'] = get_string('startpast', 'mrproject');
        }*/


        return $errors;
    }




    /**
     * Allows module to modify data returned by get_moduleinfo_data() or prepare_new_moduleinfo_data() before calling set_data()
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param array $defaultvalues passed by reference
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        //problem_editor
        if ($this->current->instance) {
            $newvalues = file_prepare_standard_editor((object)$defaultvalues, 'problem',
                             $this->editoroptions, $this->context,
                            'mod_mrproject', 'problem', 0);
            $defaultvalues['problem_editor'] = $newvalues->problem_editor;
        }

        //objective_editor
        if ($this->current->instance) {
            $newvalues = file_prepare_standard_editor((object)$defaultvalues, 'objective',
                             $this->editoroptions, $this->context,
                            'mod_mrproject', 'objective', 0);
            $defaultvalues['objective_editor'] = $newvalues->objective_editor;
        }


        //Grade
        $type = 'point';
        $defaultvalues['grade[modgrade_type]'] = $type;
        
    }




    /**
     * save_mod_data for the element 'Booking instructions'
     * save editor files in 'C:\\xampp\\moodledata'
     * 
     * @param stdClass $data
     * @param context_module $context
     */
    public function save_mod_data(stdClass $data, context_module $context) {
        global $DB;

        //problem_editor
        $editor1 = $data->problem_editor;  
        if ($editor1) {
            $data->problem = file_save_draft_area_files($editor1['itemid'], $context->id,
                                            'mod_mrproject', 'problem', 0,
                                            $this->editoroptions, $editor1['text']);
            $data->problemformat = $editor1['format'];
            $DB->update_record('mrproject', $data);
        }

        //objective_editor
        $editor2 = $data->objective_editor;  
        if ($editor2) {
            $data->objective = file_save_draft_area_files($editor2['itemid'], $context->id,
                                            'mod_mrproject', 'objective', 0,
                                            $this->editoroptions, $editor2['text']);
            $data->objectiveformat = $editor2['format'];
            $DB->update_record('mrproject', $data);
        }
    }

}
