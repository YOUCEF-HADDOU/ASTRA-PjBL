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
 * A form for the creation and editing of groups.
 *
 * @copyright 2006 The Open University, N.D.Freear AT open.ac.uk, J.White AT open.ac.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_group
 */

defined('MOODLE_INTERNAL') || die;

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;

require_once($CFG->libdir.'/formslib.php');

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Group form class
 *
 * @copyright 2006 The Open University, N.D.Freear AT open.ac.uk, J.White AT open.ac.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_group
 */
class groupform extends moodleform {

    /**
     * @var mrproject the mrproject that this form refers to
     */
    protected $mrproject;


    public function __construct($action, mrproject $mrproject) {
        $this->mrproject = $mrproject;
        /*$this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $mrproject->get_context(), 'subdirs' => false);*/


        parent::__construct($action, null);
    }

    /**
     * Definition of the form
     */
    function definition () {
        global $USER, $CFG, $COURSE;
        $coursecontext = context_course::instance($COURSE->id);

        $mform =& $this->_form;
        //$editoroptions = $this->_customdata['editoroptions'];

        $editoroptions = array('trusttext' => false, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $coursecontext,
                                   'collapsed' => true);




        //$mform->addElement('header', 'general', get_string('general', 'form'));

        //Group name
        $mform->addElement('text', 'name', get_string('groupname', 'group'), 'maxlength="254" size="50"');
        $mform->setType('name', PARAM_TEXT);

        
        //Group description
        $mform->addElement('editor', 'description_editor', get_string('groupdescription', 'group'), 
                            array('rows' => 4, 'columns' => 60), $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);


        //current picture
        $mform->addElement('static', 'currentpicture', get_string('currentpicture'));

        //delete picture
        $mform->addElement('checkbox', 'deletepicture', get_string('delete'));
        $mform->setDefault('deletepicture', 0);



        //upload image
        $mform->addElement('filepicker', 'imagefile', get_string('newpicture', 'group'));
        $mform->addHelpButton('imagefile', 'newpicture', 'group');


        //hidden: course module id
        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);

        //hidden: mrproject id
        $mform->addElement('hidden','a');
        $mform->setType('a', PARAM_INT);

        //hidden: group id
        $mform->addElement('hidden','gid');
        $mform->setType('gid', PARAM_INT);

        //hidden: course id
        $mform->addElement('hidden','courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }
    




    /**
     * Extend the form definition after the data has been parsed.
     */
    public function definition_after_data() {
        global $COURSE, $DB, $USER;

        $mform = $this->_form;
        $groupid = $mform->getElementValue('gid');
        $coursecontext = context_course::instance($COURSE->id);

        if ($group = $DB->get_record('groups', array('id' => $groupid))) {
            // If can create group conversation then get if a conversation area exists and it is enabled.
            if (\core_message\api::can_create_group_conversation($USER->id, $coursecontext)) {
                if (\core_message\api::is_conversation_area_enabled('core_group', 'groups', $groupid, $coursecontext->id)) {
                    $mform->getElement('enablemessaging')->setSelected(1);
                }
            }
            // Print picture.
            if (!($pic = print_group_picture($group, $COURSE->id, true, true, false))) {
                $pic = get_string('none');
                if ($mform->elementExists('deletepicture')) {
                    $mform->removeElement('deletepicture');
                }
            }
            $imageelement = $mform->getElement('currentpicture');
            $imageelement->setValue($pic);
        } else {
            if ($mform->elementExists('currentpicture')) {
                $mform->removeElement('currentpicture');
            }
            if ($mform->elementExists('deletepicture')) {
                $mform->removeElement('deletepicture');
            }
        }

        

    }


    /***************************************/
    /*public function savegroup($groupid, $data) {
        global $COURSE, $DB, $USER;

        $context = $this->mrproject->get_context();


        //update group
        if ($data->gid) {
            $DB->update_record('groups', $data);
            //groups_update_group($data, $mform, $editoroptions);
            
        } else {  
            $data->id = $DB->insert_record('groups', $data);
            //$id = groups_create_group($data, $mform, $editoroptions);
            //$returnurl = $CFG->wwwroot.'/group/index.php?id='.$course->id.'&group='.$id;
        }
        
        
        return true;
    }*/

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array $errors An array of errors
     */
    function validation($data, $files) {
        global $COURSE, $DB, $CFG;

        $errors = parent::validation($data, $files);

        $name = trim($data['name']);
        if (isset($data['idnumber'])) {
            $idnumber = trim($data['idnumber']);
        } else {
            $idnumber = '';
        }
        if ($data['gid'] and $group = $DB->get_record('groups', array('id'=>$data['gid']))) {
            if (core_text::strtolower($group->name) != core_text::strtolower($name)) {
                if (groups_get_group_by_name($COURSE->id,  $name)) {
                    $errors['name'] = get_string('groupnameexists', 'group', $name);
                }
            }
            if (!empty($idnumber) && $group->idnumber != $idnumber) {
                if (groups_get_group_by_idnumber($COURSE->id, $idnumber)) {
                    $errors['idnumber']= get_string('idnumbertaken');
                }
            }

            /*if ($data['enrolmentkey'] != '') {
                $errmsg = '';
                if (!empty($CFG->groupenrolmentkeypolicy) && $group->enrolmentkey !== $data['enrolmentkey']
                        && !check_password_policy($data['enrolmentkey'], $errmsg)) {
                    // Enforce password policy when the password is changed.
                    $errors['enrolmentkey'] = $errmsg;
                } else {
                    // Prevent twice the same enrolment key in course groups.
                    $sql = "SELECT id FROM {groups} WHERE id <> :groupid AND courseid = :courseid AND enrolmentkey = :key";
                    $params = array('groupid' => $data['gid'], 'courseid' => $COURSE->id, 'key' => $data['enrolmentkey']);
                    if ($DB->record_exists_sql($sql, $params)) {
                        $errors['enrolmentkey'] = get_string('enrolmentkeyalreadyinuse', 'group');
                    }
                }
            }*/

        } else if (groups_get_group_by_name($COURSE->id, $name)) {
            $errors['name'] = get_string('groupnameexists', 'group', $name);
        } else if (!empty($idnumber) && groups_get_group_by_idnumber($COURSE->id, $idnumber)) {
            $errors['idnumber']= get_string('idnumbertaken');
        } else if ($data['enrolmentkey'] != '') {
            $errmsg = '';
            if (!empty($CFG->groupenrolmentkeypolicy) && !check_password_policy($data['enrolmentkey'], $errmsg)) {
                // Enforce password policy.
                $errors['enrolmentkey'] = $errmsg;
            } else if ($DB->record_exists('groups', array('courseid' => $COURSE->id, 'enrolmentkey' => $data['enrolmentkey']))) {
                // Prevent the same enrolment key from being used multiple times in course groups.
                $errors['enrolmentkey'] = get_string('enrolmentkeyalreadyinuse', 'group');
            }
        }

        return $errors;
    }

    /**
     * Get editor options for this form
     *
     * @return array An array of options
     */
    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }


}



