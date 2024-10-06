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
 * This file contains the definition for the renderable classes for the assignment
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\model\meeting;
use \mod_mrproject\model\task;





/**********************************Upcoming meetings tab: Upcoming meetings Table *****************************************/


/**
 * This class represents a table of meetings associated with one student
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_meeting_table implements renderable {

    /** @var array list of meetings in this table */
    public $meetings = array();

    /** @var mrproject the mrproject that the meetings are in */
    public $mrproject;

    /** @var bool whether to show grades in the table */
    public $showgrades;

    /** @var bool whether any meeting in the table has other students to show */
    public $hasotherstudents = false;

    /** @var bool whether to show start/end time of the meetings */
    public $showmeeting = true;

    /** @var bool whether to show the attended/not attended icons */
    public $showattended = false;

    /** @var bool whether to show action buttons (for cancelling) */
    public $showactions = true;

    /** @var bool whether to show (confidential) teacher notes */
    public $showteachernotes = false;

    /** @var bool whether to show a link to edit tasks */
    public $showeditlink = false;

    /** @var bool whether to show the location of the task */
    public $showlocation = true;

    /** @var bool whether to show the students in the meeting */
    public $showstudent = false;

    /** @var moodle_url|null action URL for buttons */
    public $actionurl;


    /**
     * Create a new meeting table.
     *
     * @param mrproject $mrproject the mrproject in which the meetings are
     * @param bool $showgrades whether to show grades
     * @param moodle_url|null $actionurl action URL for buttons
     */
    public function __construct(mrproject $mrproject, $showgrades=true, $actionurl = null) {
        $this->mrproject = $mrproject;
        $this->showgrades = $showgrades && $mrproject->uses_grades();
        $this->actionurl = $actionurl;
    }

    
    /**
     * Add a meeting to the table.
     *
     * @param meeting $meetingmodel the meeting to be added
     * @param task $taskmodel the corresponding task
     * @param array $otherstudents any other students in the same meeting
     * @param bool $cancancel whether the user can canel the task
     * @param bool $canedit whether the user can edit the meeting/task
     * @param bool $canview whether the user can view the task
     */
    public function add_meeting(meeting $meetingmodel, task $taskmodel = null,
                             $otherstudents, $currentuserid = null, $groupid = null, $cancancel = false, $canedit = false, $canview = false) {

        if ($meetingmodel->starttime > time()) {

            $meeting = new stdClass();
            $meeting->meetingid = $meetingmodel->id;
                
            if ($taskmodel != null) {
                $meeting->student = $taskmodel->student;
            }
            $meeting->starttime = $meetingmodel->starttime;
            $meeting->endtime = $meetingmodel->endtime;
            $meeting->location = $meetingmodel->tasklocation;
            $meeting->meetingnote = $meetingmodel->meetingpurpose;
            $meeting->meetingnoteformat = $meetingmodel->meetingpurposeformat;
            $meeting->teacher = $meetingmodel->get_teacher();

            if ($taskmodel != null) {
                $meeting->taskid = $taskmodel->id;
                if ($this->mrproject->uses_tasknotes()) {
                    $meeting->tasknote = $taskmodel->tasknote;
                    $meeting->tasknoteformat = $taskmodel->tasknoteformat;
                }
                if ($this->mrproject->uses_teachernotes() && $this->showteachernotes) {
                    $meeting->teachernote = $taskmodel->teachernote;
                    $meeting->teachernoteformat = $taskmodel->teachernoteformat;
                }
            }
            $meeting->otherstudents = $otherstudents;
            $meeting->currentuserid = $currentuserid;    //Current user id
            $meeting->groupid = $groupid;  //Current group id
            $meeting->cancancel = $cancancel;
            $meeting->canedit = $canedit;
            $meeting->canview = $canview;
            if ($this->showgrades) {
                if ($taskmodel != null) {
                    $meeting->grade = $taskmodel->grade;
                }
            }
            $this->showactions = $this->showactions || $cancancel;
            $this->hasotherstudents = $this->hasotherstudents || (bool) $otherstudents;

            $this->meetings[] = $meeting;
        }
    }

    

}



/*************************************** Available meetings ********************************************/


