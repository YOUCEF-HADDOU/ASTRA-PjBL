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
 * Library for export functions
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;
use \mod_mrproject\model\task;

require_once($CFG->dirroot.'/lib/excellib.class.php');
require_once($CFG->dirroot.'/lib/odslib.class.php');
require_once($CFG->dirroot.'/lib/csvlib.class.php');
require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');


/**
 * A data field included in an export from mrproject.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mrproject_export_field {

    /** @var mixed */
    protected $renderer;

    /**
     * set_renderer
     *
     * @param mod_mrproject_renderer $renderer
     */
    public function set_renderer(mod_mrproject_renderer $renderer) {
        $this->renderer = $renderer;
    }

    /**
     * Is the field available in this mrproject?
     *
     * @param mrproject $mrproject
     * @return bool whether the field is available
     */
    public function is_available(mrproject $mrproject) {
        return true;
    }

    /**
     * Retrieve the unique id (a string) for this field
     */
    public abstract function get_id();

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public abstract function get_group();

    /**
     * Retrieve the header (in the sense of table header in the output)
     * used for this field.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return string the header for this field
     */
    public function get_header(mrproject $mrproject) {
        return get_string('field-'.$this->get_id(), 'mrproject');
    }

    /**
     * Retrieve the header (in the sense of table header in the output) as an array.
     * Needs to be overridden for multi-column fields only.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return array the header for this field
     */
    public function get_headers(mrproject $mrproject) {
        return array($this->get_header($mrproject));
    }

    /**
     * Retrieve the label used in the configuration form to label this field.
     * By default, this equals the table header.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return string the form label for this field
     */
    public function get_formlabel(mrproject $mrproject) {
        return $this->get_header($mrproject);
    }

    /**
     * Retrieves the numer of table columns used by this field (typically 1).
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the number of columns used
     */
    public function get_num_columns(mrproject $mrproject) {
        return 1;
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return strlen($this->get_formlabel($mrproject));
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return false;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public abstract function get_value(meeting $meeting, $task);

    /**
     * Retrieve the value of this field as an array.
     * Needs to be overriden for multi-column fields only.
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return array an array of strings containing the column values
     */
    public function get_values(meeting $meeting, $task) {
        return array($this->get_value($meeting, $task));
    }

}

/**
 * Get a list of all export fields available.
 *
 * @param mrproject $mrproject
 * @return array the fields as an array of mrproject_export_field objects.
 */
function mrproject_get_export_fields(mrproject $mrproject) {
    $result = array();


    //Meeting
    $result[] = new meetingdate_field();
    $result[] = new mrproject_starttime_field();
    $result[] = new mrproject_endtime_field();
    $result[] = new mrproject_location_field();
    //$result[] = new mrproject_teachername_field();
    $result[] = new meetingnotes_field();


    //$result[] = new mrproject_maxstudents_field();



    //Student
    $result[] = new mrproject_student_field('studentfullname', 'fullname', 16);
    $result[] = new mrproject_student_field('studentemail', 'email', 15, true);
    $pfields = profile_get_custom_fields();
    foreach ($pfields as $id => $field) {
        $type = $field->datatype;
        $result[] = new mrproject_profile_field('profile_'.$type, $id, $type);
    }

    $result[] = new mrproject_groups_single_field();    //group


    //$result[] = new mrproject_student_field('studentfirstname', 'firstname');
    //$result[] = new mrproject_student_field('studentlastname', 'lastname');
    //$result[] = new mrproject_student_field('studentusername', 'username');
    //$result[] = new mrproject_student_field('studentidnumber', 'idnumber', 0, true);
    //$result[] = new mrproject_groups_multi_field($mrproject);



    //Task
    $result[] = new mrproject_tasknote_field();
    $result[] = new mrproject_grade_field();
    $result[] = new mrproject_studentnote_field();



    //$result[] = new mrproject_attended_field();
    //$result[] = new mrproject_teachernote_field();
    //$result[] = new mrproject_filecount_field();

    return $result;
}


/**
 * Export field: Date of the meeting
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meetingdate_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'date';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return strlen(mod_mrproject_renderer::userdate(1)) + 3;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        return mod_mrproject_renderer::userdate($meeting->starttime);
    }
}

/**
 * Export field: Start time of the meeting
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_starttime_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'starttime';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        return mod_mrproject_renderer::usertime($meeting->starttime);
    }

}


/**
 * Export field: End time of the meeting
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_endtime_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'endtime';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        return mod_mrproject_renderer::usertime($meeting->endtime);
    }

}

/**
 * Export field: Full name of the teacher
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_teachername_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'teachername';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the header (in the sense of table header in the output)
     * used for this field.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return string the header for this field
     */
    public function get_header(mrproject $mrproject) {
        return 'Supervisor';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 20;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        return fullname($meeting->teacher);
    }

}

