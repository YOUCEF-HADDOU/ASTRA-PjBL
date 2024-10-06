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
 * Export settings form
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/mrproject/exportlib.php');

/**
 * Export settings form (using Moodle formslib)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_export_form extends moodleform {

    /**
     * @var mrproject the mrproject to be exported
     */
    protected $mrproject;

    /**
     * Create a new export settings form.
     *
     * @param string $action
     * @param mrproject $mrproject the mrproject to export
     * @param object $customdata
     */
    public function __construct($action, mrproject $mrproject, $customdata=null) {
        $this->mrproject = $mrproject;
        parent::__construct($action, $customdata);
    }

    /**
     * Form definition
     */
    protected function definition() {

        $mform = $this->_form;

        // General introduction.
        $mform->addElement('header', 'general', get_string('data', 'mrproject'));
        $mform->setExpanded('general', true);
        

        //only past meeting
        $timeoptions = [
            2 => get_string('exporttimerangepast', 'mrproject')
        ];
        $mform->addElement('select', 'timerange', get_string('exporttimerange', 'mrproject'), $timeoptions);
        $mform->setDefault('timerange', 2);
        $mform->hideIf('timerange', 'timerange' , 'eq', '2');


        //---------------------------

        //task grouped
        $radios = array();
        /*$radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('onelinepermeeting', 'mrproject'), 'onelinepermeeting');
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('onelinepertask', 'mrproject'),  'onelinepertask');*/
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('tasksgrouped', 'mrproject'), 'tasksgrouped');
        $mform->addGroup($radios, 'contentgroup',
                                          get_string('contentformat', 'mrproject'), null, false);
        $mform->setDefault('content', 'tasksgrouped');
        $mform->hideIf('contentgroup', 'timerange' , 'eq', '2');


        //everyone
        $selopt = array('all' => get_string ('everyone', 'mrproject'));
        $mform->addElement('select', 'includewhom', get_string('includemeetingsfor', 'mrproject'), $selopt);
        $mform->setDefault('includewhom', 'all');
        $mform->hideIf('includewhom', 'timerange' , 'eq', '2');


        
        //all meetings
        $selopt = array('all' => get_string('allononepage', 'mrproject'));
        $mform->addElement('select', 'paging', get_string('pagination', 'mrproject'),  $selopt);
        $mform->setDefault('paging', 0);
        $mform->hideIf('paging', 'timerange' , 'eq', '2');
        
        


        

        //yes
        $mform->addElement('selectyesno', 'includeemptymeetings', get_string('includeemptymeetings', 'mrproject'));
        $mform->setDefault('includeemptymeetings', 1);
        $mform->hideIf('includeemptymeetings', 'timerange' , 'eq', '2');


        //-----------------------------------------------------
        
        
        //$mform->addElement('header', 'datafieldhdr', get_string('datatoinclude', 'mrproject'));
        //$mform->setExpanded('datafieldhdr', true);
        //$mform->addHelpButton('datafieldhdr', 'datatoinclude', 'mrproject');


        // Select data to export.
        $this->add_exportfield_group('meeting', 'meeting');
        $this->add_exportfield_group('student', 'student');
        $this->add_exportfield_group('task', 'task');

        $mform->setDefault('field-date', 1);
        $mform->setDefault('field-starttime', 1);
        $mform->setDefault('field-endtime', 1);
        $mform->setDefault('field-location', 1);
        //$mform->setDefault('field-teachername', 0);
        $mform->setDefault('field-meetingnotes', 0);

        $mform->setDefault('field-studentfullname', 1);
        $mform->setDefault('field-studentemail', 1);
        $mform->setDefault('field-groupssingle', 1);

        $mform->setDefault('field-grade', 1);
        $mform->setDefault('field-tasknote', 1);
        $mform->setDefault('field-studentnote', 0);


        //-------------------------------------------------

        // Output file format.
        $mform->addElement('header', 'fileformathdr', get_string('fileformat', 'mrproject'));
        $mform->setExpanded('fileformathdr', true);
        //$mform->addHelpButton('fileformathdr', 'fileformat', 'mrproject');

        $radios = array();
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('csvformat', 'mrproject'), 'csv');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('excelformat', 'mrproject'),  'xls');
        //$radios[] = $mform->createElement('radio', 'outputformat', '', get_string('odsformat', 'mrproject'), 'ods');
        //$radios[] = $mform->createElement('radio', 'outputformat', '', get_string('htmlformat', 'mrproject'), 'html');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('pdfformat', 'mrproject'), 'pdf');
        $mform->addGroup($radios, 'outputformatgroup', get_string('fileformat', 'mrproject'), null, false);
        $mform->setDefault('outputformat', 'xls');

        //hide elements
        $mform->hideIf('timerange', 'outputformat' , 'eq', 'xls');
        $mform->hideIf('timerange', 'outputformat' , 'eq', 'pdf');

        /*$selopt = array('comma'     => get_string('sepcomma', 'mrproject'),
                        'colon'     => get_string('sepcolon', 'mrproject'),
                        'semicolon' => get_string('sepsemicolon', 'mrproject'),
                        'tab'       => get_string('septab', 'mrproject'));*/
        //$mform->addElement('select', 'csvseparator', get_string('csvfieldseparator', 'mrproject'),  'comma');
        //$mform->setDefault('csvseparator', 'comma');
        //$mform->disabledIf('csvseparator', 'outputformat', 'neq', 'csv');

        /*$selopt = array('P' => get_string('portrait', 'mrproject'),
                        'L' => get_string('landscape', 'mrproject'));*/
        /*$mform->addElement('select', 'pdforientation', get_string('pdforientation', 'mrproject'),  $selopt);
        $mform->disabledIf('pdforientation', 'outputformat', 'neq', 'pdf');*/


        //Actions
        $buttonarray = array();
        //$buttonarray[] = $mform->createElement('submit', 'preview', get_string('preview', 'mrproject'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('createexport', 'mrproject'));
        //$buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }

    /**
     * Add a group of export fields to the form.
     *
     * @param string $groupid id of the group in the list of fields
     * @param string $labelid language string id for the group label
     */
    private function add_exportfield_group($groupid, $labelid) {

        $mform = $this->_form;
        $fields = mrproject_get_export_fields($this->mrproject);
        $checkboxes = array();

        foreach ($fields as $field) {
            if ($field->get_group() == $groupid && $field->is_available($this->mrproject)) {
                $inputid = 'field-'.$field->get_id();
                $label = $field->get_formlabel($this->mrproject);
                $checkboxes[] = $mform->createElement('checkbox', $inputid, '', $label);
            }
        }
        $grouplabel = get_string($labelid, 'mrproject');
        $mform->addGroup($checkboxes, 'fields-'.$groupid, $grouplabel, null, false);
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

        return $errors;
    }

}