/**
 * This class represents a table of meetings associated with one student
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_availablemeeting_table implements renderable {

    /** @var array list of meetings in this table */
    public $meetings = array();

    /** @var mrproject the mrproject that the meetings are in */
    public $mrproject;

    /** @var bool whether to show grades in the table */
    public $showgrades;

    /** @var bool whether any meeting in the table has other students to show */
    public $hasotherstudents = false;

    /** @var bool whether to show start/end time of the meetings */
    public $showmeeting = true;

    /** @var bool whether to show the attended/not attended icons */
    public $showattended = false;

    /** @var bool whether to show action buttons (for cancelling) */
    public $showactions = true;

    /** @var bool whether to show (confidential) teacher notes */
    public $showteachernotes = false;

    /** @var bool whether to show a link to edit tasks */
    public $showeditlink = false;

    /** @var bool whether to show the location of the task */
    public $showlocation = true;

    /** @var bool whether to show the students in the meeting */
    public $showstudent = false;

    /** @var moodle_url|null action URL for buttons */
    public $actionurl;


    /**
     * Create a new meeting table.
     *
     * @param mrproject $mrproject the mrproject in which the meetings are
     * @param bool $showgrades whether to show grades
     * @param moodle_url|null $actionurl action URL for buttons
     */
    public function __construct(mrproject $mrproject, $showgrades=true, $actionurl = null) {
        $this->mrproject = $mrproject;
        $this->showgrades = $showgrades && $mrproject->uses_grades();
        $this->actionurl = $actionurl;
    }

    
    /**
     * Add a meeting to the table.
     *
     * @param meeting $meetingmodel the meeting to be added
     * @param task $taskmodel the corresponding task
     * @param array $otherstudents any other students in the same meeting
     * @param bool $cancancel whether the user can canel the task
     * @param bool $canedit whether the user can edit the meeting/task
     * @param bool $canview whether the user can view the task
     */
    public function add_meeting(meeting $meetingmodel, task $taskmodel,
                             $otherstudents, $currentuserid = null, $groupid = null, $cancancel = false, $canedit = false, $canview = false) {

        if ($meetingmodel->starttime == 0 && ($meetingmodel->proposeddate1 > time() || $meetingmodel->proposeddate2 > time() || $meetingmodel->proposeddate3 > time())) {
            $meeting = new stdClass();
            $meeting->meetingid = $meetingmodel->id;
            if ($this->showstudent) {
                $meeting->student = $taskmodel->student;
            }
            $meeting->starttime = $meetingmodel->starttime;
            $meeting->proposeddate1 = $meetingmodel->proposeddate1;
            $meeting->proposeddate2 = $meetingmodel->proposeddate2;
            $meeting->proposeddate3 = $meetingmodel->proposeddate3;
            $meeting->duration = $meetingmodel->duration;
            $meeting->endtime = $meetingmodel->endtime;
            //$meeting->attended = $taskmodel->attended;
            $meeting->location = $meetingmodel->tasklocation;
            $meeting->meetingnote = $meetingmodel->meetingpurpose;
            $meeting->meetingnoteformat = $meetingmodel->meetingpurposeformat;
            $meeting->teacher = $meetingmodel->get_teacher();
            $meeting->taskid = $taskmodel->id;
            if ($this->mrproject->uses_tasknotes()) {
                $meeting->tasknote = $taskmodel->tasknote;
                $meeting->tasknoteformat = $taskmodel->tasknoteformat;
            }
            if ($this->mrproject->uses_teachernotes() && $this->showteachernotes) {
                $meeting->teachernote = $taskmodel->teachernote;
                $meeting->teachernoteformat = $taskmodel->teachernoteformat;
            }
            $meeting->otherstudents = $otherstudents;
            $meeting->currentuserid = $currentuserid;    //Current user id
            $meeting->groupid = $groupid;  //Current group id
            $meeting->cancancel = $cancancel;
            $meeting->canedit = $canedit;
            $meeting->canview = $canview;
            if ($this->showgrades) {
                $meeting->grade = $taskmodel->grade;
            }
            $this->showactions = $this->showactions || $cancancel;
            $this->hasotherstudents = $this->hasotherstudents || (bool) $otherstudents;

            $this->meetings[] = $meeting;
        }
    }

    

}



/********************************** Attended meetings Table *****************************************/