/**
 * Export field: Appointment location
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_location_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'location';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        return format_string($meeting->tasklocation);
    }

}

/**
 * Export field: Maximum number of students / tasks in the meeting
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_maxstudents_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'maxstudents';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if ($meeting->meetingheld <= 0) {
            return get_string('unlimited', 'mrproject');
        } else {
            return $meeting->meetingheld;
        }
    }

}

/**
 * Export field: A field in the student record (to be chosen via the constructor)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_student_field extends mrproject_export_field {

    /** @var mixed */
    protected $id;
    /** @var mixed */
    protected $studfield;
    /** @var mixed */
    protected $typicalwidth;
    /** @var mixed */
    protected $idfield;

    /**
     * mrproject_student_field constructor.
     *
     * @param int $id
     * @param mixed $studfield
     * @param int $typicalwidth
     * @param bool $idfield
     */
    public function __construct($id, $studfield, $typicalwidth = 0, $idfield = false) {
        $this->id = $id;
        $this->studfield = $studfield;
        $this->typicalwidth = $typicalwidth;
        $this->idfield = $idfield;
    }

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'student';
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        if (!$this->idfield) {
            return true;
        }
        $ctx = $mrproject->get_context();
        return has_capability('moodle/site:viewuseridentity', $ctx);
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        if ($this->typicalwidth > 0) {
            return $this->typicalwidth;
        } else {
            return parent::get_typical_width($mrproject);
        }
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        $student = $task->get_student();
        if (is_null($student)) {
            return '';
        }
        if ($this->studfield == 'fullname') {
            return fullname($student);
        } else {
            return $student->{$this->studfield};
        }
    }

}

/**
 * Export field: A cutom profile field in the student record
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_profile_field extends mrproject_export_field {

    /** @var mixed */
    protected $id;
    /** @var mixed */
    protected $field;

    /**
     * Create a new export entry for a custom profile field.
     *
     * @param string $id the id of the field (for internal use)
     * @param int $fieldid id of the field in the database table
     * @param string $type data type of profile field to add
     */
    public function __construct($id, $fieldid, $type) {
        global $CFG;

        $this->id = $id;
        require_once($CFG->dirroot.'/user/profile/field/'.$type.'/field.class.php');
        $fieldclass = 'profile_field_'.$type;
        $fieldobj = new $fieldclass($fieldid, 0);
        $this->field = $fieldobj;
    }

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'student';
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool|mixed
     */
    public function is_available(mrproject $mrproject) {
        return $this->field->is_visible();
    }

    /**
     * Retrieve the header (in the sense of table header in the output)
     * used for this field.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return string the header for this field
     */
    public function get_header(mrproject $mrproject) {
        return format_string($this->field->field->name);
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (!$task instanceof task || $task->studentid == 0) {
            return '';
        }
        $this->field->set_userid($task->studentid);
        $this->field->load_data();
        if ($this->field->is_visible()) {
            $content = $this->field->display_data();
            return strip_tags($content);
        }
        return '';
    }

}


/**
 * Export field: Whether the task has been attended
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_attended_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'attended';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'task';
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        $str = $task->is_attended() ? get_string('yes') : get_string('no');
        return $str;
    }

}

/**
 * Export field: Slot notes
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meetingnotes_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'meetingnotes';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'meeting';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 30;
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return true;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        //return strip_tags($meeting->notes);
        return strip_tags($meeting->meetingpurpose);
    }

}

/**
 * Export field: Appointment notes
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_tasknote_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'tasknote';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'task';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 30;
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return true;
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        return $mrproject->uses_tasknotes();
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        return strip_tags($task->tasknote);
    }

}

/**
 * Export field: Teacher notes
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_teachernote_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'teachernote';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'task';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 30;
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return true;
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        return $mrproject->uses_teachernotes();
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        return strip_tags($task->teachernote);
    }

}

/**
 * Export field: Student-provided notes
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_studentnote_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'studentnote';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'task';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 10;
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return true;
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        return $mrproject->uses_studentnotes();
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        return strip_tags($task->studentnote);
    }

}

/**
 * Export field: Number of student-provided files
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_filecount_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'filecount';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'task';
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 2;
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return false;
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        return $mrproject->uses_studentfiles();
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        return $task->count_studentfiles();
    }

}

/**
 * Export field: Grade for the task
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_grade_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'grade';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'task';
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        return $mrproject->uses_grades();
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        return $task->grade;
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 8;
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return false;
    }

}

/**
 * Export field: Student groups (in one column)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_groups_single_field extends mrproject_export_field {

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'groupssingle';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'student';
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        $g = groups_get_all_groups($mrproject->courseid, 0, $mrproject->get_cm()->groupingid);
        return count($g) > 0;
    }

    /**
     * Retrieve the label used in the configuration form to label this field.
     * By default, this equals the table header.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return string the form label for this field
     */
    public function get_formlabel(mrproject $mrproject) {
        return get_string('field-groupssingle-label', 'mrproject');
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        $mrproject = $meeting->get_mrproject();
        $groups = groups_get_user_groups($mrproject->courseid, $task->studentid);
        $groupingid = $mrproject->get_cm()->groupingid;
        $gn = array();
        foreach ($groups[$groupingid] as $groupid) {
            $gn[] = groups_get_group_name($groupid);
        }
        return implode(',', $gn);
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(mrproject $mrproject) {
        return 10;
    }

}

