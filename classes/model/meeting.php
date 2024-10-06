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
 * A class for representing a mrproject meeting.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\model;

defined('MOODLE_INTERNAL') || die();

/**
 * A class for representing a 'mrproject meeting'.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting extends mvc_child_record_model {

    /**
     * @var mvc_child_list list of tasks in this meeting. (child list = list of tasks)
     */
    protected $tasks;



    /**
     * Create a new meeting in a specific mrproject
     *
     * @param mrproject $mrproject
     */
    public function __construct(mrproject $mrproject) {
        parent::__construct();
        $this->data = new \stdClass();
        $this->data->id = 0;
        $this->set_parent($mrproject);
        $this->data->mrprojectid = $mrproject->get_id();
        $this->tasks = new mvc_child_list($this, 'mrproject_task', 'meetingid',
                                                 new task_factory($this));
    }


    
    /**********************************************************************************/

    
    /**
     * get_table
     *
     * @return string
     */
    protected function get_table() {
        return 'mrproject_meeting';
    }

    
    /**
     * Create a mrproject meeting from the database.
     *
     * @param int $id
     * @param mrproject $mrproject
     */
    public static function load_by_id($id, mrproject $mrproject) {
        $meeting = new meeting($mrproject);
        $meeting->load($id);
        return $meeting;
    }

    /**
     * Save any changes to the database
     */
    public function save() {
        $this->data->mrprojectid = $this->get_parent()->get_id();
        parent::save();
        $this->tasks->save_children_meeting();
        $this->update_calendar();
    }

    /**
     * Sets task-related data (grade, comments) for all student in this meeting.
     *
     * @param task $template task from which the data will be read
     */
    public function distribute_task_data(task $template) {
        $mrproject = $this->get_mrproject();
        foreach ($this->tasks->get_children() as $task) {
            if ($task->id != $template->id && $task->studentid != 0) {
                if ($mrproject->uses_grades()) {
                    $task->grade = $template->grade;
                }
                if ($mrproject->uses_tasknotes()) {
                    $task->tasknote = $template->tasknote;
                    $task->tasknoteformat = $template->tasknoteformat;
                    $this->distribute_file_area('tasknote', $template->id, $task->id);
                }
                if ($mrproject->uses_teachernotes()) {
                    $task->studentnote = $template->studentnote;
                    $task->studentnoteformat = $template->studentnoteformat;
                    $this->distribute_file_area('teachernote', $template->id, $task->id);
                }
                $task->save();
            }
        }
    }

    /**
     * Distribute plugin files from a source to a target id within a file area
     *
     * @param mixed $area
     * @param mixed $sourceid
     * @param mixed $targetid
     */
    private function distribute_file_area($area, $sourceid, $targetid) {

        if ($sourceid == $targetid) {
            return;
        }

        $fs = get_file_storage();
        $component = 'mod_mrproject';
        $ctxid = $this->get_mrproject()->context->id;

        // Delete old files in the target area.
        $files = $fs->get_area_files($ctxid, $component, $area, $targetid);
        foreach ($files as $f) {
            $f->delete();
        }

        // Copy files from the source to the target.
        $files = $fs->get_area_files($ctxid, $component, $area, $sourceid);
        foreach ($files as $f) {
            $fs->create_file_from_storedfile(array('itemid' => $targetid), $f);
        }
    }

    /**
     * Retrieve the mrproject associated with this task.
     *
     * @return mrproject the mrproject
     */
    public function get_mrproject() {
        return $this->get_parent();
    }

    /**
     * Return the teacher object
     *
     * @return \stdClass
     */
    public function get_teacher() {
        global $DB;
        if ($this->data->teacherid) {
            return $DB->get_record('user', array('id' => $this->data->teacherid), '*', MUST_EXIST);
        } else {
            return new \stdClass();
        }
    }

    /***************************/
    


    

    /**
     * Return the end time of the meeting
     *
     * @return int
     */
    public function get_endtime() {
        return $this->data->starttime + $this->data->duration * MINSECS;
    }

    /**
     * Is this meeting bookable in its bookable period for students.
     * This checks for the availability time of the meeting and for the "guard time" restriction,
     * but not for the number of actualy booked tasks.
     *
     * @return boolean
     */
    public function is_in_bookable_period() {
        return true;
    }

    /**
     * Is this a group meeting (i.e., more than one student is permitted)
     *
     * @return boolean
     */
    public function is_groupmeeting() {
        return true;
    }


    /**
     * Count the number of tasks in this meeting
     *
     * @return int
     */
    public function get_task_count() {
        return $this->tasks->get_child_count();
    }

    /**
     * Get the task in this meeting for a specific student, or null if the student doesn't have one.
     *
     * @param int $studentid the id number of the student in question
     * @return task the task for the specified student
     */
    public function get_student_task($studentid) {
        $studapp = null;
        foreach ($this->get_tasks() as $app) {
            if ($app->studentid == $studentid) {
                $studapp = $app;
                break;
            }
        }
        return $studapp;
    }


    /**
     * Has the meeting been attended?
     *
     * @return boolean
     */
    public function is_attended() {
        /*$isattended = false;
        foreach ($this->tasks->get_children() as $app) {
            $isattended = $isattended || $app->attended;
        }*/
        return true;
    }

    /**
     * Has the meeting been booked by a specific student?
     *
     * @param mixed $studentid
     * @return boolean
     */
    public function is_booked_by_student($studentid) {
        $result = false;
        foreach ($this->get_tasks() as $task) {
            $result = $result || $task->studentid == $studentid;
        }
        return $result;
    }

    /**
     * Count the remaining free tasks in this meeting
     *
     * @return int
     */
    public function count_remaining_tasks() {
        if ($this->meetingheld == 0) {
            return -1;
        } else {
            $rem = $this->meetingheld - $this->get_task_count();
            if ($rem < 0) {
                $rem = 0;
            }
            return $rem;
        }
    }

    /**
     * Get an task by ID
     *
     * @param int $id
     * @return task
     */
    public function get_task($id) {
        return $this->tasks->get_child_by_id($id);
    }

    /**
     * Get an array of all tasks
     *
     * @param mixed $userfilter
     * @return array
     */
    public function get_tasks($userfilter = null) {
        $apps = $this->tasks->get_children();
        if ($userfilter) {
            foreach ($apps as $key => $app) {
                if (!in_array($app->studentid, $userfilter)) {
                    unset($apps[$key]);
                }
            }
        }
        return array_values($apps);
    }

    /**
     * Create a new task relating to this meeting.
     *
     * @return task
     */
    public function create_task() {
        return $this->tasks->create_child();
    }

    /**
     * Remove an task from this meeting.
     *
     * @param task $app
     */
    public function remove_task(task $app) {
        $this->tasks->remove_child($app);
    }

    /**
     * delete
     */
    public function delete() {
        $this->tasks->delete_children();
        $this->clear_calendar();
        $fs = get_file_storage();
        $fs->delete_area_files($this->get_mrproject()->get_context()->id, 'mod_mrproject', 'meetingnote', $this->get_id());
        parent::delete();
    }


    /**
     * delete (without grade)
     */
    public function delete_meeting() {
        $this->tasks->delete_children_meeting();
        $this->clear_calendar();
        $fs = get_file_storage();
        $fs->delete_area_files($this->get_mrproject()->get_context()->id, 'mod_mrproject', 'meetingnote', $this->get_id());
        parent::delete_meeting();
    }

    /**
     * Delete all tasks in this meeting.
     */
    public function delete_all_tasks() {
        $this->tasks->delete_children();
        $this->clear_calendar();
    }


    /* The event code is SSstu (for a student event) or SSsup (for a teacher event).
     * then, the id of the mrproject meeting that it belongs to.
    * finally, the courseID (legacy reasons -- not really used),
    * all in a colon delimited string. This will run into problems when the IDs of meetings and courses
    * are bigger than 7 digits in length...
    */

    /**
     * Get the id string for teacher events in this meeting
     * @return string
     */
    private function get_teacher_eventtype() {
        $meetingid = $this->get_id();
        $courseid = $this->get_parent()->get_courseid();
        return "SSsup:{$meetingid}:{$courseid}";
    }

    /**
     * Get the id string for student events in this meeting
     * @return string
     */
    private function get_student_eventtype() {
        $meetingid = $this->get_id();
        $courseid = $this->get_parent()->get_courseid();
        return "SSstu:{$meetingid}:{$courseid}";
    }

    /**
     * Remove all calendar events related to this meeting from the DB
     *
     * @uses $DB
     */
    private function clear_calendar() {
        global $DB;
        $DB->delete_records('event', array('eventtype' => $this->get_teacher_eventtype()));
        $DB->delete_records('event', array('eventtype' => $this->get_student_eventtype()));
    }

    /**
     * Update calendar events related to this meeting
     *
     * @uses $DB
     */
    private function update_calendar() {

        global $DB, $USER;

        $mrproject = $this->get_parent();

        $mytasks = $this->tasks->get_children();

        $studentids = array();
        foreach ($mytasks as $task) {
            if (!$task->is_attended()) {
                $studentids[] = $task->studentid;
            }
        }

        $teacher = $DB->get_record('user', array('id' => $this->teacherid));
        $students = $DB->get_records_list('user', 'id', $studentids);
        $studentnames = array();
        foreach ($students as $student) {
            $studentnames[] = fullname($student);
        }

        $mrprojectname = $mrproject->get_name(true);
        $mrprojectdescription = $mrproject->get_intro();

        $meetingid = $this->get_id();
        $courseid = $mrproject->get_courseid();

        $baseevent = new \stdClass();
        $baseevent->description = "$mrprojectname<br/><br/>$mrprojectdescription";
        $baseevent->format = 1;
        $baseevent->modulename = 'mrproject';
        $baseevent->courseid = 0;
        $baseevent->instance = $this->get_parent_id();
        if (isset($baseevent->timestart)) {
            $baseevent->timestart = $this->starttime;
        }
        $baseevent->timeduration = $this->duration * MINSECS;
        $baseevent->visible = 1;

        // Update student events.

        $studentevent = clone($baseevent);
        $studenteventname = get_string('meetingwith', 'mrproject').' '.$mrproject->get_teacher_name().', '.fullname($teacher);
        $studentevent->name = shorten_text($studenteventname, 200);

        $this->update_calendar_events( $this->get_student_eventtype(), $studentids, $studentevent);

        // Update teacher events.

        $teacherids = array();
        $teacherevent = clone($baseevent);
        if (count($studentids) > 0) {
            if ($teacher) {
                $teacherids[] = $teacher->id;
            } /*else {
                $teacherids[] = 0;
                //$teacherids[] = $USER->id;
            }*/
            
            if (count($studentids) > 1) {
                $teachereventname = get_string('meetingwithplural', 'mrproject').' '.
                                get_string('students', 'mrproject').', '.implode(', ', $studentnames);
            } else {
                $teachereventname = get_string('meetingwith', 'mrproject').' '.
                                get_string('student', 'mrproject').', '.$studentnames[0];
            }
            $teacherevent->name = shorten_text($teachereventname, 200);
        }

        $this->update_calendar_events( $this->get_teacher_eventtype(), $teacherids, $teacherevent);

    }

    /**
     * Update a certain type of calendat events related to this meeting.
     *
     * @param string $eventtype
     * @param array $userids users to assign to the event
     * @param \stdClass $eventdata dertails of the event
     */
    private function update_calendar_events($eventtype, array $userids, \stdClass $eventdata) {

        global $CFG, $DB;
        require_once($CFG->dirroot.'/calendar/lib.php');

        $eventdata->eventtype = $eventtype;

        $existingevents = $DB->get_records('event', array('modulename' => 'mrproject', 'eventtype' => $eventtype));
        $handledevents = array();
        $handledusers = array();

        // Update existing calendar events.
        foreach ($existingevents as $eventid => $existingdata) {
            if (in_array($existingdata->userid, $userids)) {
                $eventdata->userid = $existingdata->userid;
                $calendarevent = \calendar_event::load($existingdata);
                $calendarevent->update($eventdata, false);
                $handledevents[] = $eventid;
                $handledusers[] = $existingdata->userid;
            }
        }

        // Add new calendar events.
        foreach ($userids as $userid) {
            if (!in_array($userid, $handledusers)) {
                $thisevent = clone($eventdata);
                $thisevent->userid = $userid;
                \calendar_event::create($thisevent, false);
            }
        }

        // Remove old, obsolete calendar events.
        foreach ($existingevents as $eventid => $existingdata) {
            if (!in_array($eventid, $handledevents)) {
                $calendarevent = \calendar_event::load($existingdata);
                $calendarevent->delete();
            }
        }

    }


}