/**
 * This class represents a table of meetings associated with one student
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_attendedmeeting_table implements renderable {

    /** @var array list of meetings in this table */
    public $meetings = array();

    /** @var mrproject the mrproject that the meetings are in */
    public $mrproject;

    /** @var bool whether to show grades in the table */
    public $showgrades;

    /** @var bool whether any meeting in the table has other students to show */
    public $hasotherstudents = false;

    /** @var bool whether to show start/end time of the meetings */
    public $showmeeting = true;

    /** @var bool whether to show the attended/not attended icons */
    public $showattended = false;

    /** @var bool whether to show action buttons (for cancelling) */
    public $showactions = true;

    /** @var bool whether to show (confidential) teacher notes */
    public $showteachernotes = false;

    /** @var bool whether to show a link to edit tasks */
    public $showeditlink = false;

    /** @var bool whether to show the location of the task */
    public $showlocation = true;

    /** @var bool whether to show the students in the meeting */
    public $showstudent = false;

    /** @var moodle_url|null action URL for buttons */
    public $actionurl;

    public $actionurl2;


    /**
     * Create a new meeting table.
     *
     * @param mrproject $mrproject the mrproject in which the meetings are
     * @param bool $showgrades whether to show grades
     * @param moodle_url|null $actionurl action URL for buttons
     */
    public function __construct(mrproject $mrproject, $showgrades=true, $actionurl2 = null) {
        $this->mrproject = $mrproject;
        $this->showgrades = $showgrades && $mrproject->uses_grades();
        $this->actionurl2 = $actionurl2;
    }

    
    /**
     * Add a meeting to the table.
     *
     * @param meeting $meetingmodel the meeting to be added
     * @param task $taskmodel the corresponding task
     * @param array $otherstudents any other students in the same meeting
     * @param bool $cancancel whether the user can canel the task
     * @param bool $canedit whether the user can edit the meeting/task
     * @param bool $canview whether the user can view the task
     */
    public function add_meeting(meeting $meetingmodel, task $taskmodel = null,
                             $otherstudents, $currentuserid = null, $groupid = null, $cancancel = false, $canedit = false, $canview = false) {

        if ($meetingmodel->starttime < time()) {
            $meeting = new stdClass();
            $meeting->meetingid = $meetingmodel->id;
            
            if ($taskmodel != null) {
                $meeting->student = $taskmodel->student;
            }
           
            $meeting->starttime = $meetingmodel->starttime;
            $meeting->endtime = $meetingmodel->endtime;
            $meeting->location = $meetingmodel->tasklocation;
            $meeting->meetingnote = $meetingmodel->meetingpurpose;
            $meeting->meetingnoteformat = $meetingmodel->meetingpurposeformat;

            /*$meeting->teacher = new \stdClass();
            $meeting->teacher->firstname = get_string('students', 'mrproject');*/

            $meeting->teacher = $meetingmodel->get_teacher();

            /*if ($meetingmodel->teacherid != null && $meetingmodel->teacherid != 0) {
                $meeting->teacher = $meetingmodel->get_teacher();
            }*/


            if ($taskmodel != null) {
                $meeting->taskid = $taskmodel->id;
                if ($this->mrproject->uses_tasknotes()) {
                    $meeting->tasknote = $taskmodel->tasknote;
                    $meeting->tasknoteformat = $taskmodel->tasknoteformat;
                }
                if ($this->mrproject->uses_teachernotes() && $this->showteachernotes) {
                    $meeting->teachernote = $taskmodel->teachernote;
                    $meeting->teachernoteformat = $taskmodel->teachernoteformat;
                }
            }
            $meeting->otherstudents = $otherstudents;
            $meeting->currentuserid = $currentuserid;    //Current user id
            $meeting->groupid = $groupid;  //Current group id
            $meeting->cancancel = $cancancel;   //isnotstudent = cancancel
            $meeting->canedit = $canedit;
            $meeting->canview = $canview;
            if ($this->showgrades) {
                if ($taskmodel != null) {
                    $meeting->grade = $taskmodel->grade;
                }
            }
            $this->showactions = $this->showactions || $cancancel;
            $this->hasotherstudents = $this->hasotherstudents || (bool) $otherstudents;

            $this->meetings[] = $meeting;
        }
    }

    

}





/**********************************My meeting tab: Students list in a meeting *****************************************/

/**
 * This class represents a list of students in a meeting, to be displayed "inline" within a larger table
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_student_list implements renderable {

    /** @var array list of students to be displayed */
    public $students = array();

    /** @var mrproject the mrproject in whose context the list is */
    public $mrproject;

    /** @var bool whether tho show the grades of the students */
    public $showgrades;

    /** @var bool whether to show students in an expandable list */
    public $expandable = true;

    /** @var bool whether the expandable list is already expanded */
    public $expanded = true;

    /** @var bool whether tasks can be edited */
    public $editable = false;

    /** @var string name of the checkbox group used for marking students as seen */
    public $checkboxname = '';

    /** @var string text of the edit button */
    public $buttontext = '';

    /** @var moodle_url|null action URL for buttons */
    public $actionurl = null;

    /** @var bool whether to include links to individual tasks */
    public $linktask = false;


    public $group = '';

    public $groupid = null;


    /**
     * Create a new student list.
     *
     * @param mrproject $mrproject the mrproject in whose context the list is
     * @param bool $showgrades whether tho show grades of students
     */
    public function __construct(mrproject $mrproject, $group,  $groupid = null, $showgrades = true) {
        $this->mrproject = $mrproject;
        $this->group = $group;
        $this->groupid = $groupid;
        $this->showgrades = $showgrades;
    }



    /**
     * Add a student to the list.
     *
     * @param task $task the task to add (one student)
     * @param bool $highlight whether this entry is highlighted
     * @param bool $checked whether the "seen" tickbox is checked
     * @param bool $showgrade whether to show a grade with this entry
     * @param bool $showstudprovided whether to show an icon for student-provided files
     * @param bool $editattended whether to make the attended tickbox editable
     */
    public function add_student(task $task, $highlight, $checked = false,
                                $showgrade = true, $showstudprovided = false, $editattended = false) {
        

        global $DB;

        if ($task->collectivetask == null || $task->collectivetask == '0') {     //Individual task
            $student = new stdClass();
            $student->user = $task->get_student();

            if ($this->showgrades && $showgrade) {
                $student->grade = $task->grade;
            } else {
                $student->grade = null;
            }
            $student->highlight = $highlight;
            $student->checked = $checked;
            //$student->editattended = $editattended;
            $student->entryid = $task->id;
            $mrproject = $task->get_mrproject();
            $student->notesprovided = false;
            $student->filesprovided = 0;
            if ($showstudprovided) {
                $student->notesprovided = $mrproject->uses_studentnotes() && $task->has_studentnotes();
                if ($mrproject->uses_studentfiles()) {
                    $student->filesprovided = $task->count_studentfiles();
                }
            }
            $this->students[] = $student;

        } else {      //Collective task
            $studentids = explode('+' ,$task->collectivetask);
            foreach ($studentids as $studentid) {
                $student = new stdClass();

                //$student->user = $task->get_student();
                $student->user = $DB->get_record('user', array('id' => intval($studentid)), '*', MUST_EXIST);

                if ($this->showgrades && $showgrade) {
                    $student->grade = $task->grade;
                } else {
                    $student->grade = null;
                }
                $student->highlight = $highlight;
                $student->checked = $checked;
                //$student->editattended = $editattended;
                $student->entryid = $task->id;
                $mrproject = $task->get_mrproject();
                $student->notesprovided = false;
                $student->filesprovided = 0;
                if ($showstudprovided) {
                    $student->notesprovided = $mrproject->uses_studentnotes() && $task->has_studentnotes();
                    if ($mrproject->uses_studentfiles()) {
                        $student->filesprovided = $task->count_studentfiles();
                    }
                }
                $this->students[] = $student;
                
            }
        }
        
    }

    
}