/**
 * Export field: Student groups (in several columns)
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_groups_multi_field extends mrproject_export_field {

    /** @var mixed */
    protected $coursegroups;

    /**
     * mrproject_groups_multi_field constructor.
     *
     * @param mrproject $mrproject
     */
    public function __construct(mrproject $mrproject) {
        $this->coursegroups = groups_get_all_groups($mrproject->courseid, 0, $mrproject->get_cm()->groupingid);
    }

    /**
     * Retrieve the unique id (a string) for this field
     */
    public function get_id() {
        return 'groupsmulti';
    }

    /**
     * Retrieve the group that this field belongs to -
     * either 'meeting' or 'student' or 'task',
     *
     * @return string the group id as above
     */
    public function get_group() {
        return 'student';
    }

    /**
     * is_available
     *
     * @param mrproject $mrproject
     * @return bool
     */
    public function is_available(mrproject $mrproject) {
        return count($this->coursegroups) > 0;
    }

    /**
     * Retrieves the numer of table columns used by this field (typically 1).
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return int the number of columns used
     */
    public function get_num_columns(mrproject $mrproject) {
        return count($this->coursegroups);
    }

    /**
     * Retrieve the header (in the sense of table header in the output) as an array.
     * Needs to be overridden for multi-column fields only.
     *
     * @param mrproject $mrproject the mrproject instance in question
     * @return array the header for this field
     */
    public function get_headers(mrproject $mrproject) {
        $result = array();
        foreach ($this->coursegroups as $group) {
            $result[] = $group->name;
        }
        return $result;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return string the value of this field for the given data
     */
    public function get_value(meeting $meeting, $task) {
        return '';
    }

    /**
     * Retrieve the value of this field as an array.
     * Needs to be overriden for multi-column fields only.
     *
     * @param meeting $meeting the mrproject meeting to get data from
     * @param mixed $task the task to evaluate (may be null for an empty meeting)
     * @return array an array of strings containing the column values
     */
    public function get_values(meeting $meeting, $task) {
        if (! $task instanceof task) {
            return '';
        }
        $usergroups = groups_get_user_groups($meeting->get_mrproject()->courseid, $task->studentid)[0];
        $result = array();
        foreach ($this->coursegroups as $group) {
            $key = in_array($group->id, $usergroups) ? 'yes' : 'no';
            $result[] = get_string($key);
        }
        return $result;
    }

}

/**
 * An "output device" for mrproject exports
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mrproject_canvas {

    /**
     * @var object format instructions for header
     */
    public $formatheader;

    /**
     * @var object format instructions for boldface text
     */
    public $formatbold;

    /**
     * @var object format instructions for boldface italic text
     */
    public $formatboldit;

    /**
     * @var object format instructions for text with line wrapping
     */
    public $formatwrap;

    /**
     * Start a new page (tab, etc.) with an optional title.
     *
     * @param mixed $title the title of the page
     */
    public abstract function start_page($title);

    /**
     * Write a string into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $str the string to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public abstract function write_string($row, $col, $str, $format);

    /**
     * Write a number into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $num the number to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public abstract function write_number($row, $col, $num, $format);

    /**
     * Merge a range of cells in the same row.
     *
     * @param mixed $row the row in which to merge
     * @param mixed $fromcol the first column to merge
     * @param mixed $tocol the last column to merge
     */
    public abstract function merge_cells($row, $fromcol, $tocol);

    /**
     * Set the width of a particular column. (This will make sense only for certain outout formats,
     * it can be ignored otherwise.)
     *
     * @param int $col the affected column
     * @param int $width the width of that column
     */
    public function set_column_width($col, $width) {
        // Ignore widths by default.
    }

    /**
     * @var string title of the output file
     */
    protected $title;

    /**
     * Set the title of the entire output file.
     *
     * This is stored in the field $title, and can be used as appropriate for the particular implementation.
     *
     * @param string $title the title to set
     */
    public function set_title($title) {
        $this->title = $title;
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param string $filename the file name to send
     */
    public abstract function send($filename);

}

