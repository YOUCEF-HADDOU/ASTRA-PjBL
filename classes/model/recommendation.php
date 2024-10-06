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
 * A class for representing a 'task dependencies'.
 * 
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\model;

defined('MOODLE_INTERNAL') || die();



/**
 * A class for representing a recommendation of dependencies
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recommendation extends mvc_child_record_model {


    /**
     * Create an recommendation for a specific dependency
     *
     * @param dependency $dependency
     */
    public function __construct(dependency $dependency) {
        parent::__construct();
        $this->data = new \stdClass();
        $this->set_parent($dependency);
        $this->data->dependencyid = $dependency->get_id();
        //$this->data->consumedtime = 0;
        //$this->data->tasknoteformat = FORMAT_HTML;
        //$this->data->teachernoteformat = FORMAT_HTML;
    }




    /**********************************************************************************/

    /**
     * get_table
     *
     * @return string
     */
    protected function get_table() {
        return 'mrproject_recommendation';
    }


    /**
     * save recommendation
     */
    public function save() {
        $this->data->dependecyid = $this->get_parent()->get_id();
        parent::save();
        $scheddata = $this->get_mrproject()->get_data();
        //mrproject_update_grades($scheddata, $this->studentid);
        
    }



    /**
     * delete
     */
    /*public function delete() {
        $studid = $this->studentid;
        parent::delete();

        $scheddata = $this->get_mrproject()->get_data();
        mrproject_update_grades($scheddata, $studid);

        $fs = get_file_storage();
        $cid = $this->get_mrproject()->get_context()->id;
        $fs->delete_area_files($cid, 'mod_mrproject', 'tasknote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_mrproject', 'teachernote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_mrproject', 'studentnote', $this->get_id());

    }*/


    /**
     * delete  (without grades)
     */
    /*public function delete_meeting() {
        $studid = $this->studentid;
        parent::delete_meeting();

        $scheddata = $this->get_mrproject()->get_data();
        //mrproject_update_grades($scheddata, $studid);

        $fs = get_file_storage();
        $cid = $this->get_mrproject()->get_context()->id;
        $fs->delete_area_files($cid, 'mod_mrproject', 'tasknote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_mrproject', 'teachernote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_mrproject', 'studentnote', $this->get_id());

    }*/


    /**
     * Retrieve the dependency associated with this recommendation
     *
     * @return task;
     */
    public function get_recommendation() {
        return $this->get_parent();
    }

    /**
     * Retrieve the mrproject associated with this dependency
     *
     * @return mrproject
     */
    /*public function get_mrproject() {
        return $this->get_parent()->get_parent()->get_parent();
    }*/

    /**
     * Return the student object.
     * May be null if no student is assigned to this task (this _should_ never happen).
     */
    /*public function get_student() {
        global $DB;
        if ($this->data->studentid) {
            return $DB->get_record('user', array('id' => $this->data->studentid), '*', MUST_EXIST);
        } else {
            return null;
        }
    }*/



    /**
     * Are there any student notes associated with this task?
     * @return boolean
     */
    /*public function has_studentnotes() {
        return $this->get_mrproject()->uses_studentnotes() &&
                strlen(trim(strip_tags($this->studentnote))) > 0;
    }*/


    /**
     * How many files has the student uploaded for this task?
     *
     * @return int
     */
    /*public function count_studentfiles() {
        if (!$this->get_mrproject()->uses_studentnotes()) {
            return 0;
        }
        $ctx = $this->get_mrproject()->context->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($ctx, 'mod_mrproject', 'studentfiles', $this->id, "filename", false);
        return count($files);
    }*/



}