/********************************** All tasks of this student *****************************************/


/**
 * This class represents a table of meetings associated with one student
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_task_table implements renderable {

    /** @var array list of meetings in this table */
    public $meetings = array();

    /** @var mrproject the mrproject that the meetings are in */
    public $mrproject;

    /** @var bool whether to show grades in the table */
    public $showgrades;

    /** @var bool whether any meeting in the table has other students to show */
    public $hasotherstudents = false;

    /** @var bool whether to show start/end time of the meetings */
    public $showmeeting = true;

    /** @var bool whether to show the attended/not attended icons */
    public $showattended = false;

    /** @var bool whether to show action buttons (for cancelling) */
    public $showactions = true;

    /** @var bool whether to show (confidential) teacher notes */
    public $showteachernotes = false;

    /** @var bool whether to show a link to edit tasks */
    public $showeditlink = false;

    /** @var bool whether to show the location of the task */
    public $showlocation = true;

    /** @var bool whether to show the students in the meeting */
    public $showstudent = false;

    /** @var moodle_url|null action URL for buttons */
    public $actionurl;


    /**
     * Create a new meeting table.
     *
     * @param mrproject $mrproject the mrproject in which the meetings are
     * @param bool $showgrades whether to show grades
     * @param moodle_url|null $actionurl action URL for buttons
     */
    public function __construct(mrproject $mrproject, $showgrades=true, $actionurl = null) {
        $this->mrproject = $mrproject;
        $this->showgrades = $showgrades && $mrproject->uses_grades();
        $this->actionurl = $actionurl;
    }

    
    /**
     * Add a meeting to the table.
     *
     * @param meeting $meetingmodel the meeting to be added
     * @param task $taskmodel the corresponding task
     * @param array $otherstudents any other students in the same meeting
     * @param bool $cancancel whether the user can canel the task
     * @param bool $canedit whether the user can edit the meeting/task
     * @param bool $canview whether the user can view the task
     */
    public function add_meeting(meeting $meetingmodel, task $taskmodel,
                             $otherstudents, $cancancel = false, $canedit = false, $canview = false) {

        if ($meetingmodel->starttime < time()) {

            $meeting = new stdClass();
            $meeting->meetingid = $meetingmodel->id;
            //if ($this->showstudent) {
                $meeting->student = $taskmodel->student;
            //}
            $meeting->starttime = $meetingmodel->starttime;
            $meeting->endtime = $meetingmodel->endtime;
            //$meeting->attended = $taskmodel->attended;
            $meeting->location = $meetingmodel->tasklocation;
            $meeting->meetingnote = $meetingmodel->meetingpurpose;
            $meeting->meetingnoteformat = $meetingmodel->meetingpurposeformat;

            
            $meeting->teacher = new \stdClass();
            $meeting->teacher->firstname = get_string('students', 'mrproject');
            if ($meetingmodel->teacherid != null && $meetingmodel->teacherid != 0) {
                $meeting->teacher = $meetingmodel->get_teacher();
            }
            
            $meeting->taskid = $taskmodel->id;
            if ($this->mrproject->uses_tasknotes()) {
                $meeting->tasknote = $taskmodel->tasknote;
                $meeting->tasknoteformat = $taskmodel->tasknoteformat;
            }
            /*if ($this->mrproject->uses_teachernotes() && $this->showteachernotes) {
                $meeting->teachernote = $taskmodel->teachernote;
                $meeting->teachernoteformat = $taskmodel->teachernoteformat;
            }*/
            $meeting->otherstudents = $otherstudents;
            $meeting->cancancel = $cancancel;
            $meeting->canedit = $canedit;
            $meeting->canview = $canview;
            if ($this->showgrades) {
                $meeting->grade = $taskmodel->grade;
            }
            $this->showactions = $this->showactions || $cancancel;
            $this->hasotherstudents = $this->hasotherstudents || (bool) $otherstudents;

            $this->meetings[] = $meeting;
        }
    }

    

}