/**
 * Output device: Excel file
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_excel_canvas extends mrproject_canvas {

    /** @var mixed */
    protected $workbook;
    /** @var mixed */
    protected $worksheet;

    protected $cmid = null;

    /**
     * mrproject_excel_canvas constructor.
     */
    public function __construct($cmid) {

        // Create a workbook.
        $this->workbook = new MoodleExcelWorkbook("-");

        // Set up formats.
        $this->formatheader = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatboldit = $this->workbook->add_format();
        $this->formatwrap = $this->workbook->add_format();
        $this->formatheader->set_bold();
        $this->formatbold->set_bold();
        $this->formatboldit->set_bold();
        $this->formatboldit->set_italic();
        $this->formatwrap->set_text_wrap();
        $this->cmid = $cmid;   //course module id

    }

    /**
     * Start a new page (tab, etc.) with an optional title.
     *
     * @param mixed $title the title of the page
     */
    public function start_page($title) {
        $this->worksheet = $this->workbook->add_worksheet($title);
    }

    /**
     * ensure_open_page
     */
    private function ensure_open_page() {
        if (!$this->worksheet) {
            $this->start_page('');
        }
    }

    /**
     * Write a string into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $str the string to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public function write_string($row, $col, $str, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_string($row, $col, $str, $format);
    }

    /**
     * Write a number into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $num the number to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public function write_number($row, $col, $num, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_number($row, $col, $num, $format);
    }

    /**
     * Merge a range of cells in the same row.
     *
     * @param mixed $row the row in which to merge
     * @param mixed $fromcol the first column to merge
     * @param mixed $tocol the last column to merge
     */
    public function merge_cells($row, $fromcol, $tocol) {
        $this->ensure_open_page();
        $this->worksheet->merge_cells($row, $fromcol, $row, $tocol);
    }

    /**
     * Set the width of a particular column. (This will make sense only for certain outout formats,
     * it can be ignored otherwise.)
     *
     * @param int $col the affected column
     * @param int $width the width of that column
     */
    public function set_column_width($col, $width) {
        $this->worksheet->set_column($col, $col, $width);
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param string $filename the file name to send
     */
    public function send($filename) {
        if ($this->worksheet) {
            $this->workbook->send($filename);
            $this->workbook->close();
        } else {
            $url = new moodle_url('/mod/mrproject/view.php', array('id' => $this->cmid, 'what' => 'export'));
            redirect ($url);
        }
    }

}

/**
 * Output device: ODS file
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_ods_canvas extends mrproject_canvas {

    /** @var mixed */
    protected $workbook;
    /** @var mixed */
    protected $worksheet;

    /**
     * mrproject_ods_canvas constructor.
     */
    public function __construct() {

        // Create a workbook.
        $this->workbook = new MoodleODSWorkbook("-");

        // Set up formats.
        $this->formatheader = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatboldit = $this->workbook->add_format();
        $this->formatwrap = $this->workbook->add_format();
        $this->formatheader->set_bold();
        $this->formatbold->set_bold();
        $this->formatboldit->set_bold();
        $this->formatboldit->set_italic();
        $this->formatwrap->set_text_wrap();

    }

    /**
     * Start a new page (tab, etc.) with an optional title.
     *
     * @param mixed $title the title of the page
     */
    public function start_page($title) {
        $this->worksheet = $this->workbook->add_worksheet($title);
    }

    /**
     * ensure_open_page
     */
    private function ensure_open_page() {
        if (!$this->worksheet) {
            $this->start_page('');
        }
    }

    /**
     * Write a string into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $str the string to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public function write_string($row, $col, $str, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_string($row, $col, $str, $format);
    }

    /**
     * Write a number into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $num the number to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public function write_number($row, $col, $num, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_number($row, $col, $num, $format);
    }

    /**
     * Merge a range of cells in the same row.
     *
     * @param mixed $row the row in which to merge
     * @param mixed $fromcol the first column to merge
     * @param mixed $tocol the last column to merge
     */
    public function merge_cells($row, $fromcol, $tocol) {
        $this->ensure_open_page();
        $this->worksheet->merge_cells($row, $fromcol, $row, $tocol);
    }

    /**
     * Set the width of a particular column. (This will make sense only for certain outout formats,
     * it can be ignored otherwise.)
     *
     * @param int $col the affected column
     * @param int $width the width of that column
     */
    public function set_column_width($col, $width) {
        $this->worksheet->set_column($col, $col, $width);
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param string $filename the file name to send
     */
    public function send($filename) {
        $this->workbook->send($filename);
        $this->workbook->close();
    }

}


