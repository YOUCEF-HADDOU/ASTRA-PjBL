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
 * A class for representing a mrproject instance.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\model;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/lib.php');

/**
 * A class for representing a mrproject instance, as an MVC model.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject extends mvc_record_model {

    /**
     * @var \stdClass course module record for this mrproject
     */
    protected $cm = null;

    /**
     * @var \stdClass course record for this mrproject
     */
    protected $courserec = null;

    /**
     * @var \context_module context record of this mrproject
     */
    protected $context = null;

    /**
     * @var int effective group mode of this mrproject
     */
    protected $groupmode;

    /**
     * @var mvc_child_list list of meetings in this mrproject. (child list = list of meetings)
     */
    protected $meetings;




    /**
     * mrproject constructor.
     */
    protected function __construct() {
        parent::__construct();
        $this->meetings = new mvc_child_list($this, 'mrproject_meeting', 'mrprojectid', new meeting_factory($this));
    }




    /**********************************************************************************/

    
    /**
     * get_table
     *
     * @return string
     */
    protected function get_table() {
        return 'mrproject';
    }

    
    /**
     * Create a mrproject instance from the database.
     *
     * @param int $id module id of the mrproject
     * @return mrproject
     */
    public static function load_by_id($id) {
        global $DB;
        $cm = get_coursemodule_from_instance('mrproject', $id, 0, false, MUST_EXIST);
        return self::load_from_record($id, $cm);
    }

    /**
     * Create a mrproject instance from the database.
     *
     * @param int $cmid course module id of the mrproject
     * @return mrproject
     */
    public static function load_by_coursemodule_id($cmid) {
        global $DB;
        $cm = get_coursemodule_from_id('mrproject', $cmid, 0, false, MUST_EXIST);
        return self::load_from_record($cm->instance, $cm);
    }

    /**
     * Create a mrproject instance from an already loaded record.
     *
     * @param int $id the module id of the mrproject
     * @param \stdClass $coursemodule course module record
     * @return mrproject
     */
    protected static function load_from_record($id, \stdClass $coursemodule) {
        $mrproject = new mrproject();
        $mrproject->load($id);
        $mrproject->cm = $coursemodule;
        $mrproject->groupmode = groups_get_activity_groupmode($coursemodule);
        return $mrproject;
    }

    /**
     * Save any changes to the database
     */
    public function save() {
        parent::save();
        $this->meetings->save_children();
    }

    /**
     * Delete the mrproject
     */
    public function delete() {
        $this->delete_all_meetings();
        mrproject_grade_item_delete($this);
        parent::delete();
    }

    /**
     * Delete all meetings from this mrproject.
     */
    public function delete_all_meetings() {
        $this->meetings->delete_children();
        mrproject_grade_item_update($this, 'reset');
    }

    /**
     * Retrieve the course module id of this mrproject
     *
     * @return int
     */
    public function get_cmid() {
        return $this->cm->id;
    }

    /**
     * Retrieve the course module record of this mrproject
     *
     * @return \stdClass
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Retrieve the course id of this mrproject
     *
     * @return int
     */
    public function get_courseid() {
        return $this->data->course;
    }

    /**
     * Retrieve the course record of this mrproject
     *
     * @return \stdClass
     */
    public function get_courserec() {
        global $DB;
        if (is_null($this->courserec)) {
            $this->courserec = $DB->get_record('course', array('id' => $this->get_courseid()), '*', MUST_EXIST);
        }
        return $this->courserec;
    }

    /**
     * Retrieve the activity module context of this mrproject
     *
     * @return context_module
     */
    public function get_context() {
        if ($this->context == null) {
            $this->context = \context_module::instance($this->get_cmid());
        }
        return $this->context;
    }

    /**
     * Return the last modification date (as stored in database) for this mrproject instance.
     *
     * @return int
     */
    public function get_timemodified() {
        return $this->data->timemodified;
    }

    /**
     * Retrieve the name of this mrproject
     *
     * @param bool $applyfilters whether to apply filters so that the output is printable
     * @return string
     */
    public function get_name($applyfilters = false) {
        $name = $this->data->name;
        if ($applyfilters) {
            $name = format_text($name);
        }
        return $name;
    }

    /**
     * Retrieve the intro of this mrproject
     *
     * @param bool $applyfilters whether to apply filters so that the output is printable
     * @return string
     */
    public function get_intro($applyfilters = false) {
        $intro = $this->data->intro;
        if ($applyfilters) {
            $intro = format_text($intro);
        }
        return $intro;
    }

    
    /**
     * Retrieve the name for "teacher" in the context of this mrproject
     *
     * TODO: This involves part of the presentation, should it be here?
     *
     * @return string
     */
    public function get_teacher_name() {
        
        $name = get_string('meetingwith', 'mrproject');

        return $name;
    }

    
    /***********************************/
    public function get_userbyid($id) {
        global $DB;
        if ($id) {
            return $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
        } else {
            return new \stdClass();
        }
    }
    
    /**********************************/
    public function get_tasks($userfilter = null) {
        $apps = $this->meetings->get_children();
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
     * Retrieve the default duration of a meeting, in minutes
     *
     * @return int
     */
    public function get_default_meeting_duration() {
        return $this->data->defaultmeetingduration;
    }


    
    /**
     * Retrieve whether group scheduling is enabled in this instance
     *
     * @return boolean
     */
    public function is_group_scheduling_enabled() {
        /*global $CFG;
        $globalenable = true;
        $localenable = $this->bookingrouping >= 0;
        return $globalenable && $localenable;*/
        return true;
    }

    /**
     * Retrieve whether individual scheduling is enabled in this instance.
     * This is usually the case, but is disabled if the instance uses group scheduling
     * and the configuration setting 'mixindivgroup' is set to inactive.
     *
     * @return boolean
     */
    public function is_individual_scheduling_enabled() {
        
        return true;
        
    }


    /**
     * get the last location of a certain teacher in this mrproject
     *
     * @param \stdClass $user
     * @uses $DB
     * @return string the last known location for the current user (teacher)
     */
    public function get_last_location($user) {
        global $DB;

        $conds = array('mrprojectid' => $this->data->id, 'teacherid' => $user->id);
        $recs = $DB->get_records('mrproject_meeting', $conds, 'timemodified DESC', 'id,tasklocation', 0, 1);
        $lastlocation = '';
        if ($recs) {
            foreach ($recs as $rec) {
                $lastlocation = $rec->tasklocation;
            }
        }
        return $lastlocation;
    }


    /**
     * Whether this mrproject uses "task notes" visible to teachers and students
     * @return bool whether task notes are used
     */
    public function uses_tasknotes() {
        return true;
    }

    /**
     * Whether this mrproject uses "teacher notes" visible to teachers only
     * @return bool whether task notes are used
     */
    public function uses_teachernotes() {
        return true;
    }

    /**
     * Whether this mrproject uses booking forms at all
     * @return bool whether the booking form is used
     */
    public function uses_bookingform() {
        return true;
    }

    /**
     * Whether this mrproject has booking instructions
     * @return bool whether booking instructions present
     */
    public function has_bookinginstructions() {
        return true;
    }

    /**
     * Whether this mrproject uses "student notes" filled by students at booking time
     * @return bool whether student notes are used
     */
    public function uses_studentnotes() {
        return true;
    }

    /**
     * Whether this mrproject uses student file uploads at booking time
     * @return bool whether student file uploads are used
     */
    public function uses_studentfiles() {
        return true;
    }

    /**
     * Whether this mrproject uses any data entered by the student at booking time
     * @return bool whether student data is used
     */
    public function uses_studentdata() {
        return true;
    }

    /**
     * Whether this mrproject uses captchas at booking time
     * @return bool whether captchas are used
     */
    public function uses_bookingcaptcha() {
        return  false;
    }


    /**
     * Checks whether this mrproject allows a student (in principle) to book several meetings at a time
     * @return bool whether the student can book multiple tasks
     */
    public function allows_multiple_bookings() {
        return ($this->maxbookings != 1);
    }

    /**
     * Checks whether this mrproject allows unlimited bookings per student.
     * @return bool
     */
    public function allows_unlimited_bookings() {
        return ($this->maxbookings == 0);
    }






    /* *********************** Grading *********************** */


    /**
     * Checks whether this mrproject uses grading at all.
     * @return boolean
     */
    public function uses_grades() {
        return true;
    }

    /**
     * Return grade for given user.
     * This does not take gradebook data into account.
     *
     * @param int $userid user id
     * @return int grade of this user
     */
    public function get_user_grade($userid) {
        $grades = $this->get_user_grades($userid);
        if (isset($grades[$userid])) {
            return $grades[$userid]->rawgrade;
        }
    }

    /**
     * Return grade for given user or all users. (Total grade (the mean grade))
     *
     * @param int $userid optional user id, 0 means all users
     * @return array array of grades, false if none
     */
    public function get_user_grades($userid = 0) {      //' AND a.studentid = :userid ';
        global $CFG, $DB;

        $usersql = '';
        $params = array();
        if ($userid) {
            $usersql = " AND ((a.studentid = :userid AND a.collectivetask IS NULL) OR ( (a.collectivetask LIKE '$userid+%' OR  a.collectivetask LIKE '%+$userid+%' OR a.collectivetask LIKE '%+$userid' OR a.collectivetask LIKE '$userid')  AND  a.collectivetask IS NOT NULL) ) ";
            $params['userid'] = $userid;
        }
        $params['sid'] = $this->id;     //" AND ((a.studentid = :userid AND a.collectivetask IS NULL) OR ( (a.collectivetask LIKE '$userid+%' OR  a.collectivetask LIKE '%+$userid+%' OR a.collectivetask LIKE '%+$userid' OR a.collectivetask LIKE '$userid')  AND  a.collectivetask IS NOT NULL) ) ";

        $sql = 'SELECT a.id, a.studentid, a.grade, a.collectivetask '.
               'FROM {mrproject_meeting} s JOIN {mrproject_task} a ON s.id = a.meetingid '.
               'WHERE s.mrprojectid = :sid AND a.grade IS NOT NULL'.$usersql;

        $grades = $DB->get_records_sql($sql, $params);
        $finalgrades = array();
        $gradesums = array();

        foreach ($grades as $grade) {
            if ($grade->collectivetask == null || $grade->collectivetask == '0') {
                $gradesums[$grade->studentid] = new \stdClass();
                $finalgrades[$grade->studentid] = new \stdClass();
                $finalgrades[$grade->studentid]->userid = $grade->studentid;

            } else {
                $studentids = explode('+', $grade->collectivetask);
                foreach ($studentids as $studid) {
                    $gradesums[$studid] = new \stdClass();
                    $finalgrades[$studid] = new \stdClass();
                    $finalgrades[$studid]->userid = $studid;
                }
            }
        }


        // Grading numerically.
        foreach ($grades as $grade) {     
            if ($grade->collectivetask == null || $grade->collectivetask == '0') {      //Individual evaluation
                $gradesums[$grade->studentid]->sum = @$gradesums[$grade->studentid]->sum + $grade->grade;
                $gradesums[$grade->studentid]->count = @$gradesums[$grade->studentid]->count + 1;
                $gradesums[$grade->studentid]->max = (@$gradesums[$grade->studentid]->max < $grade->grade) ?
                                                    $grade->grade : @$gradesums[$grade->studentid]->max;

            } else {        //Collective evaluation
                $studentids = explode('+', $grade->collectivetask);
                foreach ($studentids as $studid) {
                    $gradesums[$studid]->sum = @$gradesums[$studid]->sum + $grade->grade;
                    $gradesums[$studid]->count = @$gradesums[$studid]->count + 1;
                    $gradesums[$studid]->max = (@$gradesums[$studid]->max < $grade->grade) ?
                                                        $grade->grade : @$gradesums[$studid]->max;
                }
            }
        }

        // Retrieve the adequate strategy.
        foreach ($gradesums as $student => $gradeset) {
            $finalgrades[$student]->rawgrade = number_format( (float) $gradeset->sum / (float) $gradeset->count, 2);
        
        }
        
        // Include any empty grades.
        /*if ($userid > 0) {
            if (!array_key_exists($userid, $finalgrades)) {
                $finalgrades[$userid] = new \stdClass();
                $finalgrades[$userid]->userid = $userid;
                $finalgrades[$userid]->rawgrade = null;
            }
        } else {
            $gui = new \graded_users_iterator($this->get_courserec());
            // We must gracefully live through the gradesneedregrading that can be thrown by init().
            try {
                $gui->init();
                while ($userdata = $gui->next_user()) {
                    $uid = $userdata->user->id;
                    if (!array_key_exists($uid, $finalgrades)) {
                        $finalgrades[$uid] = new \stdClass();
                        $finalgrades[$uid]->userid = $uid;
                        $finalgrades[$uid]->rawgrade = null;
                    }
                }
            } catch (\moodle_exception $e) {
                if ($e->errorcode != 'gradesneedregrading') {
                    throw $e;
                }
            }
        }*/
        return $finalgrades;

    }

    /**
     * Get gradebook information for a particular student.
     * The return value is a grade_grade object.
     *
     * @param int $studentid id number of the student
     * @return \stdClass the gradebook information. May be null if no info is found.
     */
    public function get_gradebook_info($studentid) {

        $gradinginfo = grade_get_grades($this->courseid, 'mod', 'mrproject', $this->id, $studentid);
        if (!empty($gradinginfo->items)) {
            $item = $gradinginfo->items[0];
            if (isset($item->grades[$studentid])) {
                return $item->grades[$studentid];
            }
        }
        return null;
    }











    /* *********************** Loading lists of meetings *********************** */



 /* ************************************** fetch all meetings *********************************** */

    /**
     * Fetch a generic list of meetings from the database
     *
     * @param string $wherecond WHERE condition
     * @param string $havingcond HAVING condition
     * @param array $params parameters for DB query
     * @param mixed $limitfrom query limit from here
     * @param mixed $limitnum max number od records to fetch
     * @param string $orderby ORDER BY fields
     * @return meeting[]
     */
    protected function fetch_meetings($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='') {
        global $DB;
        $select = 'SELECT s.* FROM {mrproject_meeting} s';

        $where = 'WHERE mrprojectid = :mrprojectid  AND s.isdeleted = 0';   
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['mrprojectid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        if ($orderby) {
            $order = "ORDER BY $orderby, s.id";
        } else {
            $order = "ORDER BY s.id";
        }

        $sql = "$select $where $having $order";

        $meetingdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $meetings = array();
        foreach ($meetingdata as $meetingrecord) {
            $meeting = new meeting($this);
            $meeting->load_record($meetingrecord);
            $meetings[] = $meeting;
        }
        return $meetings;
    }



    /*******************************************/

    protected function fetch_meetingsheld($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='') {
        global $DB;
        $select = 'SELECT s.* FROM {mrproject_meeting} s';

        $where = 'WHERE mrprojectid = :mrprojectid AND s.meetingheld = 1';   
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['mrprojectid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        if ($orderby) {
            $order = "ORDER BY $orderby, s.id";
        } else {
            $order = "ORDER BY s.id";
        }

        $sql = "$select $where $having $order";

        $meetingdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $meetings = array();
        foreach ($meetingdata as $meetingrecord) {
            $meeting = new meeting($this);
            $meeting->load_record($meetingrecord);
            $meetings[] = $meeting;
        }
        return $meetings;
    }


 /* ************************************** fetch upcoming meetings *********************************** */

    /**
     * Fetch a generic list of meetings from the database
     *
     * @param string $wherecond WHERE condition
     * @param string $havingcond HAVING condition
     * @param array $params parameters for DB query
     * @param mixed $limitfrom query limit from here
     * @param mixed $limitnum max number od records to fetch
     * @param string $orderby ORDER BY fields
     * @return meeting[]
     */
    protected function fetch_upcoming_meetings($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='') {
        global $DB;
        $select = 'SELECT s.* FROM {mrproject_meeting} s';

        $where = 'WHERE mrprojectid = :mrprojectid AND s.meetingaccepted = 1 AND s.meetingheld = 0';   
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['mrprojectid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        if ($orderby) {
            $order = "ORDER BY $orderby, s.id";
        } else {
            $order = "ORDER BY s.id";
        }

        $sql = "$select $where $having $order";

        $meetingdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $meetings = array();
        foreach ($meetingdata as $meetingrecord) {

            if ($meetingrecord->starttime > time()) {
                $meeting = new meeting($this);
                $meeting->load_record($meetingrecord);
                $meetings[] = $meeting;
            }
        }
        return $meetings;
    }





/* ************************************** fetch available meetings *********************************** */

    /**
     * Fetch a generic list of meetings from the database
     *
     * @param string $wherecond WHERE condition
     * @param string $havingcond HAVING condition
     * @param array $params parameters for DB query
     * @param mixed $limitfrom query limit from here
     * @param mixed $limitnum max number od records to fetch
     * @param string $orderby ORDER BY fields
     * @return meeting[]
     */
    protected function fetch_available_meetings($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='') {
        global $DB;
        $select = 'SELECT s.* FROM {mrproject_meeting} s';

        $where = 'WHERE mrprojectid = :mrprojectid AND s.meetingaccepted = 0 AND s.meetingheld = 0';   
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['mrprojectid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        if ($orderby) {
            $order = "ORDER BY $orderby, s.id";
        } else {
            $order = "ORDER BY s.id";
        }

        $sql = "$select $where $having $order";

        $meetingdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $meetings = array();
        foreach ($meetingdata as $meetingrecord) {   
            if ($meetingrecord->starttime == 0 && ($meetingrecord->proposeddate1 > time() || $meetingrecord->proposeddate2 > time() || $meetingrecord->proposeddate3 > time())) {
                $meeting = new meeting($this);
                $meeting->load_record($meetingrecord);
                $meetings[] = $meeting;
            }
        }
        return $meetings;
    }


/* ************************************** fetch attended meetings *********************************** */
 

        /**
     * Fetch a generic list of meetings from the database
     *
     * @param string $wherecond WHERE condition
     * @param string $havingcond HAVING condition
     * @param array $params parameters for DB query
     * @param mixed $limitfrom query limit from here
     * @param mixed $limitnum max number od records to fetch
     * @param string $orderby ORDER BY fields
     * @return meeting[]
     */
    protected function fetch_attended_meetings($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='') {
        global $DB;
        $select = 'SELECT s.* FROM {mrproject_meeting} s';

        $where = 'WHERE mrprojectid = :mrprojectid AND s.meetingaccepted = 1';

        /*$where = 'WHERE mrprojectid = :mrprojectid AND s.meetingaccepted = 1 AND s.teacherid = :userid';
        $params['userid'] = $teacherid;*/

        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['mrprojectid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        if ($orderby) {
            $order = "ORDER BY $orderby, s.id";
        } else {
            $order = "ORDER BY s.id";
        }

        $sql = "$select $where $having $order";

        $meetingdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $meetings = array();
        foreach ($meetingdata as $meetingrecord) {
            if ($meetingrecord->starttime < time()) {
                $meeting = new meeting($this);
                $meeting->load_record($meetingrecord);
                $meetings[] = $meeting;
            }
        }
        return $meetings;
    }



/**********************************************************************/




    /**
     * Count a list of meetings (for this mrproject) in the database
     *
     * @param string $wherecond WHERE condition
     * @param array $params parameters for DB query
     * @return int
     */
    protected function count_meetings($wherecond, array $params) {
        global $DB;
        $select = 'SELECT COUNT(*) FROM {mrproject_meeting} s';

        $where = 'WHERE mrprojectid = :mrprojectid';
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['mrprojectid'] = $this->data->id;

        $sql = "$select $where";

        return $DB->count_records_sql($sql, $params);
    }


    /**
     * Subquery that counts tasks in the current meeting.
     * Only to be used in conjunction with fetch_meetings()
     *
     * @return string
     */
    protected function task_count_query() {
        return "(SELECT COUNT(a.id) FROM {mrproject_task} a WHERE a.meetingid = s.id)";
    }

    /**
     * @var int number of student parameters used in queries
     */
    protected $studparno = 0;

    /**
     * Return a WHERE condition relating to sutdents in a meeting
     *
     * @param array $params parameters for the query (by reference)
     * @param int $studentid id of student to look for
     * @param bool $mustbeattended include only attended tasks?
     * @param bool $mustbeunattended include only unattended tasks?
     * @return string
     */
    protected function student_in_meeting_condition(&$params, $studentid, $mustbeattended, $mustbeunattended) {
        $cond = "EXISTS (SELECT 1 FROM {mrproject_task} a WHERE ((a.studentid = :studentid".$this->studparno ."AND a.collectivetask IS NULL) OR ( (a.collectivetask LIKE '$studentid+%' OR  a.collectivetask LIKE '%+$studentid+%' OR a.collectivetask LIKE '%+$studentid' OR a.collectivetask LIKE '$studentid')  AND  a.collectivetask IS NOT NULL) )"
                ." and a.meetingid = s.id";

        /*$cond = "EXISTS (SELECT 1 FROM {mrproject_task} a WHERE a.studentid = :studentid".
                $this->studparno.' and a.meetingid = s.id';*/
        
                /*if ($mustbeattended) {
            $cond .= ' AND a.attended = 1';
        }*/
        
        $cond .= ")";
        $params['studentid'.$this->studparno] = $studentid;     //a.studentid = :studentid
        $this->studparno++;
        return $cond;
    }

    /***************************************************/
    protected function teacher_in_upcomingmeeting_condition(&$params, $studentid, $mustbeattended, $mustbeunattended) {
        $cond = 'EXISTS (SELECT 1 FROM {mrproject_task} a WHERE (a.studentid = :studentid'.
                $this->studparno.' and a.meetingid = s.id) OR (s.teacherid = :teacherid)';

        /*if ($mustbeattended) {
            $cond .= ' AND a.attended = 1';
        }*/
        
        $cond .= ')';
        $params['studentid'.$this->studparno] = $studentid;
        $params['teacherid'] = $studentid;
        $this->studparno++;
        return $cond;
    }

    /*protected function teacher_in_upcomingmeeting_condition(&$params, $teacherid, $mustbeattended, $mustbeunattended) {
        /*$cond = 'EXISTS (SELECT 1 FROM {mrproject_meeting} s WHERE s.teacherid = :teacherid'.
                 $this->studparno;*/

    /*   $cond = 'EXISTS (SELECT 1 FROM {mrproject_meeting} s';

        /*if ($mustbeattended) {
            $cond .= ' AND a.attended = 1';
        }*/

    /*   $cond .= ')';
        $params['teacherid'.$this->studparno] = $teacherid;
        $this->studparno++;
        return $cond;
    }*/

    /***************************************************/

    protected function teacher_in_attendedmeeting_condition(&$params, $teacherid, $mustbeattended, $mustbeunattended) {
        /*$cond = 'EXISTS (SELECT 1 FROM {mrproject_meeting} s WHERE s.teacherid = :teacherid'.
                 $this->studparno;*/

        $cond = 'EXISTS (SELECT 1 FROM {mrproject_meeting} s';

        /*if ($mustbeattended) {
            $cond .= ' AND a.attended = 1';
        }*/

        $cond .= ')';
        $params['teacherid'.$this->studparno] = $teacherid;
        $this->studparno++;
        return $cond;
    }



    /**
     * Retrieve a meeting by id.
     *
     * @param int $id
     * @return meeting
     * @uses $DB
     */
    public function get_meeting($id) {

        global $DB;

        $meetingdata = $DB->get_record('mrproject_meeting', array('id' => $id, 'mrprojectid' => $this->id), '*', MUST_EXIST);
        $meeting = new meeting($this);
        $meeting->load_record($meetingdata);
        return $meeting;
    }

    /**
     * Retrieve a list of all meetings in this mrproject
     *
     * @return meeting[]
     */
    public function get_meetings() {
        return $this->meetings->get_children();
    }

    /**
     * Retrieve the number of meetings in the mrproject
     *
     * @return int
     */
    public function get_meeting_count() {
        return $this->meetings->get_child_count();
    }

    /**
     * Load a list of all meetings, between certain limits
     *
     * @param string $limitfrom start from this entry
     * @param string $limitnum max number of entries
     * @return meeting[]
     */
    public function get_all_meetings($limitfrom='', $limitnum='') {
        return $this->fetch_meetings('', '', array(), $limitfrom, $limitnum, 's.starttime ASC');
    }






/***************************************get upcoming meetings********************************************/ 

    /**
     * Retrieves upcoming meetings booked by a student. These will be sorted by start time.
     * A meeting is "upcoming" if it as been booked but is not attended.
     *
     * @param int $studentid
     * @return meeting[]
     */
    public function get_upcoming_meetings_for_student($studentid) {

        $params = array();
        $wherecond = $this->student_in_meeting_condition($params, $studentid, false, true);
        $meetings = $this->fetch_upcoming_meetings($wherecond, '', $params, '', '', 's.starttime');

        return $meetings;
    }

/************************************/

    public function get_upcoming_meetings_for_teacher($teacherid) {

        $params = array();
        $wherecond = $this->teacher_in_upcomingmeeting_condition($params, $teacherid, false, true);
        $meetings = $this->fetch_upcoming_meetings($wherecond, '', $params, '', '', 's.starttime');

        return $meetings;
    }


/***************************************get available meetings********************************************/ 

    /**
     * Retrieves upcoming meetings booked by a student. These will be sorted by start time.
     * A meeting is "upcoming" if it as been booked but is not attended.
     *
     * @param int $studentid
     * @return meeting[]
     */
    public function get_vailable_meetings_for_student($studentid) {

        $params = array();
        $wherecond = $this->student_in_meeting_condition($params, $studentid, false, true);
        $meetings = $this->fetch_available_meetings($wherecond, '', $params, '', '', 's.starttime');

        return $meetings;
    }



/***************************************get attended meetings********************************************/ 

    /**
     * Retrieves upcoming meetings booked by a student. These will be sorted by start time.
     * A meeting is "upcoming" if it as been booked but is not attended.
     *
     * @param int $studentid
     * @return meeting[]
     */
    public function get_attended_meetings_for_student($studentid) {

        $params = array();
        $wherecond = $this->student_in_meeting_condition($params, $studentid, false, true);
        $meetings = $this->fetch_attended_meetings($wherecond, '', $params, '', '', 's.starttime');

        return $meetings;
    }

/***********************************************/

    public function get_attended_meetings_for_teacher($studentid) {

        $params = array();
        $wherecond = $this->teacher_in_attendedmeeting_condition($params, $studentid, false, true);
        $meetings = $this->fetch_attended_meetings($wherecond, '', $params, '', '', 's.starttime');
        //$meetings = $this->fetch_attended_meetings('s.teacherid='.$studentid, '', $params, '', '', 's.starttime');


        return $meetings;
    }



    /**
     * Does htis mrproject have a meeting where a certain student is booked?
     *
     * @param int $studentid student to look for
     * @param bool $mustbeattended include only attended meetings
     * @param bool $mustbeunattended include only unattended meetings
     * @return boolean
     */
    public function has_meetings_for_student($studentid, $mustbeattended, $mustbeunattended) {
        $params = array();
        $where = $this->student_in_meeting_condition($params, $studentid, $mustbeattended, $mustbeunattended);
        $cnt = $this->count_meetings($where, $params);
        return $cnt > 0;
    }


    /**
     * Does this mrproject contain any meetings where a certain group has booked?
     *
     * @param int $groupid the group to look for
     * @param bool $mustbeattended include only attended meetings
     * @param bool $mustbeunattended include only unattended meetings
     * @return boolean
     */
    public function has_meetings_booked_for_group($groupid, $mustbeattended = false, $mustbeunattended = false) {
        global $DB;
        $attendcond = '';
        /*if ($mustbeattended) {
            $attendcond .= " AND a.attended = 1";
        }
        if ($mustbeunattended) {
            $attendcond .= " AND a.attended = 0";
        }*/
        $sql = "SELECT COUNT(*)
                  FROM {mrproject_meeting} s
                  JOIN {mrproject_task} a ON a.meetingid = s.id
                  JOIN {groups_members} gm ON a.studentid = gm.userid
                 WHERE s.mrprojectid = :mrprojectid
                       AND gm.groupid = :groupid
                       $attendcond";
        $params = array('mrprojectid' => $this->id, 'groupid' => $groupid);
        return $DB->count_records_sql($sql, $params) > 0;
    }


    /**
     * retrieves meetings without any task made
     *
     * @param int $teacherid if given, will return only meetings for this teacher
     * @return meeting[] list of unused meetings
     */
    public function get_meetings_without_task($teacherid = 0) {
        $wherecond = '('.$this->task_count_query().' = 0)';
        $params = array();
        if ($teacherid > 0) {
            list($twhere, $params) = $this->meetings_for_teacher_cond($teacherid, 0, false);
            $wherecond .= " AND $twhere";
        }
        $meetings = $this->fetch_meetings($wherecond, '', $params);
        return $meetings;
    }

    /**
     * Retrieve a list of meetings for a certain teacher or group of teachers
     * @param int $teacherid id of teacher to look for, can be 0
     * @param int $groupid find only meetings with a teacher in this group, can be 0
     * @param bool|int $timerange include only meetings in the future/past?
     *            Accepted values are: 0=all, 1=future, 2=past, false=all, true=past
     * @return mixed SQL condition and parameters
     */
    protected function meetings_for_teacher_cond($teacherid, $groupid, $timerange) {
        $wheres = array();
        $params = array();
        if ($teacherid > 0) {
            $wheres[] = "teacherid = :tid";
            $params['tid'] = $teacherid;
        }
        if ($groupid > 0) {
            $wheres[] = "EXISTS (SELECT 1 FROM {groups_members} gm WHERE gm.groupid = :gid AND gm.userid = s.teacherid)";
            $params['gid'] = $groupid;
        }
        if ($timerange === true || $timerange == 2) {
            $wheres[] = "s.starttime < ".strtotime('now');
        } else if ($timerange == 1) {
            $wheres[] = "s.starttime >= ".strtotime('now');
        }
        $where = implode(" AND ", $wheres);
        return array($where, $params);
    }

    /**
     * Count the number of meetings available to a teacher or group of teachers
     *
     * @param int $teacherid id of teacher to look for, can be 0
     * @param int $groupid find only meetings with a teacher in this group, can be 0
     * @param bool $inpast include only meetings in the past?
     * @return int
     */
    public function count_meetings_for_teacher($teacherid, $groupid = 0, $inpast = false) {
        list($where, $params) = $this->meetings_for_teacher_cond($teacherid, $groupid, $inpast);
        return $this->count_meetings($where, $params);
    }

    /**
     * Retrieve meetings available to a teacher or group of teachers
     *
     * @param int $teacherid id of teacher to look for, can be 0
     * @param int $groupid find only meetings with a teacher in this group, can be 0
     * @param mixed $limitfrom start from this entry
     * @param mixed $limitnum max number of entries
     * @param int $timerange whether to include past/future meetings (0=all, 1=future, 0=past)
     * @return meeting[]
     */
    public function get_meetings_for_teacher($teacherid, $groupid = 0, $limitfrom = '', $limitnum = '', $timerange = 0) {
        list($where, $params) = $this->meetings_for_teacher_cond($teacherid, $groupid, $timerange);
        return $this->fetch_meetings($where, '', $params, $limitfrom, $limitnum, 's.starttime ASC, s.duration ASC, s.teacherid');
    }

    /*****************************************/

    //Retrieve meetings held
    public function get_meetingsheld_for_teacher($teacherid, $groupid = 0, $limitfrom = '', $limitnum = '', $timerange = 0) {
        list($where, $params) = $this->meetings_for_teacher_cond($teacherid, $groupid, $timerange);
        return $this->fetch_meetings($where, '', $params, $limitfrom, $limitnum, 's.starttime ASC, s.duration ASC, s.teacherid');
    }

    /**
     * Retrieve meetings available to a group of teachers
     *
     * @param int $groupid find only meetings with a teacher in this group
     * @param mixed $limitfrom start from this entry
     * @param mixed $limitnum max number of entries
     * @param int $timerange whether to include past/future meetings (0=all, 1=future, 0=past)
     * @return meeting[]
     */
    public function get_meetings_for_group($groupid, $limitfrom = '', $limitnum = '', $timerange = 0) {
        list($where, $params) = $this->meetings_for_teacher_cond(0, $groupid, $timerange);
        return $this->fetch_meetingsheld($where, '', $params, $limitfrom, $limitnum, 's.starttime ASC, s.duration ASC, s.teacherid');
    }




    


    /* ************** End of meeting retrieveal routines ******************** */


    /**
     * Returns an array of meetings that would overlap with this one.
     *
     * @param int $starttime the start of time meeting as a timestamp
     * @param int $endtime end of time meeting as a timestamp
     * @param int $teacher the id of the teacher constraint, or 0 for "all teachers"
     * @param int $student the id of the student constraint, or 0 for "all students"
     * @param int $others selects where to search for conflicts, [MRPROJECT_SELF, MRPROJECT_OTHERS, MRPROJECT_ALL]
     * @param int $excludemeeting exclude meeting with this id (useful to exclude present meeting when saving)
     * @uses $DB
     * @return array conflicting meetings
     */
    public function get_conflicts($starttime, $endtime, $teacher = 0, $student = 0,
                           $others = MRPROJECT_SELF, $excludemeeting = 0) {
        global $DB;

        $params = array();

        $meetingscope = ($excludemeeting == 0) ? "" : "sl.id != :excludemeeting AND ";
        $params['excludemeeting'] = $excludemeeting;

        switch ($others) {
            case MRPROJECT_SELF:
                $mrprojectscope = "sl.mrprojectid = :mrprojectid AND ";
                $params['mrprojectid'] = $this->id;
                break;
            case MRPROJECT_OTHERS:
                $mrprojectscope = "sl.mrprojectid != :mrprojectid AND ";
                $params['mrprojectid'] = $this->id;
                break;
            default:
                $mrprojectscope = '';
        }
        if ($teacher != 0) {
            $teacherscope = "sl.teacherid = :teacherid AND ";
            $params['teacherid'] = $teacher;
        } else {
            $teacherscope = "";
        }

        $studentjoin = ($student != 0) ? "JOIN {mrproject_task} a ON a.meetingid = sl.id AND a.studentid = :studentid " : '';
        $params['studentid'] = $student;

        $timeclause = "( (sl.starttime <= :starttime1 AND sl.starttime + sl.duration * 60 > :starttime2) OR
                         (sl.starttime < :endtime1 AND sl.starttime + sl.duration * 60 >= :endtime2) OR
                         (sl.starttime >= :starttime3 AND sl.starttime + sl.duration * 60 <= :endtime3) )
                       AND sl.starttime + sl.duration * 60 > :nowtime";
        $params['starttime1'] = $starttime;
        $params['starttime2'] = $starttime;
        $params['starttime3'] = $starttime;
        $params['endtime1'] = $endtime;
        $params['endtime2'] = $endtime;
        $params['endtime3'] = $endtime;
        $params['nowtime'] = time();

        $sql = "SELECT sl.*,
                       s.name AS mrprojectname,
                       (CASE WHEN (s.id = :thisid) THEN 1 ELSE 0 END) AS isself,
                       c.id AS courseid, c.shortname AS courseshortname, c.fullname AS coursefullname,
                       (SELECT COUNT(*) FROM {mrproject_task} ac WHERE sl.id = ac.meetingid) AS numstudents
                  FROM {mrproject_meeting} sl
                       $studentjoin
                  JOIN {mrproject} s ON sl.mrprojectid = s.id
                  JOIN {course} c ON c.id = s.course
                 WHERE $meetingscope $mrprojectscope $teacherscope $timeclause
              ORDER BY sl.starttime ASC, sl.duration ASC";

        $params['thisid'] = $this->id;

        $conflicting = $DB->get_records_sql($sql, $params);

        return $conflicting;
    }

    /**
     * retrieves a task and the corresponding meeting
     *
     * @param mixed $taskid
     * @return mixed List of (meeting, mrproject_task)
     */
    public function get_meeting_task($taskid) {
        global $DB;

        $meetingid = $DB->get_field('mrproject_task', 'meetingid', array('id' => $taskid));
        $meeting = $this->get_meeting($meetingid);
        $app = $meeting->get_task($taskid);

        return array($meeting, $app);
    }

    /**
     * Retrieves all tasks of a student. These will be sorted by start time.
     *
     * @param int $studentid
     * @return array of task objects
     */
    public function get_tasks_for_student($studentid) {

        global $DB;

        $sql = "SELECT a.id as taskid, s.*
                  FROM {mrproject_meeting} s, {mrproject_task} a
                 WHERE s.mrprojectid = :mrprojectid
                       AND s.id = a.meetingid
                       AND a.studentid = :studid
              ORDER BY s.starttime";
        $params = array('mrprojectid' => $this->id, 'studid' => $studentid);

        $meetingrecs = $DB->get_records_sql($sql, $params);

        $tasks = array();
        foreach ($meetingrecs as $rec) {
            $meeting = new meeting($this);
            $meeting->load_record($rec);
            $appointrec = $DB->get_record('mrproject_task', array('id' => $rec->taskid), '*', MUST_EXIST);
            $task = new task($meeting);
            $task->load_record($appointrec);
            $tasks[] = $task;
        }

        return $tasks;
    }



    //Retrieves all tasks held of a student.
    public function get_tasksheld_for_student($studentid) {

        global $DB;


        $sql = "SELECT a.id as taskid, s.*
                  FROM {mrproject_meeting} s, {mrproject_task} a
                 WHERE s.mrprojectid = :mrprojectid
                       AND s.id = a.meetingid
                       AND ((a.studentid = :studid AND a.collectivetask IS NULL) OR ( (a.collectivetask LIKE '$studentid+%' OR  a.collectivetask LIKE '%+$studentid+%' OR a.collectivetask LIKE '%+$studentid' OR a.collectivetask LIKE '$studentid')  AND  a.collectivetask IS NOT NULL) )
                       AND s.meetingheld = 1
              ORDER BY s.starttime";
        $params = array('mrprojectid' => $this->id, 'studid' => $studentid, 'studentid' => $studentid);

        $meetingrecs = $DB->get_records_sql($sql, $params);      //AND ((a.studentid = :studid AND a.collectivetask IS NULL) OR ( ... STRING_SPLIT(a.collectivetask, '+')  AND  a.collectivetask IS NOT NULL) )

        $tasks = array();
        foreach ($meetingrecs as $rec) {
            $meeting = new meeting($this);
            $meeting->load_record($rec);
            $appointrec = $DB->get_record('mrproject_task', array('id' => $rec->taskid), '*', MUST_EXIST);
            $task = new task($meeting);
            $task->load_record($appointrec);
            $tasks[] = $task;
        }

        return $tasks;
    }

    /**
     * Create a new meeting relating to this mrproject.
     *
     * @return meeting
     */
    public function create_meeting() {
        return $this->meetings->create_child();
    }

    /**
     * Computes how many tasks a student can still book.
     *
     * @param int $studentid
     * @param bool $includechangeable include tasks that are booked but can still be changed?
     * @return int the number of bookable or changeable tasks, possibly 0; returns -1 if unlimited.
     */
    public function count_bookable_tasks($studentid, $includechangeable = true) {
        global $DB;

        // Find how many meetings have already been booked.
        $sql = 'SELECT COUNT(*) FROM {mrproject_meeting} s'
              .' JOIN {mrproject_task} a ON s.id = a.meetingid'
              .' WHERE s.mrprojectid = :mrprojectid AND a.studentid=:studentid';
        if ($this->mrprojectmode == 'onetime') {
            if ($includechangeable) {
                $sql .= ' AND s.starttime <= :cutofftime';
            }
            //$sql .= ' AND a.attended = 0';
        } else if ($includechangeable) {
            $sql .= ' AND (s.starttime <= :cutofftime)';
        }
        $params = array('mrprojectid' => $this->id, 'studentid' => $studentid, 'cutofftime' => time());

        $booked = $DB->count_records_sql($sql, $params);
        $allowed = $this->maxbookings;

        if ($allowed == 0) {
            return -1;
        } else if ($booked >= $allowed) {
            return 0;
        } else {
            return $allowed - $booked;
        }

    }

    /**
     * Get list of teachers that have meetings in this mrproject
     *
     * @return \stdClass[]
     */
    public function get_teachers() {
        global $DB;
        $sql = "SELECT DISTINCT u.*
                  FROM {mrproject_meeting} s, {user} u
                 WHERE s.teacherid = u.id
                       AND mrprojectid = ?";
        $teachers = $DB->get_records_sql($sql, array($this->id));
        return $teachers;
    }

    /**
     * Get list of available users with a certain capability.
     *
     * @param string $capability the capabilty to look for
     * @param int|array $groupids - group id or array of group ids; if set, will only return users who are in these groups.
     *                             (for legacy processing, allow also group objects and arrays of these)
     * @return \stdClass[] array of moodle user records
     */
    protected function get_available_users($capability, $groupids = 0) {

        // If full group objects are given, reduce the array to only group ids.
        if (is_array($groupids) && is_object(array_values($groupids)[0])) {
            $groupids = array_keys($groupids);
        } else if (is_object($groupids)) {
            $groupids = $groupids->id;
        }

        // Legacy: empty string amounts to no group filter.
        if ($groupids === '') {
            $groupids = 0;
        }

        $users = array();
        if (is_integer($groupids)) {
            $users = get_enrolled_users($this->get_context(), $capability, $groupids, 'u.*', null, 0, 0, true);

        } else if (is_array($groupids)) {
            foreach ($groupids as $groupid) {
                $groupusers = get_enrolled_users($this->get_context(), 'mod/mrproject:appoint', $groupid,
                                                 'u.*', null, 0, 0, true);
                foreach ($groupusers as $user) {
                    if (!array_key_exists($user->id, $users)) {
                        $users[$user->id] = $user;
                    }
                }
            }
        }

        $modinfo = get_fast_modinfo($this->courseid);
        $info = new \core_availability\info_module($modinfo->get_cm($this->cmid));
        $users = $info->filter_user_list($users);

        return $users;
    }

    /**
     * Get list of available students (i.e., users that can book meetings)
     *
     * @param mixed $groupids - group id or array of group ids; if set, will only return users who are in these groups.
     * @return \stdClass[] array of moodle user records
     */
    public function get_available_students($groupids = 0) {

        return $this->get_available_users('mod/mrproject:appoint', $groupids);
    }

    /**
     * Get list of available teachers (i.e., users that can offer meetings)
     *
     * @param mixed $groupids - only return users who are in this group.
     * @return \stdClass array of moodle user records
     */
    public function get_available_teachers($groupids = 0) {

        return $this->get_available_users('mod/mrproject:attend', $groupids);
    }

    /**
     * Checks whether there are any possible teachers for the mrproject
     *
     * @return bool whether teachers are present
     */
    public function has_available_teachers() {
        $teachers = $this->get_available_teachers();
        return count($teachers) > 0;
    }

    /**
     * Get a list of students that can still make an task.
     *
     * @param mixed $groups single group or array of groups - only return
     *            users who are in one of these group(s).
     * @param int $cutoff if the number of students in the course is more than this limit,
     *            the routine will return the number of students rather than a list
     *            (this is for performance reasons).
     * @param bool $onlymandatory include only students who _must_ (rather than _can_) make
     *            another task. This matters onyl in mrprojects where students can make
     *            unlimited tasks.
     * @return int|array of moodle user records; or int 0 if there are no students in the course;
     *            or the number of students if there are too many students. Array keys are student ids.
     */
    public function get_students_for_scheduling($groups = '', $cutoff = 0, $onlymandatory = false) {
        $studs = $this->get_available_students($groups);
        if (($cutoff > 0 && count($studs) > $cutoff) || count($studs) == 0) {
            return count($studs);
        }
        $schedstuds = array();
        foreach ($studs as $stud) {
            $include = false;
            if ($this->allows_unlimited_bookings()) {
                $include = !$onlymandatory || !$this->has_meetings_for_student($stud->id, false, false);
            } else {
                $include = ($this->count_bookable_tasks($stud->id, false) != 0);
            }
            if ($include) {
                $schedstuds[$stud->id] = $stud;
            }
        }
        return $schedstuds;
    }


    /**
     * Delete an task, and do whatever is needed
     *
     * @param int $taskid
     * @uses $DB
     */
    public function delete_task($taskid) {
        global $DB;

        if (!$oldrecord = $DB->get_record('mrproject_task', array('id' => $taskid))) {
            return;
        }

        $meeting = $this->get_meeting($oldrecord->meetingid);
        $task = $meeting->get_task($taskid);

        // Delete the task.
        $meeting->remove_task($task);
        $meeting->save();
    }

    /**
     * Frees all empty meetings that are in the past, hence no longer bookable.
     * This applies to all mrprojects in the system.
     *
     * @uses $CFG
     * @uses $DB
     */
    public static function free_late_unused_meetings() {
        global $DB;

        $sql = "SELECT DISTINCT s.id
                           FROM {mrproject_meeting} s
                LEFT OUTER JOIN {mrproject_task} a ON s.id = a.meetingid
                          WHERE a.studentid IS NULL
                            AND starttime < ?";
        $now = time();
        $todelete = $DB->get_records_sql($sql, array($now), 0, 1000);
        if ($todelete) {
            list($usql, $params) = $DB->get_in_or_equal(array_keys($todelete));
            $DB->delete_records_select('mrproject_meeting', " id $usql ", $params);
        }
    }

    

    /**
     * Format a date in the current user's timezone.
     * @param int $date a timestamp
     * @return string printable date
     */
    public static function userdate($date) {
        if ($date == 0) {
            return '';
        } else {
            return userdate($date, get_string('strftimedaydate'));
        }
    }


    /************************************/
    
    public static function usershortdate($date) {
        if ($date == 0) {
            return '';
        } else {
            return userdate($date, get_string('strftimedatefullshort', 'core_langconfig'));
        }
    }


    /**
     * Format a time in the current user's timezone.
     * @param int $date a timestamp
     * @return string printable time
     */
    public static function usertime($date) {
        if ($date == 0) {
            return '';
        } else {
            $timeformat = get_user_preferences('calendar_timeformat'); // Get user config.
            
            $timeformat = get_config(null, 'calendar_site_timeformat'); // Get calendar config if above not exist.
            
            return userdate($date, $timeformat);
        }
    }

}