/**********************************************************************************************************/


/**
 * This class represents a table of meetings which a student can book.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_meeting_booker implements renderable {

    /**
     * @var array list of meetings to be displayed
     */
    public $meetings = array();

    /**
     * @var mrproject mrproject in whose context the list is
     */
    public $mrproject;

    /**
     * @var int the id number of the student booking meetings
     */
    public $studentid;

    /**
     * @var moodle_url action url for buttons
     */
    public $actionurl;


    /**
     * Contructs a meeting booker.
     *
     * @param mrproject $mrproject the mrproject in which the booking takes place
     * @param int $studentid the student who books
     * @param moodle_url $actionurl
     * @param int $maxselect no longer used
     */
    public function __construct(mrproject $mrproject, $studentid, moodle_url $actionurl, $maxselect) {
        $this->mrproject = $mrproject;
        $this->studentid = $studentid;
        $this->actionurl = $actionurl;
    }



    /**
     * Add a meeting to the list.
     *
     * @param meeting $meetingmodel the meeting to be added
     * @param bool $canbook whether the meeting can be booked
     * @param bool $bookedbyme whether the meeting is already booked by the current student
     * @param string $groupinfo information about group meetings
     * @param array $otherstudents other students in this meeting
     */
    public function add_meeting(meeting $meetingmodel, $canbook, $bookedbyme, $groupinfo, $otherstudents) {
        $meeting = new stdClass();
        $meeting->meetingid = $meetingmodel->id;
        $meeting->starttime = $meetingmodel->starttime;
        $meeting->endtime = $meetingmodel->endtime;
        $meeting->location = $meetingmodel->tasklocation;
        $meeting->notes = $meetingmodel->meetingpurpose;
        $meeting->notesformat = $meetingmodel->meetingpurposeformat;
        $meeting->bookedbyme = $bookedbyme;
        $meeting->canbook = $canbook;
        $meeting->groupinfo = $groupinfo;
        $meeting->teacher = $meetingmodel->get_teacher();
        $meeting->otherstudents = $otherstudents;

        $this->meetings[] = $meeting;
    }

    

}






/**
 * Command bar with action buttons, used by teachers.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_command_bar implements renderable {

    /**
     * @var array list of drop-down menus in the command bar
     */
    public $menus = array();

    /**
     * @var array list of action_link objects used in the menu
     */
    public $linkactions = array();

    /**
     * @var string title of the menu
     */
    public $title = '';


    /**
     * Contructs a command bar
     */
    public function __construct() {
        // Nothing to add right now.
    }


    /**
     * Adds a group of menu items in a menu.
     *
     * @param string $title the title of the group
     * @param array $actions an array of action_menu_link instances, representing the commands
     */
    public function add_group($title, array $actions) {
        $menu = new action_menu($actions);
        $menu->actiontext = $title;
        $this->menus[] = $menu;
    }

    /**
     * Creates an action link with an optional confirmation dialogue attached.
     *
     * @param moodle_url $url URL of the action
     * @param string $titlekey key of the link title
     * @param string $iconkey key of the icon to display
     * @param string|null $confirmkey key for the confirmation text
     * @param string|null $id id attribute of the new link
     * @return action_link the new action link
     */
    public function action_link(moodle_url $url, $titlekey, $iconkey, $confirmkey = null, $id = null) {
        $title = get_string($titlekey, 'mrproject');
        $pix = new pix_icon($iconkey, $title, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $attributes = array();
        if ($id) {
            $attributes['id'] = $id;
        }
        $confirmaction = null;
        if ($confirmkey) {
            $confirmaction = new confirm_action(get_string($confirmkey, 'mrproject'));
        }
        $act = new action_link($url, $title, $confirmaction, $attributes, $pix);
        $act->primary = false;
        return $act;
    }

    

}



/******************************************My meetings tab: Meetings table*******************************************/


/**
 * This class represents a table of meetings displayed to a teacher, with options to modify the list.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_meeting_manager implements renderable {

    /**
     * @var array list of meetings
     */
    public $meetings = array();

    /**
     * @var mrproject mrproject in whose context the list is
     */
    public $mrproject;

    /**
     * @var moodle_url action URL for buttons
     */
    public $actionurl;

    /**
     * @var bool should the teacher owning the meeting be shown?
     */
    public $showteacher = false;

    public $groupid = null;


    /**
     * Contructs a meeting manager.
     *
     * @param mrproject $mrproject the mrproject in which the booking takes place
     * @param moodle_url $actionurl action URL for buttons
     */
    public function __construct(mrproject $mrproject, moodle_url $actionurl, $groupid) {
        $this->mrproject = $mrproject;
        $this->actionurl = $actionurl;
        $this->groupid = $groupid;
    }



    /**
     * Add a meeting to the list.
     *
     * @param meeting $meetingmodel the meeting to be added
     * @param mrproject_student_list $students the list of students in the meeting
     * @param bool $editable whether the meeting is editable
     */
    public function add_meeting(meeting $meetingmodel, mrproject_student_list $students, $editable) {
        $meeting = new stdClass();
        $meeting->meetingid = $meetingmodel->id;
        $meeting->starttime = $meetingmodel->starttime;
        $meeting->endtime = $meetingmodel->endtime;
        $meeting->duration = $meetingmodel->duration;
        $meeting->proposeddate1 = $meetingmodel->proposeddate1;
        $meeting->proposeddate2 = $meetingmodel->proposeddate2;
        $meeting->proposeddate3 = $meetingmodel->proposeddate3;
        $meeting->location = $meetingmodel->tasklocation;
        $meeting->teacher = $meetingmodel->get_teacher();
        $meeting->students = $students;        //see, renderable.php (mrproject_student_list)
        $meeting->editable = $editable;
        //$meeting->isattended = $meetingmodel->is_attended();
        //$meeting->isappointed = $meetingmodel->get_task_count();
        //$meeting->meetingheld = $meetingmodel->meetingheld;

        $this->meetings[] = $meeting;
    }

    
}