/**
 * An output device that is based on first collecting all text in an array.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mrproject_cached_text_canvas extends mrproject_canvas {

    /** @var mixed */
    protected $pages;
    /** @var mixed */
    protected $curpage;

    /**
     * mrproject_cached_text_canvas constructor.
     */
    public function __construct() {

        $this->formatheader = 'header';
        $this->formatbold = 'bold';
        $this->formatboldit = 'boldit';
        $this->formatwrap = 'wrap';

        $this->start_page('');

    }

    /**
     * get_col_count
     *
     * @param mixed $page
     * @return int
     */
    protected function get_col_count($page) {
        $maxcol = 0;
        foreach ($page->cells as $rownum => $row) {
            foreach ($row as $colnum => $col) {
                if ($colnum > $maxcol) {
                    $maxcol = $colnum;
                }
            }
        }
        return $maxcol + 1;
    }

    /**
     * get_row_count
     *
     * @param mixed $page
     * @return int
     */
    protected function get_row_count($page) {
        $maxrow = 0;
        foreach ($page->cells as $rownum => $row) {
            if ($rownum > $maxrow) {
                $maxrow = $rownum;
            }
        }
        return $maxrow + 1;
    }

    /**
     * compute_relative_widths
     *
     * @param mixed $page
     * @return array
     */
    protected function compute_relative_widths($page) {
        $cols = $this->get_col_count($page);
        $sum = 0;
        foreach ($page->columnwidths as $width) {
            $sum += $width;
        }
        $relwidths = array();
        for ($col = 0; $col < $cols; $col++) {
            if ($sum > 0 && isset($page->columnwidths[$col])) {
                $relwidths[$col] = (int) ($page->columnwidths[$col] / $sum * 100);
            } else {
                $relwidths[$col] = 0;
            }
        }
        return $relwidths;
    }

    /**
     * Start a new page (tab, etc.) with an optional title.
     *
     * @param mixed $title the title of the page
     */
    public function start_page($title) {
        $onemptypage = $this->curpage &&  !$this->curpage->cells && !$this->curpage->mergers && !$this->curpage->title;
        if ($onemptypage) {
            $this->curpage->title = $title;
        } else {
            $newpage = new stdClass;
            $newpage->title = $title;
            $newpage->cells = array();
            $newpage->formats = array();
            $newpage->mergers = array();
            $newpage->columnwidths = array();
            $this->pages[] = $newpage;
            $this->curpage = $newpage;
        }
    }

    /**
     * Write a string into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $str the string to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public function write_string($row, $col, $str, $format=null) {
        $this->curpage->cells[$row][$col] = $str;
        $this->curpage->formats[$row][$col] = $format;
    }

    /**
     * Write a number into a certain position of the canvas.
     *
     * @param mixed $row the row into which to write (starts with 0)
     * @param mixed $col the column into which to write (starts with 0)
     * @param mixed $num the number to write
     * @param mixed $format the format to use (one of the $format... fields of this object), can be null
     */
    public function write_number($row, $col, $num, $format=null) {
        $this->write_string($row, $col, $num, $format);
    }

    /**
     * Merge a range of cells in the same row.
     *
     * @param mixed $row the row in which to merge
     * @param mixed $fromcol the first column to merge
     * @param mixed $tocol the last column to merge
     */
    public function merge_cells($row, $fromcol, $tocol) {
        $this->curpage->mergers[$row][$fromcol] = $tocol - $fromcol + 1;
    }

    

    /**
     * Set the width of a particular column. (This will make sense only for certain outout formats,
     * it can be ignored otherwise.)
     *
     * @param int $col the affected column
     * @param int $width the width of that column
     */
    public function set_column_width($col, $width) {
        $this->curpage->columnwidths[$col] = $width;
    }

}

/**
 * Output device: HTML file
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_html_canvas extends mrproject_cached_text_canvas {

    /**
     * as_html
     *
     * @param mixed $rowcutoff
     * @param bool $usetitle
     * @return string
     */
    public function as_html($rowcutoff, $usetitle = true) {
        global $OUTPUT;

        $o = '';

        if ($usetitle && $this->title) {
            $o .= html_writer::tag('h1', $this->title);
        }

        foreach ($this->pages as $page) {
            if ($page->title) {
                $o .= html_writer::tag('h2', $page->title);
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);
            if ($rowcutoff && $rows > $rowcutoff) {
                $rows = $rowcutoff;
            }
            $relwidths = $this->compute_relative_widths($page);

            $table = new html_table();
            $table->cellpadding = 3;
            for ($row = 0; $row < $rows; $row++) {
                $hrow = new html_table_row();
                $col = 0;
                while ($col < $cols) {
                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $cell = new html_table_cell('');
                    $text = '';
                    if (isset($page->cells[$row][$col])) {
                        $text = $page->cells[$row][$col];
                    }
                    if (isset($page->formats[$row][$col])) {
                        $cell->header = ($page->formats[$row][$col] == 'header');
                        if ($page->formats[$row][$col] == 'boldit') {
                            $text = html_writer::tag('i', $text);
                            $text = html_writer::tag('b', $text);
                        }
                        if ($page->formats[$row][$col] == 'bold') {
                            $text = html_writer::tag('b', $text);
                        }
                    }
                    if ($span > 1) {
                        $cell->colspan = $span;
                    }
                    if ($row == 0 & $relwidths[$col] > 0) {
                        $cell->width = $relwidths[$col].'%';
                    }
                    $cell->text = $text;
                    $hrow->cells[] = $cell;
                    $col = $col + $span;
                }
                $table->data[] = $hrow;
            }
            $o .= html_writer::table($table);
        }
        return $o;
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param string $filename the file name to send
     */
    public function send($filename) {
        global $OUTPUT, $PAGE;
        $PAGE->set_pagelayout('print');
        echo $OUTPUT->header();
        echo $this->as_html(0, true);
        echo $OUTPUT->footer();
    }

}

/**
 * Output device: CSV (text) file
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_csv_canvas extends mrproject_cached_text_canvas {

    /** @var mixed */
    protected $delimiter;

    /**
     * mrproject_csv_canvas constructor.
     *
     * @param mixed $delimiter
     */
    public function __construct($delimiter) {
        parent::__construct();
        $this->delimiter = $delimiter;
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param string $filename the file name to send
     */
    public function send($filename) {

        $writer = new csv_export_writer($this->delimiter);
        $writer->set_filename($filename);

        foreach ($this->pages as $page) {
            if ($page->title) {
                $writer->add_data(array('*** '.$page->title.' ***'));
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);

            for ($row = 0; $row < $rows; $row++) {
                $data = array();
                $col = 0;
                while ($col < $cols) {
                    if (isset($page->cells[$row][$col])) {
                        $data[] = $page->cells[$row][$col];
                    } else {
                        $data[] = '';
                    }

                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $col += $span;
                }
                $writer->add_data($data);
            }
        }

        $writer->download_file();
    }

}

/**
 * Output device: PDF file
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_pdf_canvas extends mrproject_cached_text_canvas {

    /** @var mixed */
    protected $orientation;

    /**
     * mrproject_pdf_canvas constructor.
     *
     * @param mixed $orientation
     */
    public function __construct($orientation) {
        parent::__construct();
        $this->orientation = $orientation;
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param string $filename the file name to send
     */
    public function send($filename) {

        $doc = new pdf($this->orientation);
        if ($this->title) {
            $doc->setHeaderData('', 0, $this->title);
            $doc->setPrintHeader(true);
        } else {
            $doc->setPrintHeader(false);
        }
        $doc->setPrintFooter(false);

        foreach ($this->pages as $page) {
            $doc->AddPage();
            if ($page->title) {
                $doc->writeHtml('<h2>'.$page->title.'</h2>');
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);
            $relwidths = $this->compute_relative_widths($page);

            $o = html_writer::start_tag('table', array('border' => 1, 'cellpadding' => 1));
            for ($row = 0; $row < $rows; $row++) {
                $o .= html_writer::start_tag('tr');
                $col = 0;
                while ($col < $cols) {
                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $opts = array();
                    if ($row == 0 && $relwidths[$col] > 0) {
                        $opts['width'] = $relwidths[$col].'%';
                    }
                    if ($span > 1) {
                        $opts['colspan'] = $span;
                    }
                    $o .= html_writer::start_tag('td', $opts);
                    $cell = '';
                    if (isset($page->cells[$row][$col])) {
                        $cell = s($page->cells[$row][$col]);
                        if (isset($page->formats[$row][$col])) {
                            $thisformat = $page->formats[$row][$col];
                            if ($thisformat == 'header') {
                                $cell = html_writer::tag('b', $cell);
                            } else if ($thisformat == 'boldit') {
                                $cell = html_writer::tag('i', $cell);
                            }
                        }
                    }
                    $o .= $cell;

                    $o .= html_writer::end_tag('td');

                    $col += $span;
                }
                $o .= html_writer::end_tag('tr');
            }
            $o .= html_Writer::end_tag('table');
            $doc->writeHtml($o);
        }

        $doc->Output($filename.'.pdf');
    }

}