/******************************************My meetings tab: Plan student team meetings*******************************************/


/**
 * A list of students displayed to a teacher, with action menus to schedule the students.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_scheduling_list implements renderable {

    /**
     * @var array lines in the list
     */
    public $lines = array();

    /**
     * @var mrproject the mrproject in whose context the list is
     */
    public $mrproject;

    /**
     * @var array extra headers for custom fields in the list
     */
    public $extraheaders;

    /**
     * @var string HTML id of the list
     */
    public $id = 'schedulinglist';


    /**
     * Contructs a scheduling list.
     *
     * @param mrproject $mrproject the mrproject in which the booking takes place
     * @param array $extraheaders headers for extra data fields
     */
    public function __construct(mrproject $mrproject, array $extraheaders) {
        $this->mrproject = $mrproject;
        $this->extraheaders = $extraheaders;
    }


    /**
     * Add a line to the list.
     *
     * @param string $pix icon to display next to the student's name
     * @param string $name name of the student
     * @param array $extrafields content of extra data fields to be displayed
     * @param array $actions actions to be displayed in an action menu
     */
    public function add_line($pix, $name, $memberslist, array $extrafields, $actions) {
        $line = new stdClass();
        $line->pix = $pix;
        $line->name = $name;
        $line->memberslist = $memberslist;
        $line->extrafields = $extrafields;
        $line->actions = $actions;

        $this->lines[] = $line;
    }

}




/********************************************* Total grade ****************************************/



/**
 * Represents information about a student's total grade in the mrproject, plus gradebook information.
 *
 * To be used in teacher screens.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_totalgrade_info implements renderable {

    /**
     * @var stdClass|null gradebook grade for the student
     */
    public $gbgrade;

    /**
     * @var mrproject mrproject in whose context the information is
     */
    public $mrproject;

    /**
     * @var bool whether to show a total grade
     */
    public $showtotalgrade;

    /**
     * @var int the total grade to display
     */
    public $totalgrade;

    /**
     * Constructs a grade info object
     *
     * @param mrproject $mrproject the mrproject in question
     * @param stdClass $gbgrade information about the grade in the gradebook (may be null)
     * @param bool $showtotalgrade whether the total grade in the mrproject should be shown
     * @param int $totalgrade the total grade of the student in this mrproject
     */
    public function __construct(mrproject $mrproject, $gbgrade, $showtotalgrade = false, $totalgrade = 0) {
        $this->mrproject = $mrproject;
        $this->gbgrade = $gbgrade;
        $this->showtotalgrade = $showtotalgrade;
        $this->totalgrade = $totalgrade;
    }

}





/**
 * This class represents a list of scheduling conflicts.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_conflict_list implements renderable {

    /**
     * @var array list of conflicts
     */
    public $conflicts = array();


    /**
     * Add several conflicts to the list.
     *
     * @param array $conflicts information about the conflicts
     */
    public function add_conflicts(array $conflicts) {
        foreach ($conflicts as $c) {
            $this->add_conflict($c);
        }
    }


    /**
     * Add a conflict to the list.
     *
     * @param stdClass $conflict information about the conflict
     * @param stdClass $user the user who is affected
     */
    public function add_conflict(stdClass $conflict, $user = null) {
        $c = clone($conflict);
        if ($user) {
            $c->userfullname = fullname($user);
        } else {
            $c->userfullname = '';
        }
        $this->conflicts[] = $c;
    }

    

}


/************************************ View meeting report **********************************************/