/**
 * A class that generates the export file with given settings.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_export {

    /**
     * @var mrproject_canvas the canvas used for output
     */
    protected $canvas;

    /**
     * @var array a list of student ids to be filtered for
     */
    protected $studfilter = null;

    /**
     * Create a new export with a given canvas
     *
     * @param mrproject_canvas $canvas the canvas to use
     */
    public function __construct(mrproject_canvas $canvas) {
        $this->canvas = $canvas;
    }

    /**
     * Build the output on the canvas.
     *
     * @param mrproject $mrproject the mrproject to export
     * @param array $fields the fields to include
     * @param string $mode output mode
     * @param int $userid id of the teacher to export for, 0 if meetings for all teachers are exported
     * @param int $groupid the id of the group (of students) to export tasks for, 0 if none
     * @param mixed $timerange
     * @param bool $includeempty whether to include meetings without tasks
     * @param bool $pageperteacher whether one page should be used for each teacher
     */
    public function build(mrproject $mrproject, array $fields, $mode, $userid, $groupid, $timerange, $includeempty,
                          $pageperteacher) {
        
        
        if ($groupid) {
            $this->studfilter = array_keys(groups_get_members($groupid, 'u.id'));
        }
        $this->canvas->set_title('Project: '.format_string($mrproject->name).' (exported On: '. $mrproject->usertime( time() ).')');
        if ($userid) {
            $meetings = $mrproject->get_meetingsheld_for_teacher($userid, $groupid, '', '', $timerange);
            $this->build_page($mrproject, $fields, $meetings, $mode, $includeempty);
        } else if ($pageperteacher) {
            $teachers = $mrproject->get_teachers();
            foreach ($teachers as $teacher) {
                $meetings = $mrproject->get_meetingsheld_for_teacher($teacher->id, $groupid, '', '', $timerange);
                $title = fullname($teacher);
                $this->canvas->start_page($title);
                $this->build_page($mrproject, $fields, $meetings, $mode, $includeempty);
            }
        } else {
            $meetings = $mrproject->get_meetings_for_group($groupid, '', '', $timerange);
            $this->build_page($mrproject, $fields, $meetings, $mode, $includeempty);
        }
    }

    /**
     * Write a page of output to the canvas.
     * (Pages correspond to "tabs" in spreadsheet format, not to printed pages.)
     *
     * @param mrproject $mrproject the mrproject being exported
     * @param array $fields the fields to include
     * @param array $meetings the meetings to include
     * @param string $mode output mode
     * @param bool $includeempty whether to include meetings without tasks
     */
    protected function build_page(mrproject $mrproject, array $fields, array $meetings, $mode, $includeempty) {

        // Output the header.
        $row = 0;
        /*$col = 0;
        foreach ($fields as $field) {
            if ($field->get_group() != 'meeting' || $mode != 'tasksgrouped') {
                $headers = $field->get_headers($mrproject);
                $numcols = $field->get_num_columns($mrproject);
                for ($i = 0; $i < $numcols; $i++) {
                    $this->canvas->write_string($row, $col + $i, '', $this->canvas->formatheader);
                    //$this->canvas->set_column_width($col + $i, $field->get_typical_width($mrproject));
                    $this->canvas->set_column_width($col + $i, $field->get_typical_width($mrproject));
                }
                $col = $col + $numcols;
            }
        }
        $row++;

        $this->canvas->merge_cells($row-1, 0, $col - 1);*/

        // Output the data rows.
        $meetingfilter = null;
        foreach ($meetings as $meeting) {
            $appts = $meeting->get_tasks($this->studfilter);
            if ($mode == 'tasksgrouped') {
                if ($appts || $includeempty) {
                    foreach ($appts as $appt) {
                        $userfilter = $appt->studentid;
                        $teachercaps = ['mod/mrproject:managealltasks']; 
                        $isteacher = has_any_capability($teachercaps, $mrproject->get_context(), $userfilter);
                        if (!$isteacher && $meetingfilter != $meeting) {

                            //header (titles)
                            $col = 0;
                            foreach ($fields as $field) {
                                if ($field->get_group() != 'meeting' || $mode != 'tasksgrouped') {
                                    $headers = $field->get_headers($mrproject);
                                    $numcols = $field->get_num_columns($mrproject);
                                    for ($i = 0; $i < $numcols; $i++) {
                                        $this->canvas->write_string($row, $col + $i, $headers[$i], $this->canvas->formatheader);
                                        $this->canvas->set_column_width($col + $i, $field->get_typical_width($mrproject));
                                    }
                                    $col = $col + $numcols;
                                }
                            }
                            $row++;


                            //meeting infos
                            $this->write_row_summary($row, $meeting, $fields);     //write meeting summary (exported file)
                            $row++;
                            $meetingfilter = $meeting;
                        }


                    }
                }

                //Tasks and grades
                foreach ($appts as $appt) {

                    $userfilter = $appt->studentid;
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    $isteacher = has_any_capability($teachercaps, $mrproject->get_context(), $userfilter);
                    if (!$isteacher && $appt->studentid != 0) {
                        if ($appt->collectivetask == null || $appt->collectivetask == '0') {
                            $this->write_row($row, $meeting, $appt, $fields, false, false, $appt->studentid);
                            $row++;
                        } else {
                            $studentids = explode('+', $appt->collectivetask);
                            foreach ($studentids as $studentid) {
                                $this->write_row($row, $meeting, $appt, $fields, false, false, $studentid);
                                $row++;
                            }
                        }
                    }
                }
            } /*else {
                if ($appts) {
                    if ($mode == 'onelinepertask') {
                        foreach ($appts as $appt) {
                            $this->write_row($row, $meeting, $appt, $fields, true);
                            $row++;
                        }
                    } else {
                        $this->write_row($row, $meeting, $appts[0], $fields, true, count($appts) > 1);
                        $row++;
                    }
                } else if ($includeempty) {
                    $this->write_row($row, $meeting, null, $fields, true);
                    $row++;
                }
            }*/
        }

    }

    /**
     * Write a row of the export to the canvas
     * @param int $row row number on canvas
     * @param meeting $meeting the meeting of the task to write
     * @param task $task the task to write
     * @param array $fields list of fields to include
     * @param bool $includemeetingfields whether fields relating to meetings, rather than tasks, should be included
     * @param string $multiple whether the row represents multiple values (tasks)
     */
    protected function write_row($row, meeting $meeting, $task, array $fields, $includemeetingfields = true,
                                 $multiple = false, $studentid) {

        $col = 0;
        foreach ($fields as $field) {
            if ($includemeetingfields || $field->get_group() != 'meeting') {
                if ($multiple && $field->get_group() != 'meeting') {
                    $value = get_string('multiple', 'mrproject');
                    $this->canvas->write_string($row, $col, $value);
                    $col++;
                } else {

                    $numcols = $field->get_num_columns($meeting->get_mrproject());
                    $values = $field->get_values($meeting, $task);
                    $format = $field->is_wrapping() ? $this->canvas->formatwrap : null;
                    for ($i = 0; $i < $numcols; $i++) {
                        if ($field->get_id() == 'studentfullname') {
                            $values[$i] = fullname($meeting->get_mrproject()->get_userbyid($studentid));
                            $this->canvas->write_string($row, $col + $i, $values[$i], $format);
                        }
                        if ($field->get_id() == 'studentemail') {
                            $values[$i] = $meeting->get_mrproject()->get_userbyid($studentid)->email;
                        }
                        $this->canvas->write_string($row, $col + $i, $values[$i], $format);
                    }
                    $col = $col + $numcols;

                    
                }
            }
        }
    }

    /**
     * Write a summary of meeting-related data into a row (meeting infos)
     *
     * @param int $row the row number on the canvas
     * @param meeting $meeting the meeting to be written
     * @param array $fields the fields to include
     */
    protected function write_row_summary($row, meeting $meeting, array $fields) {

        $strs = array();

        //initialise fields
        /*for ($i = 0; $i < 6; $i++) {
            $strs[$i] = ' ';
        }*/

        $cols = 0;
        foreach ($fields as $field) {
            if ($field->get_group() == 'meeting') {
                $strs[] = $field->get_value($meeting, null);
            } else {
                $cols++;
            }
        }
        $str = implode(' - ', $strs);
        
        //$str1 = 'Meeting: '.$strs[0].' ['.$strs[1].' - '. $strs[2].'],  '.'Location: ' .$strs[3];
        //$str2 = 'Location: ' .$strs[3];


        /*if (array_key_exists(0, $strs) ) {
            $strs[0] = 'Meeting date: '.$strs[0];
        } else {
            $strs[0] = ' ';
        }

        if (array_key_exists(1, $strs) ) {
            $strs[1] = ' ['.$strs[1];
        } else {
            $strs[1] = ' ';
        }

        if (array_key_exists(2, $strs) ) {
            $strs[2] = ' - '. $strs[2].']';
        } else {
            $strs[2] = ' ';
        }

        if (array_key_exists(3, $strs) ) {
            $strs[3] = ' ;  '.'Location: ' .$strs[3];
        } else {
            $strs[3] = ' ';
        }

        if (array_key_exists(4, $strs) ) {
            $strs[4] = ' ;  Meeting purpose: ' .$strs[4];
        } else {
            $strs[4] = ' ';
        }*/


        //$str = 'Meeting date: '.$strs[0].' ['.$strs[1].' - '. $strs[2].'] ;  '.'Location: ' .$strs[3].' ;  Meeting purpose: ' .$strs[5];

        /*for ($i = 0; $i < 6; $i++) {
            if ($field->get_group() != 'meeting') {
                $strs[$i] = ' ';
                $cols++;
            }
        }*/


        //$str = $strs[0] . $strs[1] . $strs[2] . $strs[3] . $strs[4];



        $this->canvas->write_string($row, 0, 'Meeting information: [ '.$str. ' ]', $this->canvas->formatheader);
        //$this->canvas->write_string($row+1, 0, $str2, $this->canvas->formatwrap);

        $this->canvas->merge_cells($row, 0, $cols - 1);
        //$this->canvas->merge_cells($row+1, 0, $cols - 1);
    }

}