/**
 * Information about an task in the mrproject.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_meeting_report implements renderable {

    /**
     * @var mrproject mrproject in whose context the task is
     */
    public $mrproject;

    /**
     * @var meeting meeting in which the task is
     */
    public $meeting;

    /**
     * @var task the task itself
     */
    public $task;

    /**
     * @var bool whether to show information about the meeting (times, etc.)
     */
    public $showmeetinginfo;

    /**
     * @var bool whether to show booking instructions
     */
    public $showbookinginfo;

    /**
     * @var bool whether to show information about the student
     */
    public $showstudentdata;

    /**
     * @var string information about the group the booking is for
     */
    public $groupinfo;

    /**
     * @var bool whether the information is shown to a student (rather than a teacher)
     */
    public $onstudentside;

    /**
     * @var bool whether to show grades and task notes
     */
    public $showresult;



    /**
     * Create task information for a new task in a meeting.
     *
     * @param meeting $meeting the meeting in question
     * @param bool $showbookinginstr whether to show booking instructions
     * @param bool $onstudentside whether the screen is shown to a student
     * @param string $groupinfo information about the group that the booking is for
     * @return mrproject_meeting_report
     */
    public static function make_from_meeting(meeting $meeting, $showbookinginstr = false, $onstudentside = false,
                                          $groupinfo = null) {
        $info = new mrproject_meeting_report();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showbookinginfo = $showbookinginstr;
        $info->showstudentdata = false;
        $info->showresult = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = $groupinfo;

        return $info;
    }

    /**
     * Create task information for an existing task.
     *
     * @param meeting $meeting the meeting in question
     * @param task $task the task in question
     * @param string $onstudentside whether the screen is shown to a student
     * @return mrproject_meeting_report
     */
    public static function make_from_task(meeting $meeting, task $task, $onstudentside = true) {
        $info = new mrproject_meeting_report();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->task = $task;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showboookinginfo = true;
        $info->showstudentdata = $info->mrproject->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = null;

        return $info;
    }

    /**
     * Create task information for an existing task, shown to a teacher.
     * This excludes booking instructions and results.
     *
     * @param meeting $meeting the meeting in question
     * @param task $task the task in question
     * @return mrproject_meeting_report
     */
    public static function make_for_teacher(meeting $meeting, task $task) {
        $info = new mrproject_meeting_report();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->task = $task;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showboookinginfo = false;
        $info->showstudentdata = $info->mrproject->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = false;
        $info->groupinfo = null;

        return $info;
    }

}

/**************************** individual deliverables: Submit task report + dependencies ***************************************/

/**
 * Information about an task in the mrproject.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_task_info implements renderable {

    /**
     * @var mrproject mrproject in whose context the task is
     */
    public $mrproject;

    /**
     * @var meeting meeting in which the task is
     */
    public $meeting;

    /**
     * @var task the task itself
     */
    public $task;

    /**
     * @var bool whether to show information about the meeting (times, etc.)
     */
    public $showmeetinginfo;

    /**
     * @var bool whether to show booking instructions
     */
    public $showbookinginfo;

    /**
     * @var bool whether to show information about the student
     */
    public $showstudentdata;

    /**
     * @var string information about the group the booking is for
     */
    public $groupinfo;

    /**
     * @var bool whether the information is shown to a student (rather than a teacher)
     */
    public $onstudentside;

    /**
     * @var bool whether to show grades and task notes
     */
    public $showresult;



    /**
     * Create task information for a new task in a meeting.
     *
     * @param meeting $meeting the meeting in question
     * @param bool $showbookinginstr whether to show booking instructions
     * @param bool $onstudentside whether the screen is shown to a student
     * @param string $groupinfo information about the group that the booking is for
     * @return mrproject_task_info
     */
    public static function make_from_meeting(meeting $meeting, $showbookinginstr = false, $onstudentside = false,
                                          $groupinfo = null) {
        $info = new mrproject_task_info();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showbookinginfo = $showbookinginstr;
        $info->showstudentdata = false;
        $info->showresult = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = $groupinfo;

        return $info;
    }

    /**
     * Create task information for an existing task.
     *
     * @param meeting $meeting the meeting in question
     * @param task $task the task in question
     * @param string $onstudentside whether the screen is shown to a student
     * @return mrproject_task_info
     */
    public static function make_from_task(meeting $meeting, task $task, $onstudentside = true) {
        $info = new mrproject_task_info();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->task = $task;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showboookinginfo = true;
        $info->showstudentdata = $info->mrproject->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = null;

        return $info;
    }

    /**
     * Create task information for an existing task, shown to a teacher.
     * This excludes booking instructions and results.
     *
     * @param meeting $meeting the meeting in question
     * @param task $task the task in question
     * @return mrproject_task_info
     */
    public static function make_for_teacher(meeting $meeting, task $task) {
        $info = new mrproject_task_info();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->task = $task;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showboookinginfo = false;
        $info->showstudentdata = $info->mrproject->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = false;
        $info->groupinfo = null;

        return $info;
    }

}





/************************************ Viewstudent: this task **********************************************/

/**
 * Information about an task in the mrproject.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_submitted_task implements renderable {

    /**
     * @var mrproject mrproject in whose context the task is
     */
    public $mrproject;

    /**
     * @var meeting meeting in which the task is
     */
    public $meeting;

    /**
     * @var task the task itself
     */
    public $task;

    /**
     * @var bool whether to show information about the meeting (times, etc.)
     */
    public $showmeetinginfo;

    /**
     * @var bool whether to show booking instructions
     */
    public $showbookinginfo;

    /**
     * @var bool whether to show information about the student
     */
    public $showstudentdata;

    /**
     * @var string information about the group the booking is for
     */
    public $groupinfo;

    /**
     * @var bool whether the information is shown to a student (rather than a teacher)
     */
    public $onstudentside;

    /**
     * @var bool whether to show grades and task notes
     */
    public $showresult;

    public $actionurl = null;

    public $student = null;


    /**
     * Create task information for a new task in a meeting.
     *
     * @param meeting $meeting the meeting in question
     * @param bool $showbookinginstr whether to show booking instructions
     * @param bool $onstudentside whether the screen is shown to a student
     * @param string $groupinfo information about the group that the booking is for
     * @return mrproject_submitted_task
     */
    public static function make_from_meeting(meeting $meeting, $showbookinginstr = false, $onstudentside = false,
                                          $groupinfo = null) {
        $info = new mrproject_submitted_task();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showbookinginfo = $showbookinginstr;
        $info->showstudentdata = false;
        $info->showresult = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = $groupinfo;

        return $info;
    }

    /**
     * Create task information for an existing task.
     *
     * @param meeting $meeting the meeting in question
     * @param task $task the task in question
     * @param string $onstudentside whether the screen is shown to a student
     * @return mrproject_submitted_task
     */
    public static function make_from_task(meeting $meeting, task $task, $onstudentside = true) {
        $info = new mrproject_submitted_task();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->task = $task;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showboookinginfo = true;
        $info->showstudentdata = $info->mrproject->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = null;

        return $info;
    }

    /**
     * Create task information for an existing task, shown to a teacher.
     * This excludes booking instructions and results.
     *
     * @param meeting $meeting the meeting in question
     * @param task $task the task in question
     * @return mrproject_submitted_task
     */
    public static function make_for_teacher(meeting $meeting, task $task, $actionurl = null, $student = null) {
        $info = new mrproject_submitted_task();
        $info->meeting = $meeting;
        $info->meeting->meetingid = $meeting->id;
        $info->task = $task;
        $info->task->taskid = $task->id;
        $info->mrproject = $meeting->get_mrproject();
        $info->showmeetinginfo = true;
        $info->showboookinginfo = false;
        $info->showstudentdata = $info->mrproject->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = false;
        $info->groupinfo = null;
        $info->actionurl = $actionurl;
        $info->student = $student;    //student id

        return $info;
    }

}

/******************************************Members: students list*******************************************/


/**
 * A list of students displayed to a teacher, with action menus to schedule the students.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_students_list implements renderable {

    /**
     * @var array lines in the list
     */
    public $lines = array();

    /**
     * @var mrproject the mrproject in whose context the list is
     */
    public $mrproject;

    /**
     * @var array extra headers for custom fields in the list
     */
    public $extraheaders;

    /**
     * @var string HTML id of the list
     */
    public $id = 'schedulinglist';


    /**
     * Contructs a scheduling list.
     *
     * @param mrproject $mrproject the mrproject in which the booking takes place
     * @param array $extraheaders headers for extra data fields
     */
    public function __construct(mrproject $mrproject, array $extraheaders) {
        $this->mrproject = $mrproject;
        $this->extraheaders = $extraheaders;
    }


    /**
     * Add a line to the list.
     *
     * @param string $pix icon to display next to the student's name
     * @param string $name name of the student
     * @param array $extrafields content of extra data fields to be displayed
     * @param array $actions actions to be displayed in an action menu
     */
    public function add_line($pix, $name, $responsibility, array $extrafields) {
        $line = new stdClass();
        $line->pix = $pix;
        $line->name = $name;
        $line->responsibility = $responsibility;
        $line->extrafields = $extrafields;

        $this->lines[] = $line;
    }

}


/****************************************** Members: supervisors list **************************************/


/**
 * A list of students displayed to a teacher, with action menus to schedule the students.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_supervisors_list implements renderable {

    /**
     * @var array lines in the list
     */
    public $lines = array();

    /**
     * @var mrproject the mrproject in whose context the list is
     */
    public $mrproject;

    /**
     * @var array extra headers for custom fields in the list
     */
    public $extraheaders;

    /**
     * @var string HTML id of the list
     */
    public $id = 'schedulinglist';


    /**
     * Contructs a scheduling list.
     *
     * @param mrproject $mrproject the mrproject in which the booking takes place
     * @param array $extraheaders headers for extra data fields
     */
    public function __construct(mrproject $mrproject, array $extraheaders) {
        $this->mrproject = $mrproject;
        $this->extraheaders = $extraheaders;
    }


    /**
     * Add a line to the list.
     *
     * @param string $pix icon to display next to the student's name
     * @param string $name name of the student
     * @param array $extrafields content of extra data fields to be displayed
     * @param array $actions actions to be displayed in an action menu
     */
    public function add_line($pix, $name, $role, array $extrafields) {
        $line = new stdClass();
        $line->pix = $pix;
        $line->name = $name;
        $line->role = $role;
        $line->extrafields = $extrafields;

        $this->lines[] = $line;
    }

}

/***************************************************************************************/