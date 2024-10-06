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
 * This file contains a renderer for the mrproject module
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;
use \mod_mrproject\permission\mrproject_permissions;

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir.'/formslib.php');



/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the mrproject module.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_mrproject_renderer extends plugin_renderer_base {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct($page = null, $target = null) {
        if ($page) {
            parent::__construct($page, $target);
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
            if (empty($timeformat)) {
                $timeformat = get_config(null, 'calendar_site_timeformat'); // Get calendar config if above not exist.
            }
            if (empty($timeformat)) {
                $timeformat = get_string('strftimetime'); // Get locale default format if both of the above do not exist.
            }
            return userdate($date, $timeformat);
        }
    }

    /**
     * Format a meeting date and time, for use as a parameter in a language string.
     *
     * @param int $meetingdate
     *            a timestamp, start time of the meeting
     * @param int $duration
     *            length of the meeting in minutes
     * @return stdClass date and time formatted for usage in language strings
     */
    public static function meetingdatetime($meetingdate, $duration) {
        $shortformat = get_string('strftimedatetimeshort');

        $a = new stdClass();
        $a->date = self::userdate($meetingdate);
        $a->starttime = self::usertime($meetingdate);
        $a->shortdatetime = userdate($meetingdate, $shortformat);
        $a->endtime = self::usertime($meetingdate + $duration * MINSECS);
        $a->duration = $duration;

        return $a;
    }


    //Individual deliverables table
    public static function meetinginfos($meetingdate, $duration, $tasklocation) {
        $shortformat = get_string('strftimedatetimeshort');

        $a = new stdClass();
        $a->date = self::userdate($meetingdate);
        $a->starttime = self::usertime($meetingdate);
        $a->shortdatetime = userdate($meetingdate, $shortformat);
        $a->endtime = self::usertime($meetingdate + $duration * MINSECS);
        $a->duration = $duration;
        $a->tasklocation = $tasklocation;

        return $a;
    }


    /**
     * @var array a cached version of scale levels
     */
    protected $scalecache = array();

    /**
     * Get a list of levels in a grading scale.
     *
     * @param int $scaleid id number of the scale
     * @return array levels on the scale
     */
    public function get_scale_levels($scaleid) {
        global $DB;

        if (!array_key_exists($scaleid, $this->scalecache)) {
            $this->scalecache[$scaleid] = array();
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $levels = explode(',', $scale->scale);
                foreach ($levels as $levelid => $value) {
                    $this->scalecache[$scaleid][$levelid + 1] = $value;
                }
            }
        }
        return $this->scalecache[$scaleid];
    }

    /**
     * Formats a grade in a specific mrproject for display.
     *
     * @param mixed $subject either a mrproject instance or a scale id
     * @param string $grade the grade to be displayed
     * @param bool $short formats the grade in short form (result empty if grading is
     * not used, or no grade is available; parantheses are put around the grade if it is present)
     * @return string the formatted grade
     */
    public function format_grade($subject, $grade, $short = false) {
        if ($subject instanceof mrproject) {
            $scaleid = 20;
        } else {
            $scaleid = (int) $subject;
        }

        $result = '';
        if ($scaleid == 0 || is_null($grade) ) {
            // mrproject doesn't allow grading, or no grade entered.
            if (!$short) {
                $result = get_string('nograde');
            }
        } else {
            $grade = (int) $grade;
            if ($scaleid > 0) {
                // Numeric grade.
                $result .= $grade;
                if (strlen($grade) > 0) {
                    $result .= '/' . $scaleid;
                }
            } 
            if ($short && (strlen($result) > 0)) {
                $result = '('.$result.')';
            }
        }
        return $result;
    }

    /**
     * A utility function for producing grading lists (for use in formslib)
     *
     * Note that the selection list will contain a "nothing selected" option
     * with key -1 which will be displayed as "No grade".
     *
     * @param reference $mrproject
     * @return array the choices to be displayed in a grade chooser
     */
    public function grading_choices($mrproject) {
       // if (20 > 0) {
            $scalegrades = array();
            /*for ($i = 0; $i <= 20; $i++) {
                $scalegrades[$i] = $i;
            }*/
            $scalegrades[0] = 0;
            $scalegrades[1] = 1;
            $scalegrades[2] = 2;
            $scalegrades[3] = 3;
            $scalegrades[4] = 4;
            $scalegrades[5] = 5;
            $scalegrades[6] = 6;
            $scalegrades[7] = 7;
            $scalegrades['7.5'] = 7.5;
            $scalegrades[8] = 8;
            $scalegrades['8.5'] = 8.5;
            $scalegrades[9] = 9;
            $scalegrades['9.5'] = 9.5;
            $scalegrades[10] = 10;
            $scalegrades['10.5'] = 10.5;
            $scalegrades[11] = 11;
            $scalegrades['11.5'] = 11.5;
            $scalegrades[12] = 12;
            $scalegrades['12.5'] = 12.5;
            $scalegrades[13] = 13;
            $scalegrades['13.5'] = 13.5;
            $scalegrades[14] = 14;
            $scalegrades['14.5'] = 14.5;
            $scalegrades[15] = 15;
            $scalegrades['15.5'] = 15.5;
            $scalegrades[16] = 16;
            $scalegrades['16.5'] = 16.5;
            $scalegrades[17] = 17;
            $scalegrades['17.5'] = 17.5;
            $scalegrades[18] = 18;
            $scalegrades['18.5'] = 18.5;
            $scalegrades[19] = 19;
            $scalegrades['19.5'] = 19.5;
            $scalegrades[20] = 20;

        /*} else {
            $scaleid = - (20);
            $scalegrades = $this->get_scale_levels($scaleid);
        }*/

        $scalegrades = array(-1 => get_string('nograde')) + $scalegrades;

        return $scalegrades;
    }

    /**
     * Return a string describing the grading strategy of a mrproject.
     *
     * @param int $strategy id number for the strategy
     * @return string description of the strategy
     */
    public function format_grading_strategy($strategy) {
        if ($strategy == MRPROJECT_MAX_GRADE) {
            return get_string('maxgrade', 'mrproject');
        } else {
            return get_string('meangrade', 'mrproject');
        }
    }

    /**
     * Format a user-entered "note" on a meeting or task, adjusting any links to embedded files.
     * The "note" may also be the booking instructions.
     *
     * @param string $content content of the note
     * @param int $format format of the note
     * @param context $context context of the note
     * @param string $area file ara for embedded files
     * @param int $itemid item id for embedded files
     * @return string the formatted note
     */
    public function format_notes($content, $format, $context, $area, $itemid) {
        $text = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_mrproject', $area, $itemid);
        return format_text($text, $format);
    }

    /**
     * Format the notes relating to an task (task notes and confidential notes).
     *
     * @param mrproject $mrproject the mrproject in whose context the task is
     * @param stdClass $data database record describing the task
     * @param string $idfield the field in the record containing the item id
     * @return string formatted notes
     */
    public function format_task_notes(mrproject $mrproject, $data, $idfield = 'id') {
        $note = '';
        $id = $data->{$idfield};
        if (isset($data->tasknote) && $mrproject->uses_tasknotes()) {
            $note .= $this->format_notes($data->tasknote, $data->tasknoteformat, $mrproject->get_context(),
                                         'tasknote', $id);
        }
        if (isset($data->teachernote) && $mrproject->uses_teachernotes()) {
            $note .= $this->format_notes($data->teachernote, $data->teachernoteformat, $mrproject->get_context(),
                                         'teachernote', $id);
        }
        return $note;
    }

    /**
     * Produce HTML code for a link to a user's profile.
     * That is, the full name of the user is displayed with a link to the user's course profile on it.
     *
     * @param mrproject $mrproject the mrproject in whose context the link is
     * @param stdClass $user the user to link to
     * @return string HTML code of the link
     */
    public function user_profile_link(mrproject $mrproject, stdClass $user) {
        $profileurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $mrproject->course));
        return html_writer::link($profileurl, fullname($user));

    }

    /**
     * Produce HTML code for a link to a user's task.
     * That is, the full name of the user is displayed with a link to a given task.
     *
     * @param unknown $mrproject the mrproject in whose context the link is
     * @param unknown $user the use in question
     * @param unknown $taskid id number of the task to link to
     * @return string HTML code of the link
     */
    public function task_link($mrproject, $user, $taskid) {
        $paras = array(
                        'what' => 'viewstudent',
                        'id' => $mrproject->cmid,
                        'taskid' => $taskid
        );
        $url = new moodle_url('/mod/mrproject/view.php', $paras);
        return html_writer::link($url, fullname($user));
    }

    /**
     * Render a list of files in a filearea.
     *
     * @param int $contextid id number of the context of the files
     * @param string $filearea name of the file area
     * @param int $itemid item id in the file area
     * @return string rendered list of files
     */
    public function render_attachments($contextid, $filearea, $itemid) {

        $fs = get_file_storage();
        $o = '';

        // We retrieve all files according to the time that they were created.  In the case that several files were uploaded
        // at the sametime (e.g. in the case of drag/drop upload) we revert to using the filename.
        $files = $fs->get_area_files($contextid, 'mod_mrproject', $filearea, $itemid, "filename", false);
        if ($files) {
            $o .= html_writer::start_tag('ul', array('class' => 'mrproject_filelist'));
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $pathname = $file->get_filepath();
                $mimetype = $file->get_mimetype();
                $iconimage = $this->pix_icon(file_file_icon($file), get_mimetype_description($file),
                                             'moodle', array('class' => 'icon'));
                $path = moodle_url::make_pluginfile_url($contextid, 'mod_mrproject', $filearea, $itemid, $pathname, $filename);

                $ulitem = html_writer::link($path, $iconimage) . html_writer::link($path, s($filename));
                $o .= html_writer::tag('ul', $ulitem);
            }
            $o .= html_writer::end_tag('ul');
        }

        return $o;
    }



/*****************************************Tab: My tasks****************************************************/

    /**
     * Render the module introduction of a mrproject.
     *
     * @param mrproject $mrproject the mrproject in question
     * @return string rendered module info
     */
    public function mod_intro($mrproject) {
        $o = $this->heading(format_string($mrproject->name), 2);

        if (trim(strip_tags($mrproject->intro))) {
            $o .= $this->box_start('mod_introbox');
            $o .= format_module_intro('mrproject', $mrproject->get_data(), $mrproject->cmid);
            $o .= $this->box_end();
        }
        return $o;
    }

    /**
     * Construct a tab header in the teacher view.
     *
     * @param moodle_url $baseurl
     * @param string $namekey
     * @param string $what
     * @param string $subpage
     * @param string $nameargs
     * @return tabobject
     */
    private function teacherview_tab(moodle_url $baseurl, $namekey, $what, $subpage = '', $nameargs = null) {
        $taburl = new moodle_url($baseurl, array('what' => $what, 'subpage' => $subpage));
        $tabname = get_string($namekey, 'mrproject', $nameargs);
        $id = ($subpage != '') ? $subpage : $what;
        $tab = new tabobject($id, $taburl, $tabname);
        return $tab;
    }

    /**
     * Render the tab header hierarchy in the teacher view.
     *
     * @param mrproject $mrproject the mrproject in question
     * @param mrproject_permissions $permissions the permissions manager (for hiding tabs)
     * @param moodle_url $baseurl base URL for the tab addresses
     * @param string $selected the selected tab
     * @param array $inactive any inactive tabs
     * @return string rendered tab tree
     */
    public function teacherview_tabs(mrproject $mrproject, mrproject_permissions $permissions,
                                     moodle_url $baseurl, $selected, $inactive = null) {

        

        /*..................MRP Tabs.......................*/

        

        $level1 = array();

        //Welcome tab
        $level1[] = $this->teacherview_tab($baseurl, 'welcometab', 'welcomepage', 'welcometab');

        //Members tab
        $level1[] = $this->teacherview_tab($baseurl, 'memberstab', 'memberspage', 'memberstab');
        
        
        //Meetings tab
        $taburl = new moodle_url($baseurl, array('what' => 'meetingspage', 'subpage' => 'mymeetingssubtab'));
        $meetingstab = new tabobject('meetingstab', $taburl, get_string('meetingstab', 'mrproject'));
        $meetingstab->subtree = array(
            /*new tabobject('000', '', ''),
            new tabobject('000', '', ''),
            new tabobject('000', '', ''),*/
            $this->teacherview_tab($baseurl, 'mymeetingssubtab', 'meetingspage', 'mymeetingssubtab'),
            $this->teacherview_tab($baseurl, 'upcomingmeetingssubtab', 'meetingspage', 'upcomingmeetingssubtab')
        );
        $level1[] = $meetingstab;


        //Deliverables tab
        $taburl = new moodle_url($baseurl, array('what' => 'deliverablespage', 'subpage' => 'collectivedeliverablessubtab'));
        $deliverablestab = new tabobject('deliverablestab', $taburl, get_string('deliverablestab', 'mrproject'));
        $deliverablestab->subtree = array(
            /*new tabobject('000', '', ''),
            new tabobject('000', '', ''),
            new tabobject('000', '', ''),
            new tabobject('000', '', ''),
            new tabobject('000', '', ''),*/
            $this->teacherview_tab($baseurl, 'collectivedeliverablessubtab', 'deliverablespage', 'collectivedeliverablessubtab'),
            $this->teacherview_tab($baseurl, 'individualdeliverablessubtab', 'deliverablespage', 'individualdeliverablessubtab'),
            $this->teacherview_tab($baseurl, 'dependenciessubtab', 'deliverablespage', 'dependenciessubtab'),        
            
        );
        $level1[] = $deliverablestab;



        //Export tab
        $teachercaps = ['mod/mrproject:managealltasks']; 
        $isnotstudent = has_any_capability($teachercaps, $mrproject->get_context());
        if ($isnotstudent) {
            $level1[] = $this->teacherview_tab($baseurl, 'exporttab', 'export', 'exporttab');
        }


        /*..................................................*/

        /*$level1[] = $this->teacherview_tab($baseurl, 'mytasks', 'teacherview', 'mytasks');
        if ($permissions->can_see_all_meetings()) {
            $level1[] = $this->teacherview_tab($baseurl, 'alltasks', 'teacherview', 'alltasks');
        }
        $level1[] = $this->teacherview_tab($baseurl, 'datelist', 'datelist');

        $statstab = $this->teacherview_tab($baseurl, 'statistics', 'viewstatistics', 'overall');
        $statstab->subtree = array(
                        $this->teacherview_tab($baseurl, 'overall', 'viewstatistics', 'overall'),
                        $this->teacherview_tab($baseurl, 'studentbreakdown', 'viewstatistics', 'studentbreakdown'),
                        $this->teacherview_tab($baseurl, 'staffbreakdown', 'viewstatistics', 'staffbreakdown', get_string('meetingwith', 'mrproject')),
                        $this->teacherview_tab($baseurl, 'lengthbreakdown', 'viewstatistics', 'lengthbreakdown'),
                        $this->teacherview_tab($baseurl, 'groupbreakdown', 'viewstatistics', 'groupbreakdown')
        );
        $level1[] = $statstab;
        $level1[] = $this->teacherview_tab($baseurl, 'export', 'export');*/


        return $this->tabtree($level1, $selected, $inactive);
    }




/********************************** Upcoming meetings table *****************************************/


    /**
     * Render a table of meetings. (using the interface 'mrproject_meeting_table' defined in rederable.php)
     *
     * @param mrproject_meeting_table $meetingtable the table to rended
     * @return string the HTML output
     */
    public function render_mrproject_meeting_table(mrproject_meeting_table $meetingtable) {
        $table = new html_table();

        if ($meetingtable->showmeeting) {
            $table->head  = array(get_string('date', 'mrproject'));
            $table->align = array('left');
        }
        if ($meetingtable->showstudent) {
            $table->head[]  = get_string('name');
            $table->align[] = 'left';
        }
        /*if ($meetingtable->showattended) {
            $table->head[] = get_string('seen', 'mrproject');
            $table->align[] = 'center';
        }*/
        if ($meetingtable->showmeeting) {
            $table->head[]  = get_string('meetingwith', 'mrproject');
            $table->align[] = 'left';
        }
        if ($meetingtable->showmeeting && $meetingtable->showlocation) {
            $table->head[]  = get_string('location', 'mrproject');
            $table->align[] = 'left';
        }

        /*$table->head[] = get_string('meetingpurpose', 'mrproject');
        $table->align[] = 'left';*/

        if ($meetingtable->showgrades) {
            $table->head[] = get_string('grade', 'mrproject');
            $table->align[] = 'left';
        } else if ($meetingtable->hasotherstudents) {
            $table->head[] = get_string('otherstudents', 'mrproject');
            $table->align[] = 'left';
        }
        if ($meetingtable->showactions) {
            $table->head[] = '';
            $table->align[] = 'right';
        }

        $table->data = array();

        foreach ($meetingtable->meetings as $meeting) {
            $rowdata = array();

            if (isset($meeting->taskid)) {
                $studenturl = new moodle_url($meetingtable->actionurl, array('taskid' => $meeting->taskid));
            }
            $timedata = $this->userdate($meeting->starttime);
            if ($meetingtable->showeditlink) {
                $timedata = $this->action_link($studenturl, $timedata);
            }
            $timedata = html_writer::div($timedata, 'datelabel');

            $starttime = $this->usertime($meeting->starttime);
            $endtime   = $this->usertime($meeting->endtime);
            $timedata .= html_writer::div("{$starttime} &ndash; {$endtime}", 'timelabel');

            if ($meetingtable->showmeeting) {
                $rowdata[] = $timedata;
            }

            if ($meetingtable->showstudent) {
                $name = fullname($meeting->student);
                if ($meetingtable->showeditlink) {
                    $name = $this->action_link($studenturl, $name);
                }
                $rowdata[] = $name;
            }

            /*if ($meetingtable->showattended) {
                $iconid = $meeting->attended ? 'ticked' : 'unticked';
                $iconhelp = $meeting->attended ? 'seen' : 'notseen';
                $attendedpix = $this->pix_icon($iconid, get_string($iconhelp, 'mrproject'), 'mod_mrproject');
                $rowdata[] = $attendedpix;
            }*/


            /*if ($meetingtable->showmeeting) {
                $rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);
            }*/

            //meeting with
            if ($meetingtable->showmeeting) {

                if ($meeting->cancancel) {      //the current user is teacher ($meeting->cancancel = isnotstudent)

                    $urlparas = array('id' => $meetingtable->mrproject->cmid,
                    'what' => 'memberspage',
                    'subpage' => 'memberstab',
                    'group' => $meeting->groupid);
                    $url = new moodle_url('/mod/mrproject/view.php', $urlparas);


                    //other teachers
                    $meetwithteacher = false;
                    $teacherids = array();
                    $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    foreach ($selectedmeeting->get_tasks() as $task) { 
                        if (has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $task->studentid)) {
                            global $USER;
                            if ($task->studentid != $USER->id) {
                                $meetwithteacher = true;
                                array_push($teacherids, $task->studentid);
                                //break;
                            }
                        }
                    }
                    $teacherlist = '';
                    if ($meetwithteacher) {    //with student and other teachers
                        foreach ($teacherids as $teacherid) {
                            $teacherlist .= $this->user_profile_link($meetingtable->mrproject, $meetingtable->mrproject->get_userbyid($teacherid)). '<br/>';
                        }
                    }

                    //other teachers + student team
                    $rowdata[] = $teacherlist . html_writer::link($url, get_string('students', 'mrproject'));   
                    
                } 

                else {   //the current user is student
                    
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    $isteacher = has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $meeting->teacher->id); 
                    
                    if ($isteacher) {   //a teacher launched a meeting
                        $rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);

                    } else {   //a student launched a meeting

                        $meetwithteacher = false;
                        $teacherids = array();
                        $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);
                        foreach ($selectedmeeting->get_tasks() as $task) { 
                            if (has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $task->studentid)) {
                                $meetwithteacher = true;
                                array_push($teacherids, $task->studentid);
                                //break;
                            }
                        }

                        if ($meetwithteacher) {    //with a teacher
                            $teacherlist = '';
                            foreach ($teacherids as $teacherid) {
                                $teacherlist .= $this->user_profile_link($meetingtable->mrproject, $meetingtable->mrproject->get_userbyid($teacherid)). '<br/>';
                            }
                            $rowdata[] = $teacherlist;
                        
                        } else {   //beetween students

                            $urlparas = array('id' => $meetingtable->mrproject->cmid,
                            'what' => 'memberspage',
                            'subpage' => 'memberstab',
                            'group' => $meeting->groupid);
                            
                            $url = new moodle_url('/mod/mrproject/view.php', $urlparas);
                            $rowdata[] = html_writer::link($url, get_string('students', 'mrproject'));
                            
                        }

                    }
                    
                }
                
            }



            if ($meetingtable->showmeeting && $meetingtable->showlocation) {
                $rowdata[] = format_string($meeting->location);
            }

            $notes = '';
            if ($meetingtable->showmeeting && isset($meeting->meetingnote)) {
                $notes .= $this->format_notes($meeting->meetingnote, $meeting->meetingnoteformat,
                                              $meetingtable->mrproject->get_context(), 'meetingnote', $meeting->meetingid);
            }
            //$rowdata[] = $notes;

            if ($meetingtable->showgrades || $meetingtable->hasotherstudents) {
                $gradedata = '';
                if ($meeting->otherstudents) {
                    $gradedata = $this->render($meeting->otherstudents);
                } else if ($meetingtable->showgrades) {
                    $gradedata = $this->format_grade($meetingtable->mrproject, $meeting->grade);
                }
                $rowdata[] = $gradedata;
            }

            
            
            //$rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);

            if ($meetingtable->showactions) {
                $actions = '';
                /*if ($meeting->canedit) {
                    $buttonurl = new moodle_url($meetingtable->actionurl,
                                     array('what' => 'editbooking', 'taskid' => $meeting->taskid));
                    $button = new single_button($buttonurl, get_string('editbooking', 'mrproject'));
                    $actions .= $this->render($button);
                }*/
                /*if ($meeting->canview) {
                    $buttonurl = new moodle_url($meetingtable->actionurl,
                                     array('what' => 'viewbooking', 'taskid' => $meeting->taskid));
                    $button = new single_button($buttonurl, get_string('viewbooking', 'mrproject'));
                    $actions .= $this->render($button);
                }*/
                //if ($meeting->cancancel) {
                    $buttonurl = new moodle_url($meetingtable->actionurl,
                                     array('what' => 'cancelbooking', 'meetingid' => $meeting->meetingid, 'groupid' => $meeting->groupid));
                    $button = new single_button($buttonurl, get_string('cancelbooking', 'mrproject'));
                    $actions .= $this->render($button);
                    $actions .= $this->action_icon($buttonurl, new pix_icon('i/invalid', get_string('cancelbooking', 'mrproject')));
                //}


                if ($meeting->canedit) {
                    $rowdata[] = '<em>Accepted </em>'. $this->output->pix_icon('i/grade_partiallycorrect', '') .'<br/>'. $actions;
                } else {
                    $rowdata[] = '<em>Accepted </em>'. $this->output->pix_icon('i/grade_partiallycorrect', ''); 
                }

            }
            $table->data[] = $rowdata;
        }

        return html_writer::table($table);
    }




/********************************** Available meetings table*****************************************/


    /**
     * Render a table of meetings. (using the interface 'mrproject_meeting_table' defined in rederable.php)
     *
     * @param mrproject_availablemeeting_table $meetingtable the table to rended
     * @return string the HTML output
     */
    public function render_mrproject_availablemeeting_table(mrproject_availablemeeting_table $meetingtable) {
        
        $this->page->requires->yui_module('moodle-mod_mrproject-saveseen',
                        'M.mod_mrproject.saveseen.init', array($meetingtable->mrproject->cmid) );

        $table = new html_table();

        $table->id = 'availablemeeting';

        /*if ($meetingtable->showmeeting) {
            $table->head  = array(get_string('proposeddates', 'mrproject'));
            $table->align = array('left');
        }*/
        if ($meetingtable->showstudent) {
            $table->head[]  = get_string('name');
            $table->align[] = 'left';
        }
        /*if ($meetingtable->showattended) {
            $table->head[] = get_string('seen', 'mrproject');
            $table->align[] = 'center';
        }*/
        if ($meetingtable->showmeeting) {
            $table->head[]  = get_string('meetingwith', 'mrproject');
            $table->align[] = 'left';
        }
        if ($meetingtable->showmeeting && $meetingtable->showlocation) {
            $table->head[]  = get_string('location', 'mrproject');
            $table->align[] = 'left';
        }

        /*$table->head[] = get_string('meetingpurpose', 'mrproject');
        $table->align[] = 'left';*/

        if ($meetingtable->showgrades) {
            $table->head[] = get_string('grade', 'mrproject');
            $table->align[] = 'left';
        } else if ($meetingtable->hasotherstudents) {
            $table->head[] = get_string('otherstudents', 'mrproject');
            $table->align[] = 'left';
        }
        if ($meetingtable->showactions) {
            $table->head[] = get_string('proposeddates', 'mrproject');
            $table->align[] = 'left';
        }

        $table->data = array();

        foreach ($meetingtable->meetings as $meeting) {
            $rowdata = array();

            $studenturl = new moodle_url($meetingtable->actionurl, array('taskid' => $meeting->taskid));


            if ($meetingtable->showstudent) {
                $name = fullname($meeting->student);
                if ($meetingtable->showeditlink) {
                    $name = $this->action_link($studenturl, $name);
                }
                $rowdata[] = $name;
            }


            //meeting with
            if ($meetingtable->showmeeting) {

                if ($meeting->cancancel) {      //the current user is teacher ($meeting->cancancel = isnotstudent)

                    //student team link
                    $urlparas = array('id' => $meetingtable->mrproject->cmid,
                    'what' => 'memberspage',
                    'subpage' => 'memberstab',
                    'group' => $meeting->groupid);
                    $url = new moodle_url('/mod/mrproject/view.php', $urlparas);


                    //other teachers
                    $meetwithteacher = false;
                    $teacherids = array();
                    $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    foreach ($selectedmeeting->get_tasks() as $task) { 
                        if (has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $task->studentid)) {
                            global $USER;
                            if ($task->studentid != $USER->id) {
                                $meetwithteacher = true;
                                array_push($teacherids, $task->studentid);
                                //break;
                            }
                        }
                    }
                    $teacherlist = '';
                    if ($meetwithteacher) {    //with student and other teachers
                        foreach ($teacherids as $teacherid) {
                            $teacherlist .= $this->user_profile_link($meetingtable->mrproject, $meetingtable->mrproject->get_userbyid($teacherid)). '<br/>';
                        }
                    }

                    //other teachers + student team
                    $rowdata[] = $teacherlist . html_writer::link($url, get_string('students', 'mrproject'));   
                    
                } 

                else {   //the current user is student
                    
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    $isteacher = has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $meeting->teacher->id); 
                    
                    if ($isteacher) {   //meeting launched by a teacher
                        $rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);

                    } else {   //meeting launched by a student

                        $meetwithteacher = false;
                        $teacherids = array();
                        $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);
                        foreach ($selectedmeeting->get_tasks() as $task) { 
                            if (has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $task->studentid)) {
                                $meetwithteacher = true;
                                array_push($teacherids, $task->studentid);
                                //break;
                            }
                        }

                        if ($meetwithteacher) {    //with a teacher
                            $teacherlist = '';
                            foreach ($teacherids as $teacherid) {
                                $teacherlist .= $this->user_profile_link($meetingtable->mrproject, $meetingtable->mrproject->get_userbyid($teacherid)). '<br/>';
                            }
                            $rowdata[] = $teacherlist;
                        
                        } else {   //beetween students

                            $urlparas = array('id' => $meetingtable->mrproject->cmid,
                            'what' => 'memberspage',
                            'subpage' => 'memberstab',
                            'group' => $meeting->groupid);
                            
                            $url = new moodle_url('/mod/mrproject/view.php', $urlparas);
                            $rowdata[] = html_writer::link($url, get_string('students', 'mrproject'));
                            
                        }

                    }
                } 
            }


            if ($meetingtable->showmeeting && $meetingtable->showlocation) {
                $rowdata[] = format_string($meeting->location);
            }

            $notes = '';
            if ($meetingtable->showmeeting && isset($meeting->meetingnote)) {
                $notes .= $this->format_notes($meeting->meetingnote, $meeting->meetingnoteformat,
                                              $meetingtable->mrproject->get_context(), 'meetingnote', $meeting->meetingid);
            }
            //$rowdata[] = $notes;

            if ($meetingtable->showgrades || $meetingtable->hasotherstudents) {
                $gradedata = '';
                if ($meeting->otherstudents) {
                    $gradedata = $this->render($meeting->otherstudents);
                } else if ($meetingtable->showgrades) {
                    $gradedata = $this->format_grade($meetingtable->mrproject, $meeting->grade);
                }
                $rowdata[] = $gradedata;
            }


            
            if ($meetingtable->showactions) {
                
                //proposed date 1
                if ($meeting->proposeddate1 != 0) {
                    $date1 = $meeting->proposeddate1;
                    $proposeddate1 = $this->usershortdate($date1);  
                    if ($meetingtable->showeditlink) {
                        $proposeddate1 = $this->action_link($studenturl, $proposeddate1);
                    }
                    $starttime = $this->usertime($meeting->proposeddate1);
                    $endtime   = $this->usertime($meeting->proposeddate1 + $meeting->duration * MINSECS);
                    $proposeddate1 .= ' <strong><sub>('.$starttime .'&ndash;'. $endtime.')</sub></strong>';
                }

                //proposed date 2
                if ($meeting->proposeddate2 != 0) {
                    $date2 = $meeting->proposeddate2;
                    $proposeddate2 = $this->usershortdate($date2);  
                    if ($meetingtable->showeditlink) {
                        $proposeddate2 = $this->action_link($studenturl, $proposeddate2);
                    }
                    $starttime = $this->usertime($meeting->proposeddate2);
                    $endtime   = $this->usertime($meeting->proposeddate2 + $meeting->duration * MINSECS);
                    $proposeddate2 .= ' <strong><sub>('.$starttime .'&ndash;'. $endtime.')</sub></strong>';
                }
                //proposed date 3
                if ($meeting->proposeddate3 != 0) {
                    $date3 = $meeting->proposeddate3;
                    $proposeddate3 = $this->usershortdate($date3);  
                    if ($meetingtable->showeditlink) {
                        $proposeddate3 = $this->action_link($studenturl, $proposeddate3);
                    }
                    $starttime = $this->usertime($meeting->proposeddate3);
                    $endtime   = $this->usertime($meeting->proposeddate3 + $meeting->duration * MINSECS);
                    $proposeddate3 .= ' <strong><sub>('.$starttime .'&ndash;'. $endtime.')</sub></strong>';
                }


                //if ($meeting->cancancel) {

                    $action1 = '';
                    $action2 = '';
                    $action3 = '';

                    //Accept date 1
                    if ($meeting->proposeddate1 != 0 && $meeting->proposeddate1 >= time()) {
                        if ($meeting->canedit) {
                            $buttonurl = new moodle_url($meetingtable->actionurl,
                                    array('what' => 'bookmeeting', 'meetingid' => $meeting->meetingid, 'selecteddate' => $date1, 'groupid' => $meeting->groupid));
                            //$action1 = get_string('acceptdate', 'mrproject').'&nbsp;';
                            //$action1 .= $this->action_icon($buttonurl, new pix_icon('i/grade_partiallycorrect', get_string('bookmeeting', 'mrproject')));
                            $action1 .= html_writer::link($buttonurl, '<strong>'.$proposeddate1.'</strong>', array('class' => 'proposeddates'));
                        } else {
                            $action1 .= '<strong>'.$proposeddate1.'</strong>';
                        }
                    }

                    
                    //Accept date 2
                    if ($meeting->proposeddate2 != 0 && $meeting->proposeddate2 >= time()) {
                        if ($meeting->canedit) {
                            $buttonurl = new moodle_url($meetingtable->actionurl,
                                    array('what' => 'bookmeeting', 'meetingid' => $meeting->meetingid, 'selecteddate' => $date2, 'groupid' => $meeting->groupid));
                            //$action2 = $this->action_icon($buttonurl, new pix_icon('i/grade_partiallycorrect', get_string('bookmeeting', 'mrproject')));
                            $action2 .= html_writer::link($buttonurl, '<strong>'.$proposeddate2.'</strong>', array('class' => 'proposeddates'));
                        } else {
                            $action2 .= '<strong>'.$proposeddate2.'</strong>';
                        }
                    }


                    //Accept date 3
                    if ($meeting->proposeddate3 != 0 && $meeting->proposeddate3 >= time()) {
                        if ($meeting->canedit) {
                            $buttonurl = new moodle_url($meetingtable->actionurl,
                                    array('what' => 'bookmeeting', 'meetingid' => $meeting->meetingid, 'selecteddate' => $date3, 'groupid' => $meeting->groupid));
                            //$action3 = $this->action_icon($buttonurl, new pix_icon('i/grade_partiallycorrect', get_string('bookmeeting', 'mrproject')));
                            $action3 .= html_writer::link($buttonurl, '<strong>'. $proposeddate3.'</strong>', array('class' => 'proposeddates') );
                        } else {
                            $action3 .= '<strong>'. $proposeddate3.'</strong>';
                        }
                    }


                $info = '';
                if ($meeting->canedit) {
                    $info = get_string('acceptthisdate', 'mrproject'). $this->output->pix_icon('t/expanded', ''). '<br/>';
                
                } else {
                    $info = get_string('pendingacceptance', 'mrproject'). $this->output->pix_icon('i/progressbar', ''). '<br/>';
                }


                //Actions: Proposed dates
                if ($meeting->proposeddate1 >= time() && $meeting->proposeddate2 >= time() && $meeting->proposeddate3 >= time()) {
                    $rowdata[] = $info. $action1 .'<br/>'. $action2 .'<br/>'. $action3;
                
                } elseif ($meeting->proposeddate1 < time() && $meeting->proposeddate2 >= time() && $meeting->proposeddate3 >= time()) {
                    $rowdata[] = $info. $action2 .'<br/>'. $action3;
                    
                } elseif ($meeting->proposeddate1 >= time() && $meeting->proposeddate2 < time() && $meeting->proposeddate3 >= time()) {
                    $rowdata[] = $info. $action1 .'<br/>'. $action3;

                } elseif ($meeting->proposeddate1 >= time() && $meeting->proposeddate2 >= time() && $meeting->proposeddate3 < time()) {
                    $rowdata[] = $info. $action1 .'<br/>'. $action2;

                } elseif ($meeting->proposeddate1 >= time() && $meeting->proposeddate2 < time() && $meeting->proposeddate3 < time()) {
                    $rowdata[] = $info. $action1;

                } elseif ($meeting->proposeddate1 < time() && $meeting->proposeddate2 >= time() && $meeting->proposeddate3 < time()) {
                    $rowdata[] = $info. $action2;

                } elseif ($meeting->proposeddate1 < time() && $meeting->proposeddate2 < time() && $meeting->proposeddate3 >= time()) {
                    $rowdata[] = $info. $action3;

                } else {
                    $rowdata[] = get_string('expireddates', 'mrproject');   
                }



                /*} else {
                    $rowdata[] = '<em>Pending acceptance! </em>'. $this->output->pix_icon('i/progressbar', '');   // i/loading_small
                }*/


            }
            $table->data[] = $rowdata;
        }

        return html_writer::table($table);
    }



    /************************** Meetings held: Attended meetings Table *********************************/


    /**
     * Render a table of meetings. (using the interface 'mrproject_meeting_table' defined in rederable.php)
     *
     * @param mrproject_attendedmeeting_table $meetingtable the table to rended
     * @return string the HTML output
     */
    public function render_mrproject_attendedmeeting_table(mrproject_attendedmeeting_table $meetingtable) {
        $table = new html_table();

        if ($meetingtable->showmeeting) {
            $table->head  = array(get_string('date', 'mrproject'));
            $table->align = array('left');
        }
        if ($meetingtable->showstudent) {
            $table->head[]  = get_string('name');
            $table->align[] = 'left';
        }
        /*if ($meetingtable->showattended) {
            $table->head[] = get_string('seen', 'mrproject');
            $table->align[] = 'center';
        }*/
        if ($meetingtable->showmeeting) {
            $table->head[]  = get_string('meetingwith', 'mrproject'); 
            $table->align[] = 'left';
        }
        if ($meetingtable->showmeeting && $meetingtable->showlocation) {
            $table->head[]  = get_string('location', 'mrproject');
            $table->align[] = 'left';
        }

        /*$table->head[] = get_string('meetingpurpose', 'mrproject');
        $table->align[] = 'left';*/

        if ($meetingtable->showgrades) {
            $table->head[] = get_string('grade', 'mrproject');
            $table->align[] = 'left';
        } else if ($meetingtable->hasotherstudents) {
            $table->head[] = get_string('otherstudents', 'mrproject');
            $table->align[] = 'left';
        }
        if ($meetingtable->showactions) {
            $table->head[] = '';
            $table->align[] = 'right';
        }

        $table->data = array();

        foreach ($meetingtable->meetings as $meeting) {
            $rowdata = array();

            if (isset($meeting->taskid)) {
                $studenturl = new moodle_url($meetingtable->actionurl, array('taskid' => $meeting->taskid));
            }

            $timedata = $this->userdate($meeting->starttime);
            if ($meetingtable->showeditlink) {
                $timedata = $this->action_link($studenturl, $timedata);
            }
            $timedata = html_writer::div($timedata, 'datelabel');

            $starttime = $this->usertime($meeting->starttime);
            $endtime   = $this->usertime($meeting->endtime);
            $timedata .= html_writer::div("{$starttime} &ndash; {$endtime}", 'timelabel');

            if ($meetingtable->showmeeting) {
                $rowdata[] = $timedata;
            }

            /*if ($meetingtable->showstudent) {
                $name = fullname($meeting->student);
                if ($meetingtable->showeditlink) {
                    $name = $this->action_link($studenturl, $name); 
                }
                $rowdata[] = $name;
            }*/

            /*if ($meetingtable->showattended) {
                $iconid = $meeting->attended ? 'ticked' : 'unticked';
                $iconhelp = $meeting->attended ? 'seen' : 'notseen';
                $attendedpix = $this->pix_icon($iconid, get_string($iconhelp, 'mrproject'), 'mod_mrproject');
                $rowdata[] = $attendedpix;
            }*/



            //meeting with
            if ($meetingtable->showmeeting) {

                if ($meeting->cancancel) {      //the current user is teacher  ($meeting->cancancel = isnotstudent)
                    
                    if ($meeting->teacher->id == $meeting->currentuserid) {      //this is my meeting (meeting->teacherid == My id)
                        
                        /*$urlparas = array('id' => $meetingtable->mrproject->cmid,
                        'what' => 'memberspage',
                        'subpage' => 'memberstab',
                        'group' => $meeting->groupid);
                        
                        $url = new moodle_url('/mod/mrproject/view.php', $urlparas);
                        $rowdata[] = html_writer::link($url, get_string('students', 'mrproject'));*/
                        $rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);
                        
                    }
                    else {   //meeting launched by another teacher

                        $teachercaps = ['mod/mrproject:managealltasks']; 
                        $isteacher = has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $meeting->teacher->id); 
                        if ($isteacher) {   //meet with another teacher
                            $rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);
                        
                        } else {    //meeting launched by a student
                            $meetwithteacher = false;
                            $teacherids = array();
                            $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);
                            foreach ($selectedmeeting->get_tasks() as $task) { 
                                if (has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $task->studentid)) {
                                    $meetwithteacher = true;
                                    array_push($teacherids, $task->studentid);
                                    //break;
                                }
                            }
                                
                            if ($meetwithteacher) {    //with teachers
                                $teacherlist = '';
                                foreach ($teacherids as $teacherid) {
                                    $teacherlist .= $this->user_profile_link($meetingtable->mrproject, $meetingtable->mrproject->get_userbyid($teacherid)). '<br/>';
                                }
                                $rowdata[] = $teacherlist;
                            
                            } else {     //between students 
                                $urlparas = array('id' => $meetingtable->mrproject->cmid,
                                'what' => 'memberspage',
                                'subpage' => 'memberstab',
                                'group' => $meeting->groupid);
                                
                                $url = new moodle_url('/mod/mrproject/view.php', $urlparas);
                                $rowdata[] = html_writer::link($url, get_string('students', 'mrproject'));
                            }
                        }   
                    }
                } 

                else {   //the current user is student
                    
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    $isteacher = has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $meeting->teacher->id); 
                    
                    if ($isteacher) {   //meeting launched by a teacher
                        $rowdata[] = $this->user_profile_link($meetingtable->mrproject, $meeting->teacher);
                    } else {   //meeting launched by a student

                        $meetwithteacher = false;
                        $teacherids = array();
                        $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);
                        foreach ($selectedmeeting->get_tasks() as $task) { 
                            if (has_any_capability($teachercaps, $meetingtable->mrproject->get_context(), $task->studentid)) {
                                $meetwithteacher = true;
                                array_push($teacherids, $task->studentid);
                                //break;
                            }
                        }

                        if ($meetwithteacher) {    //with a teacher
                            $teacherlist = '';
                            foreach ($teacherids as $teacherid) {
                                $teacherlist .= $this->user_profile_link($meetingtable->mrproject, $meetingtable->mrproject->get_userbyid($teacherid)). '<br/>';
                            }
                            $rowdata[] = $teacherlist;

                        } else {   //beetween students

                            $urlparas = array('id' => $meetingtable->mrproject->cmid,
                            'what' => 'memberspage',
                            'subpage' => 'memberstab',
                            'group' => $meeting->groupid);
                            
                            $url = new moodle_url('/mod/mrproject/view.php', $urlparas);
                            $rowdata[] = html_writer::link($url, get_string('students', 'mrproject'));
                            
                        }

                    }
                    
                }
                
            }



            //location
            if ($meetingtable->showmeeting && $meetingtable->showlocation) {
                $rowdata[] = format_string($meeting->location);
            }

            /*$notes = '';
            if ($meetingtable->showmeeting && isset($meeting->meetingnote)) {
                $notes .= $this->format_notes($meeting->meetingnote, $meeting->meetingnoteformat,
                                              $meetingtable->mrproject->get_context(), 'meetingnote', $meeting->meetingid);
            }
            $rowdata[] = $notes;*/


            //participants
            if ($meetingtable->showgrades || $meetingtable->hasotherstudents) {
                $gradedata = '';
                if ($meeting->otherstudents) {
                    $gradedata = $this->render($meeting->otherstudents);
                } else if ($meetingtable->showgrades) {
                    $gradedata = $this->format_grade($meetingtable->mrproject, $meeting->grade);
                }
                $rowdata[] = $gradedata;
            }



            $actions = '';

            $teachercaps = ['mod/mrproject:managealltasks']; 
            $isteacher = has_any_capability($teachercaps, $meetingtable->mrproject->get_context());

            if (!$isteacher) {     //Student

                //add meeting report
                $buttonurl = new moodle_url($meetingtable->actionurl, 
                                 array('what' => 'addmeetingreport', 'meetingid' => $meeting->meetingid));
                $button = new single_button($buttonurl, get_string('meetingreportbutton', 'mrproject'));
                $actions = $this->render($button);


                //preview link
                $buttonurl = new moodle_url($meetingtable->actionurl2,
                                array('what' => 'viewmeeting', 'meetingid' => $meeting->meetingid));
                $actions .= html_writer::link($buttonurl, '<br/><strong>'.get_string('preview', 'mrproject').'</strong>');
                $actions .= $this->action_icon($buttonurl, new pix_icon('t/show', get_string('preview', 'mrproject')));
                

            } else {         //Teacher
                //preview meeting report
                $buttonurl = new moodle_url($meetingtable->actionurl,
                                array('what' => 'viewmeeting', 'meetingid' => $meeting->meetingid));
                $button = new single_button($buttonurl, get_string('seedetails', 'mrproject'));
                $actions = $this->render($button);
                $buttonurl2 = new moodle_url($meetingtable->actionurl2,
                                array('what' => 'viewmeeting', 'meetingid' => $meeting->meetingid));
                $actions .= $this->action_icon($buttonurl2, new pix_icon('t/show', get_string('seedetails', 'mrproject')));

                
            }



            //submit task report
            /*$buttonurl = new moodle_url($meetingtable->actionurl,
                             array('what' => 'submittaskreport', 'taskid' => $meeting->taskid));
            $button = new single_button($buttonurl, get_string('submittaskreport', 'mrproject'));
            $actions .= $this->render($button);*/
            

            /*if ($meeting->canedit) {
                $buttonurl = new moodle_url($meetingtable->actionurl,
                                 array('what' => 'editbooking', 'taskid' => $meeting->taskid));
                $button = new single_button($buttonurl, get_string('editbooking', 'mrproject'));
                $actions .= $this->render($button);
            }*/
            //if ($meeting->canview) {
                
            //}
            /*if ($meeting->cancancel) {
                $buttonurl = new moodle_url($meetingtable->actionurl,
                                 array('what' => 'cancelbooking', 'meetingid' => $meeting->meetingid));
                $button = new single_button($buttonurl, get_string('cancelbooking', 'mrproject'));
                $actions .= $this->render($button);
            }*/
            $rowdata[] = $actions;

            
            $table->data[] = $rowdata;
        }

        return html_writer::table($table);
    }




    /**
     * Render a command bar.
     *
     * @param mrproject_command_bar $commandbar
     * @return string
     */
    public function render_mrproject_command_bar(mrproject_command_bar $commandbar) {
        $o = '';
        foreach ($commandbar->linkactions as $id => $action) {
            $this->add_action_handler($action, $id);
        }
        $o .= html_writer::start_div('commandbar');
        if ($commandbar->title) {
            $o .= html_writer::span($commandbar->title, 'title');
        }
        foreach ($commandbar->menus as $m) {
            $o .= $this->render($m);
        }
        $o .= html_writer::end_div();
        return $o;
    }




/**********************************My meeting tab: Students list in a meeting*****************************************/

    /**
     * Rendering a list of student, to be displayed within a larger table
     *
     * @param mrproject_student_list $studentlist
     * @return string
     */
    public function render_mrproject_student_list(mrproject_student_list $studentlist) {

        $o = '';
        $o .= $studentlist->group;
        

        $toggleid = html_writer::random_id('toggle');

        if ($studentlist->expandable && count($studentlist->students) > 0) {
            
            $this->page->requires->yui_module('moodle-mod_mrproject-studentlist',
                            'M.mod_mrproject.studentlist.init',
                            array($toggleid, (boolean) $studentlist->expanded) );
            $imgclass = 'studentlist-togglebutton';
            $alttext = get_string('showparticipants', 'mrproject');
            $o .= $this->output->pix_icon('t/switch', $alttext, 'moodle',
                            array('id' => $toggleid, 'class' => $imgclass));
        }

        $divprops = array('id' => 'list'.$toggleid);
        $o .= html_writer::start_div('studentlist', $divprops);
        if (count($studentlist->students) > 0) {
            $editable = $studentlist->actionurl && $studentlist->editable;
            if ($editable) {
                $o .= html_writer::start_tag('form', array('action' => $studentlist->actionurl,
                                'method' => 'post', 'class' => 'studentselectform'));
            }

            

            //add student
            $filterarray = array();
            foreach ($studentlist->students as $student) {

                if (!in_array($student->user, $filterarray)) {
                    $class = 'otherstudent';
                    $checkbox = '';
                    //if ($studentlist->checkboxname) {
                        //if ($student->editattended) {
                            /*$checkbox = html_writer::checkbox($studentlist->checkboxname, $student->entryid, false, '',
                                            array('class' => 'studentselect'));*/
                        /*} else {
                            $img = $student->checked ? 'ticked' : 'unticked';
                            $checkbox = $this->render(new pix_icon($img, '', 'mrproject', array('class' => 'statictickbox')));
                        }*/
                    //}
                    if ($studentlist->linktask) {
                        $name = $this->task_link($studentlist->mrproject, $student->user, $student->entryid);
                    } else {
                        //$name = fullname($student->user);
                        if ($student->user) {
                            $name = $this->user_profile_link($studentlist->mrproject, $student->user);
                        }
                    }
                    $studicons = '';
                    $studprovided = array();
                    if ($student->notesprovided) {
                        $studprovided[] = get_string('message', 'mrproject');
                    }
                    if ($student->filesprovided) {
                        $studprovided[] = get_string('nfiles', 'mrproject', $student->filesprovided);
                    }
                    if ($studprovided) {
                        $providedstr = implode(', ', $studprovided);
                        $alttext = get_string('studentprovided', 'mrproject', $providedstr);
                        $attachicon = new pix_icon('attachment', $alttext, 'mrproject', array('class' => 'studdataicon'));
                        $studicons .= $this->render($attachicon);
                    }

                    if ($student->highlight) {
                        $class .= ' highlight';
                    }
                    if ($student->user) {
                        $picture = $this->user_picture($student->user, array('courseid' => $studentlist->mrproject->courseid));
                    }
                    $grade = '';
                    if ($studentlist->showgrades && $student->grade) {
                        $grade = $this->format_grade($studentlist->mrproject, $student->grade, true);
                    }

                    if ($student->highlight) {    //teacher

                        if ($student->user->id > 0) {
                            global $DB;
                            $multiroles = $DB->get_field('groups_members', 'multiroles', array('userid' => $student->user->id, 'groupid' => $studentlist->groupid));
                        }

                        if ($multiroles != null && $multiroles != '') {
                            $o .= html_writer::div( $picture . ' ' . $name . '&nbsp;(<u>'.$multiroles.'</u>)' . $studicons . ' ' . $grade, $class);
                        } else {
                            $o .= html_writer::div( $picture . ' ' . $name . get_string('withsupervisor', 'mrproject') . $studicons . ' ' . $grade, $class);
                        }

                    } else {   //student
                        $o .= html_writer::div( $picture . ' ' . $name . $studicons . ' ' . $grade, $class);
                    }

                    
                    array_push($filterarray, $student->user);
                }
                
            }

            if ($editable) {
                $o .= html_writer::empty_tag('input', array(
                                'type' => 'submit',
                                'class' => 'studentselectsubmit',
                                'value' => $studentlist->buttontext
                ));
                $o .= html_writer::end_tag('form');
            }
        }
        $o .= html_writer::end_div();

        return $o;
    }






/********************************** All tasks of this student *****************************************/


    /**
     * Render a table of meetings. (using the interface 'mrproject_task_table' defined in rederable.php)
     *
     * @param mrproject_task_table $meetingtable the table to rended
     * @return string the HTML output
     */
    public function render_mrproject_task_table(mrproject_task_table $meetingtable) {
        $table = new html_table();

        if ($meetingtable->showmeeting) {
            $table->head  = array(get_string('date', 'mrproject'));
            $table->align = array('left');
        }
        if ($meetingtable->showstudent) {
            $table->head[]  = get_string('name');
            $table->align[] = 'left';
        }
        
        if ($meetingtable->showmeeting && $meetingtable->showlocation) {
            $table->head[]  = get_string('location', 'mrproject');
            $table->align[] = 'left';
        }

        $table->head[] = get_string('what', 'mrproject');
        $table->align[] = 'left';

        if ($meetingtable->showgrades) {
            $table->head[] = get_string('grade', 'mrproject');
            $table->align[] = 'left';
        } else if ($meetingtable->hasotherstudents) {
            $table->head[] = get_string('otherstudents', 'mrproject');
            $table->align[] = 'left';
        }
        if ($meetingtable->showactions) {
            $table->head[] = '';
            $table->align[] = 'right';
        }

        $table->data = array();

        $tasknumber = 1;
        foreach ($meetingtable->meetings as $meeting) {
            $rowdata = array();

            $studenturl = new moodle_url($meetingtable->actionurl, array('taskid' => $meeting->taskid));

            $selectedmeeting = $meetingtable->mrproject->get_meeting($meeting->meetingid);    //$info->meeting->meetingid
            
            //$selectedtask = $meeting->get_task($meeting->taskid);

            $selectedtask = $selectedmeeting->get_task($meeting->taskid);

            
            $startingdate = $this->usershortdate($selectedtask->startingdate);
            $duedate = $this->usershortdate($selectedtask->duedate);
            
            $timedata = html_writer::div("{$this->action_link($studenturl, '<strong>Task ('.$tasknumber.'):</strong>')} <br/> <u>Starting date:</u> {$startingdate} <br/><u>Due date:</u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {$duedate}", 'datelabel');

            //Date
            if ($meetingtable->showmeeting) {
                $rowdata[] = $timedata;
            }



            if ($meetingtable->showstudent) {
                $name = fullname($meeting->student);
                if ($meetingtable->showeditlink) {
                    $name = $this->action_link($studenturl, $name);
                }
                $rowdata[] = $name;
            }

            

            if ($meetingtable->showmeeting && $meetingtable->showlocation) {
                $rowdata[] = format_string($meeting->location);
            }

            
            //Defined task
            $notes = '';
            $notes .= $this->format_task_notes($meetingtable->mrproject, $meeting, 'taskid');
            $rowdata[] =  html_writer::div($notes, 'definedtask');
            //$rowdata[] = $notes;


            //Grade
            $rowdata[] = '<strong>'.$meeting->grade.'</strong>';


            

            $table->data[] = $rowdata;
            $tasknumber ++;
        }

        return html_writer::table($table);
    }



/**********************************My meetings tab: Meetings table**************************************/

    /**
     * Render a meeting manager.
     *
     * @param mrproject_meeting_manager $meetingman
     * @return string
     */
    public function render_mrproject_meeting_manager(mrproject_meeting_manager $meetingman) {

        $this->page->requires->yui_module('moodle-mod_mrproject-saveseen',
                        'M.mod_mrproject.saveseen.init', array($meetingman->mrproject->cmid) );

        $o = '';

        $table = new html_table();
        $table->head  = array(get_string('proposed', 'mrproject'), get_string('start', 'mrproject'),
                        get_string('end', 'mrproject'), get_string('location', 'mrproject'), get_string('otherstudents', 'mrproject') );
        $table->align = array ('left', 'left', 'left', 'left', 'left', 'left');
        if ($meetingman->showteacher) {
            $table->head[] = get_string('meetingwith', 'mrproject'); 
            $table->align[] = 'left';
        }
        $table->head[] = get_string('action', 'mrproject');
        $table->align[] = 'center';

        $table->id = 'meetingmanager';
        $table->data = array();


        foreach ($meetingman->meetings as $meeting) {

            $rowdata = array();


            $startdatestr1 = '';
            $starttimestr1 = '';
            $endtimestr1 = '';
            //proposeddate1
            if ($meeting->proposeddate1 != 0) {
                $startdatestr1 = $this->userdate($meeting->proposeddate1);
                $starttimestr1 = $this->usertime($meeting->proposeddate1);
                $endtimestr1 = $this->usertime($meeting->proposeddate1 + $meeting->duration * MINSECS); 
            }

            $startdatestr2 = '';
            $starttimestr2 = '';
            $endtimestr2 = '';
            //proposeddate2
            if ($meeting->proposeddate2 != 0) {
                $startdatestr2 = $this->userdate($meeting->proposeddate2);
                $starttimestr2 = $this->usertime($meeting->proposeddate2);
                $endtimestr2 = $this->usertime($meeting->proposeddate2 + $meeting->duration * MINSECS); 
            }

            $startdatestr3 = '';
            $starttimestr3 = '';
            $endtimestr3 = '';
            //proposeddate3
            if ($meeting->proposeddate3 != 0) {
                $startdatestr3 = $this->userdate($meeting->proposeddate3);
                $starttimestr3 = $this->usertime($meeting->proposeddate3);
                $endtimestr3 = $this->usertime($meeting->proposeddate3 + $meeting->duration * MINSECS); 
            }


            //dates
            $rowdata[] = $startdatestr1 .'<br/>'. $startdatestr2 .'<br/>'. $startdatestr3;

            //start time
            $rowdata[] = $starttimestr1 .'<br/>'. $starttimestr2 .'<br/>'. $starttimestr3;

            //end time
            $rowdata[] = $endtimestr1 .'<br/>'. $endtimestr2 .'<br/>'. $endtimestr3;



            //location
            $rowdata[] = format_string($meeting->location);



            //Students
            $rowdata[] = $this->render($meeting->students);


            if ($meetingman->showteacher) {
                $rowdata[] = $this->user_profile_link($meetingman->mrproject, $meeting->teacher);
            }


            //Actions
            $actions = '';
            //if ($meeting->editable) {
                $url = new moodle_url($meetingman->actionurl, array('what' => 'updatemeeting', 'meetingid' => $meeting->meetingid, 'groupid' => $meetingman->groupid));  //params submitted when opening forms
                $actions .= $this->action_icon($url, new pix_icon('t/edit', get_string('edit')));

                $url = new moodle_url($meetingman->actionurl, array('what' => 'deletemeeting', 'meetingid' => $meeting->meetingid));
                $confirmdelete = new confirm_action(get_string('confirmdelete-one', 'mrproject'));
                $actions .= $this->action_icon($url, new pix_icon('t/delete', get_string('delete')), $confirmdelete);
                
            //}
            $rowdata[] = $actions;


            //Add data to the table
            $table->data[] = $rowdata;

        }
        $o .= html_writer::table($table);

        return $o;
    }




/****************************My meetings tab: Plan student team meetings*******************************/
    /**
     * Render a scheduling list.
     *
     * @param mrproject_scheduling_list $list
     * @return string
     */
    public function render_mrproject_scheduling_list(mrproject_scheduling_list $list) {

        $mtable = new html_table();

        $mtable->id = $list->id;
        $mtable->head[]  = '';
        $mtable->head[]  = get_string('teamname', 'mrproject');
        $mtable->head[]  = get_string('studentlist', 'mrproject');
        $mtable->align = array ('center', 'left');
        foreach ($list->extraheaders as $field) {
            $mtable->head[] = $field;
            $mtable->align[] = 'left';
        }
        
        $mtable->head[] = get_string('action', 'mrproject');
        $mtable->align[] = 'center';

        $mtable->data = array();
        foreach ($list->lines as $line) {
            $data = array($line->pix, $line->name, $line->memberslist);
            foreach ($line->extrafields as $field) {
                $data[] = $field;
            }
            $actions = '';
            if ($line->actions) {
                $menu = new action_menu($line->actions);
                $menu->actiontext = get_string('planmeeting', 'mrproject');
                $actions = $this->render($menu);
            }
            $data[] = $actions;
            $mtable->data[] = $data;
        }
        return html_writer::table($mtable);
    }


/************************************* Grade ***********************************************************/

    /**
     * Render total grade information.
     *
     * @param mrproject_totalgrade_info $gradeinfo
     * @return string
     */
    public function render_mrproject_totalgrade_info(mrproject_totalgrade_info $gradeinfo) {
        $items = array();

        if ($gradeinfo->showtotalgrade) {
            //$items[] = array('gradingstrategy', $this->format_grading_strategy($gradeinfo->mrproject->gradingstrategy));
            //$items[] = array('totalgrade', '<strong>'.$this->format_grade($gradeinfo->mrproject, $gradeinfo->totalgrade).'</strong>');
            $items[] = array('totalgrade', '<strong>' . $gradeinfo->totalgrade . '</strong>');
        }

        if (!is_null($gradeinfo->gbgrade)) {
            $gbgradeinfo = $this->format_grade($gradeinfo->mrproject, $gradeinfo->gbgrade->grade);
            $attributes = array();
            if ($gradeinfo->gbgrade->hidden) {
                $attributes[] = get_string('hidden', 'grades');
            }
            if ($gradeinfo->gbgrade->locked) {
                $attributes[] = get_string('locked', 'grades');
            }
            if ($gradeinfo->gbgrade->overridden) {
                $attributes[] = get_string('overridden', 'grades');
            }
            if (count($attributes) > 0) {
                $gbgradeinfo .= ' ('.implode(', ', $attributes) .')';
            }
            //$items[] = array('gradeingradebook', $gbgradeinfo);
        }

        $o = html_writer::start_div('totalgrade');
        $o .= html_writer::start_tag('dl', array('class' => 'totalgrade'));
        foreach ($items as $item) {
            $o .= html_writer::tag('dt', get_string($item[0], 'mrproject'));
            $o .= html_writer::tag('dd', $item[1]);
        }
        $o .= html_writer::end_tag('dl');
        $o .= html_writer::end_div('totalgrade');
        return $o;
    }

    /**
     * Render a conflict list.
     *
     * @param mrproject_conflict_list $cl
     * @return string
     */
    public function render_mrproject_conflict_list(mrproject_conflict_list $cl) {

        $o = html_writer::start_tag('ul');

        foreach ($cl->conflicts as $conflict) {
            $a = new stdClass();
            $a->datetime = userdate($conflict->starttime);
            $a->duration = $conflict->duration;
            if ($conflict->isself) {
                $entry = get_string('conflictlocal', 'mrproject', $a);
            } else {
                $a->courseshortname = $conflict->courseshortname;
                $a->coursefullname = $conflict->coursefullname;
                $a->mrprojectname = format_string($conflict->mrprojectname);
                $entry = get_string('conflictremote', 'mrproject', $a);
            }
            $o .= html_writer::tag('li', $entry);
        }

        $o .= html_writer::end_tag('ul');

        return $o;
    }



/************************************ View meeting report *******************************************/

    /**
     * Render a table containing information about a booked task
     *
     * @param mrproject_meeting_report $ai
     * @return string
     */
    public function render_mrproject_meeting_report(mrproject_meeting_report $ai) {
        $o = '';
        $o .= $this->output->container_start('taskinfotable');

        $o .= $this->output->box_start('boxaligncenter taskinfotable');

        $t = new html_table();       //meeting infos
        $t->align[0] = 'left';

        $t1 = new html_table();      //meeting outcomes (header)
        $t1->align[0] = 'center';
        $t2 = new html_table();      //meeting outcomes (content)
        $t2->align[0] = 'left';

        $t3 = new html_table();      //defined tasks (header)
        $t3->align[0] = 'center';
        $t4 = new html_table();      //defined tasks (content)
        $t4->align[0] = 'left';

        $t5 = new html_table();      //Teacher feedback (header)
        $t5->align[0] = 'center';
        $t6 = new html_table();      //Teacher feedback (content)
        $t6->align[0] = 'left';
        

            

            //Date & time
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('meetingdatetimelabel', 'mrproject').'</strong>');
            $data = self::meetingdatetime($ai->meeting->starttime, $ai->meeting->duration);
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell2 = new html_table_cell(get_string('meetingdatetimelong', 'mrproject', $data).$blankspace.$blankspace.$blankspace);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;



            

            //Meeting with --> cell2
            /*$teachercaps = ['mod/mrproject:managealltasks']; 
            $isteacher = has_any_capability($teachercaps, $ai->mrproject->get_context(), $ai->meeting->get_teacher()->id);
            if ($isteacher) {   //meeting launched by a teacher
                $cell2 = new html_table_cell(fullname($ai->meeting->get_teacher()));

            } else {      //meeting launched by a student
                $meetwithteacher = false;
                $selectedmeeting = $ai->mrproject->get_meeting($ai->meeting->meetingid);    //$info->meeting->meetingid
                foreach ($selectedmeeting->get_tasks() as $task) { 
                    if (has_any_capability($teachercaps, $ai->mrproject->get_context(), $task->studentid)) {
                        $meetwithteacher = true;
                        break;
                    }
                }
                if ($meetwithteacher) {    //with a teacher
                    $cell2 = new html_table_cell(fullname($ai->mrproject->get_userbyid($task->studentid)));
                    
                } else {    //between students
                    $cell2 = new html_table_cell(get_string('students', 'mrproject'));
                }     
            }*/

            



            //Location
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('location', 'mrproject').'</strong>');
            $cell2 = new html_table_cell(format_string($ai->meeting->tasklocation));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;


            //Meeting purpose
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('meetingpurpose', 'mrproject').'</strong>');
            $notes = $this->format_notes($ai->meeting->meetingpurpose, $ai->meeting->meetingpurposeformat, $ai->mrproject->get_context(),
                                          'meetingpurpose', $ai->meeting->id);
            $cell2 = new html_table_cell($notes);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
            



            //Attendees
            $row = new html_table_row();
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell1 = new html_table_cell('<strong>'.get_string('attendees', 'mrproject').$blankspace.'</strong>');
     
            //participants list
            $groupmembers = array();
            $studentids = array();
            $waspresent = array();
            $wasabsent = array();
            $selectedmeeting = $ai->mrproject->get_meeting($ai->meeting->meetingid);
            foreach ($selectedmeeting->get_tasks() as $task) {
                if ($task->studentid != 0) {    //meet launched by a student
                    
                    if (!in_array($task->studentid, $studentids)) {    //to eliminate duplicates students
                        array_push($studentids, $task->studentid);

                        //add a participant
                        if ($task->attended) {
                            $member = $ai->mrproject->get_userbyid($task->studentid);
                            $waspresent[] = fullname($member);
                        } else {
                            $member = $ai->mrproject->get_userbyid($task->studentid);
                            $wasabsent[] = fullname($member);
                        }
                    }
                    
                    
                } else {      //meet launched by a teacher
                    if (!in_array($task->studentid, $studentids)) {    //to eliminate duplicates students
                        array_push($studentids, $task->studentid);

                        //add a participant
                        if ($task->attended) {
                            $member = $ai->mrproject->get_userbyid($selectedmeeting->teacherid);
                            $waspresent[] = fullname($member);
                        } else {
                            $member = $ai->mrproject->get_userbyid($selectedmeeting->teacherid);
                            $wasabsent[] = fullname($member);
                        }
                    }
                    
                }
            }
            $present = implode(', ', $waspresent);
            $absent = implode(', ', $wasabsent);

            $cell2 = new html_table_cell(get_string('waspresent', 'mrproject').'<strong>[ </strong>'.$present.'<strong> ]</strong>'  .'<br/>'.  
                                         get_string('wasabsent', 'mrproject').'<strong>[ </strong>'.$absent.'<strong> ]</strong>');
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;




            //Meeting outcomes (header)
            $row = new html_table_row();
            //$cell1 = new html_table_cell('<strong>'.get_string('meetingoutcomes', 'mrproject').'</strong>');
            $cell1 = '<big><strong>'.get_string('meetingoutcomes', 'mrproject'). '</strong></big>  ';
            $cell1 .= $this->output->render (new pix_icon('e/file-text', ''));
            //$cell1->header = true;
            $row->cells = array($cell1);
            $t1->data[] = $row;


            //Meeting outcomes (content)
            $row = new html_table_row();
            $notes = $this->format_notes($ai->meeting->meetingoutcomes, $ai->meeting->meetingoutcomesformat, $ai->mrproject->get_context(),
                                          'meetingoutcomes', $ai->meeting->id);
            $cell1 = new html_table_cell($notes);
            $row->cells = array($cell1);
            $t2->data[] = $row;

        


            //Defined tasks (header)
            $row = new html_table_row();
            //$cell1 = new html_table_cell('<strong>'.get_string('what', 'mrproject').'</strong>');
            $cell1 = '<big><strong>'.get_string('what', 'mrproject').'</strong></big>  ';
            $cell1 .= $this->output->render (new pix_icon('i/outcomes', ''));
            //$cell1->header = true;
            $row->cells = array($cell1);
            $t3->data[] = $row;


            //defined tasks (content)
            $tasknumber = 1;
            $teachercaps = ['mod/mrproject:managealltasks']; 
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            foreach ($selectedmeeting->get_tasks() as $task) {
                if ($task->studentid != 0) {
                    $isteacher = has_any_capability($teachercaps, $ai->mrproject->get_context(), $task->studentid);
                    if (!$isteacher) {
                        $row = new html_table_row();
                        $timeformat = get_string('strftimedatetimeshort');
                        $startingdate = $this->usershortdate($task->startingdate, '%d/%m/%Y');
                        $duedate = $this->usershortdate($task->duedate, '%d/%m/%Y');
                        $cell1 = new html_table_cell('<strong> Task('.$tasknumber.')'.$blankspace.'</strong><br/><u><em>From:</u> '. $startingdate.'</em>'.'<br/><u><em>To:</u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$duedate.'</em>');
                        $notes = $this->format_notes($task->tasknote, $task->tasknoteformat, $ai->mrproject->get_context(),
                                                    'tasknote', $ai->meeting->id);

                        if ($task->collectivetask == null || $task->collectivetask == '0') {    //Individual task
                            $assignedto =  fullname($ai->mrproject->get_userbyid($task->studentid));

                        } else {   //Collective task
                            $studentids = explode('+' ,$task->collectivetask);
                            $assignedto = '';
                            foreach ($studentids as $student) {
                                $assignedto .= fullname($ai->mrproject->get_userbyid($student)) .'; &nbsp;';
                            }
                        }

                        $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        $cell2 = new html_table_cell('<strong>Assigned to:</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'. $assignedto .'<br/><u>Task description:</u>' .$blankspace.$blankspace.$blankspace.$blankspace.$blankspace.$blankspace. $notes);
                        $row->cells = array($cell1, $cell2);
                        $t4->data[] = $row;

                        $tasknumber ++;
                    }
                }
            }


/******************************************************************************/




            //teacher feedback (header)
            $row = new html_table_row();
            $cell1 = '<big><strong>'.get_string('yourteachernote', 'mrproject').'</strong></big>  ';
            $cell1 .= $this->output->render (new pix_icon('i/group', ''));
            //$cell1->header = true;
            $row->cells = array($cell1);
            $t5->data[] = $row;


            //teacher feedback (content)
            $row = new html_table_row();
            $notes = $this->format_notes($ai->meeting->feedbackbyteacher, $ai->meeting->feedbackbyteacherformat, $ai->mrproject->get_context(),
                                          'feedbackbyteacher', $ai->meeting->id);
            $cell1 = new html_table_cell($notes);
            $row->cells = array($cell1);
            $t6->data[] = $row;


            //last line
            $row = new html_table_row();
            $cell1 = new html_table_cell('');
            $row->cells = array($cell1);
            $t6->data[] = $row;
        
            
        

        if ($ai->showstudentdata) {
            
            //if ($ai->mrproject->uses_studentfiles()) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('studentfiles', 'mrproject'));
                $att = $this->render_attachments($ai->mrproject->context->id, 'studentfiles', $ai->task->id);
                $cell2 = new html_table_cell($att);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            //}
        }

        if ($ai->showresult) {
            if ($ai->mrproject->uses_tasknotes() && $ai->task->tasknote) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('tasknote', 'mrproject'));
                $note = $this->format_notes($ai->task->tasknote, $ai->task->tasknoteformat,
                                            $ai->mrproject->get_context(), 'tasknote', $ai->task->id);
                $cell2 = new html_table_cell($note);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
            if ($ai->mrproject->uses_grades()) {
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('grade', 'mrproject'));
                $gradetext = $this->format_grade($ai->mrproject, $ai->task->grade, false);
                $cell2 = new html_table_cell($gradetext);
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }

        $o .= html_writer::table($t);    //meeting infos

        $o .= html_writer::table($t1);   //meeting outcomes (header)
        $o .= html_writer::table($t2);   //meeting outcomes (content)

        $o .= html_writer::table($t3);   //defined tasks (header)
        $o .= html_writer::table($t4);   //defined tasks (content)

        $o .= html_writer::table($t5);   //teacher feedback (header)
        $o .= html_writer::table($t6);   //teacher feedback (content)

        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }


/************************** individual deliverables: Submit task report + dependencies ***********************************/

    //Completion rate (progress bar)
    public function emojiPercentBar($done, $total=100)
    {
        $green=html_entity_decode('&#x1F7E9;', 0, 'UTF-8');
        $white=html_entity_decode('&#x2B1C;', 0, 'UTF-8');

        $perc = round(($done * 100) / $total);
        $bar = round((10 * $perc) / 100);

        return sprintf("%s%s", str_repeat($green, $bar), str_repeat($white, 10-$bar));

    }


    /**
     * Render a table containing information about a booked task
     *
     * @param mrproject_task_info $ai
     * @return string
     */
    public function render_mrproject_task_info(mrproject_task_info $ai) {
        $o = '';
        $o .= $this->output->container_start('taskinfotable');

        $o .= $this->output->box_start('boxaligncenter taskinfotable');

        $t = new html_table();

        

            //starting date
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('startingdate', 'mrproject').'</strong>');
            $data = userdate($ai->task->startingdate);
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell2 = new html_table_cell($data .$blankspace.$blankspace.$blankspace.$blankspace);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;


            //due date
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('duedate', 'mrproject').'</strong>');
            $data = userdate($ai->task->duedate);
            $cell2 = new html_table_cell($data);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;



            //submission date
            
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('submissiondate', 'mrproject').'</strong><br/>');
            if ($ai->task->submissiondate != 0  &&  $ai->task->submissiondate != null) {
                if ($ai->task->submissiondate <= $ai->task->duedate) {
                    $cell2 = userdate($ai->task->submissiondate) . html_writer::link('', get_string('submittedontime', 'mrproject'), array('class' => 'submittedtask'));
                } else {
                    $cell2 = userdate($ai->task->submissiondate) . html_writer::link('', get_string('afterdeadline', 'mrproject'), array('class' => 'timedouttask'));
                }
            } else {
                $cell2 = '';
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
            


            //Task description
            $row = new html_table_row();
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell1 = new html_table_cell('<strong>'.get_string('tasknote', 'mrproject').'</strong><br/>' .$blankspace);
            $note = $this->format_notes($ai->task->tasknote, $ai->task->tasknoteformat,
                                        $ai->mrproject->get_context(), 'tasknote', $ai->task->id);
            $cell2 = new html_table_cell($note);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;



            $teachercaps = ['mod/mrproject:managealltasks']; 
            $isteacher = has_any_capability($teachercaps, $ai->mrproject->get_context());
            if ($isteacher) {
                //Completion rate
                $row = new html_table_row();
                $cell1 = new html_table_cell('<strong>'.get_string('completionrate', 'mrproject').'</strong><br/>');
                if ($ai->task->completionrate != 0) {
                    $cell2 = new html_table_cell($ai->task->completionrate .' % '. emojiPercentBar($ai->task->completionrate, 100) );
                } else {
                    $cell2 = '';
                }
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }


            //Uploaded files
            $row = new html_table_row();
            $cell1 = '<strong>'.get_string('studentfiles', 'mrproject').'</strong>  ';
            $cell1 .= $this->output->render (new pix_icon('attachment', '', 'mrproject', array('class' => 'studdataicon')));
            $att = $this->render_attachments($ai->mrproject->context->id, 'studentfiles', $ai->task->id);
            $cell2 = new html_table_cell($att);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;



            //Final grade
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('finalgrade', 'mrproject').'</strong>');
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell2 = '';
            if ($ai->task->grade != null) {
                $cell2 = new html_table_cell($ai->task->grade .$blankspace.$blankspace.$blankspace.$blankspace.$blankspace);
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;


            //Note (Appreciation)
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('studentnote', 'mrproject').'</strong>');
            $cell2 = new html_table_cell($ai->task->studentnote);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }



    /************************************ viewstudent: This task *******************************************/


    /**
     * Render a table containing information about a booked task
     *
     * @param mrproject_submitted_task $ai
     * @return string
     */
    public function render_mrproject_submitted_task (mrproject_submitted_task $ai) {
        global $DB, $USER;

        $o = '';
        $o .= $this->output->container_start('taskinfotable');

        $o .= $this->output->box_start('boxaligncenter taskinfotable');

        $t0 = new html_table();    //Submitted task (header)
        $t0->align[0] = 'center';
        $t1 = new html_table();    //Submitted task

        $t2 = new html_table();    //Learning experience (header)
        $t2->align[0] = 'center';
        $t3 = new html_table();    //Learning experience
        //$t3->align[0] = 'center';

        $t4 = new html_table();    //Evaluation (header)
        $t4->align[0] = 'center';
        $t5 = new html_table();    //Evaluation
        
        


            //Submitted task (header)
            $row = new html_table_row();
            $cell1 = '<big><strong><u>'.get_string('submittedtask', 'mrproject').'</u></strong></big>  ';
            $cell1 .= $this->output->render (new pix_icon('a/view_icon_active', '')); 
            $row->cells = array($cell1); 
            $t0->data[] = $row;

        

            //starting date
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('startingdate', 'mrproject').'</strong>');
            $data = userdate($ai->task->startingdate);
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell2 = new html_table_cell($data .$blankspace.$blankspace.$blankspace.$blankspace);
            $row->cells = array($cell1, $cell2);
            $t1->data[] = $row;


            //due date
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('duedate', 'mrproject').'</strong>');
            $data = userdate($ai->task->duedate);
            $cell2 = new html_table_cell($data);
            $row->cells = array($cell1, $cell2);
            $t1->data[] = $row;



            //submission date
            $teachercaps = ['mod/mrproject:managealltasks']; 
            $isteacher = has_any_capability($teachercaps, $ai->mrproject->get_context());
            if ($isteacher) {
                $row = new html_table_row();
                $cell1 = new html_table_cell('<strong>'.get_string('submissiondate', 'mrproject').'</strong><br/>');
                if ($ai->task->submissiondate != 0  &&  $ai->task->submissiondate != null) {
                    if ($ai->task->submissiondate <= $ai->task->duedate) {
                        $cell2 = userdate($ai->task->submissiondate) . html_writer::link('', get_string('submittedontime', 'mrproject'), array('class' => 'submittedtask'));
                    } else {
                        $cell2 = userdate($ai->task->submissiondate) . html_writer::link('', get_string('afterdeadline', 'mrproject'), array('class' => 'timedouttask'));
                    }
                } else {
                    $cell2 = '';
                }
                $row->cells = array($cell1, $cell2);
                $t1->data[] = $row;
            }
            


            //Task description
            $row = new html_table_row();
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell1 = new html_table_cell('<strong>'.get_string('tasknote', 'mrproject').'</strong><br/>' .$blankspace);
            $note = $this->format_notes($ai->task->tasknote, $ai->task->tasknoteformat,
                                        $ai->mrproject->get_context(), 'tasknote', $ai->task->id);
            $cell2 = new html_table_cell($note);
            $row->cells = array($cell1, $cell2);
            $t1->data[] = $row;



            $teachercaps = ['mod/mrproject:managealltasks']; 
            $isteacher = has_any_capability($teachercaps, $ai->mrproject->get_context());
            if ($isteacher) {
                //Completion rate
                $row = new html_table_row();
                $cell1 = new html_table_cell('<strong>'.get_string('completionrate', 'mrproject').'</strong><br/>');
                if ($ai->task->completionrate != 0) {
                    $cell2 = new html_table_cell($ai->task->completionrate .' % '. emojiPercentBar($ai->task->completionrate, 100) );
                } else {
                    $cell2 = '';
                }
                $row->cells = array($cell1, $cell2);
                $t1->data[] = $row;


                
            }


            //Uploaded files
            $row = new html_table_row();
            $cell1 = '<strong>'.get_string('studentfiles', 'mrproject').'</strong>  ';
            $cell1 .= $this->output->render (new pix_icon('attachment', '', 'mrproject', array('class' => 'studdataicon')));
            $att = $this->render_attachments($ai->mrproject->context->id, 'studentfiles', $ai->task->id);
            $cell2 = new html_table_cell($att);
            $row->cells = array($cell1, $cell2);
            $t1->data[] = $row;




            //Learning experience (header)
            $row = new html_table_row();
            $cell1 = '<big><u><strong>'.get_string('studentlearningexperience', 'mrproject').'</strong></u></big>  ';
            $cell1 .= $this->output->render (new pix_icon('i/siteevent', '')); 
            $row->cells = array($cell1);
            $t2->data[] = $row;



            //dependency, link, consumed time (header)
            $row = new html_table_row();
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell1 = new html_table_cell($blankspace.'<br/><strong><em>'.get_string('dependency', 'mrproject').'</em></strong>');
            
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell2 = new html_table_cell($blankspace.'<br/><strong><em>'.get_string('consumedtime', 'mrproject').'</em></strong>');
            
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell3 = new html_table_cell($blankspace.'<br/><strong><em>'.get_string('link', 'mrproject').'</em></strong>');
            
            $row->cells = array($cell1, $cell2, $cell3);
            $t3->data[] = $row;



            //dependencies list
            $dependencynumber = 1;
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $dependencies = $DB->get_records('mrproject_dependency', ['taskid' => $ai->task->taskid]);   //Get a number of records as an array
            foreach ($dependencies as $dependency) {
                $row = new html_table_row();
                $cell1 = new html_table_cell($dependency->dependency);
                //$cell1->header = true;

                $cell2 = new html_table_cell($dependency->consumedtime. ' <small>minutes</small>');
                
                $recommendurl = new moodle_url($ai->actionurl,
                                array('what' => 'recommendthisdependency', 'dependencyid' => $dependency->id, 'taskid' => $ai->task->id, 'studentid' => $ai->student));

                
                //get recommendations
                $recommended = $DB->get_field('mrproject_recommendation', 'id', array('dependencyid' => $dependency->id, 'recommendedby' => $USER->id));
            
                if (!$recommended) {     
                    $cell3 = html_writer::link(new moodle_url($dependency->link), $dependency->link .'<br/><br/>'
                                                . html_writer::link($recommendurl, get_string('recommend', 'mrproject') . '&nbsp;' .$this->pix_icon('t/approve', get_string('exportgrades', 'mrproject')),  ['class' => 'recommend']), array('target' => '_blank', 'rel' => 'noopener'));  
                    
                } else {
                    $cell3 = html_writer::link(new moodle_url($dependency->link), $dependency->link .'<br/><br/>'
                                                . html_writer::link($recommendurl, get_string('recommended', 'mrproject') . '&nbsp;' .$this->pix_icon('i/star-rating', get_string('exportgrades', 'mrproject')),  ['class' => 'recommended']), array('target' => '_blank', 'rel' => 'noopener'));  
                    
                }

                $row->cells = array($cell1, $cell2, $cell3);
                $t3->data[] = $row;
                $dependencynumber ++;
            }



            //Evaluation (header)
            $row = new html_table_row();
            $cell1 = '<big><strong><u>'.get_string('evaluation', 'mrproject').'</u></strong></big>  ';
            $cell1 .= $this->output->render (new pix_icon('i/user', ''));
            $row->cells = array($cell1);
            $t4->data[] = $row;



            //Final grade
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('finalgrade', 'mrproject').'</strong>');
            $blankspace = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $cell2 = '';
            if ($ai->task->grade != null) {
                $cell2 = new html_table_cell($ai->task->grade .$blankspace.$blankspace.$blankspace.$blankspace.$blankspace);
            }
            $row->cells = array($cell1, $cell2);
            $t5->data[] = $row;


            //Note (Appreciation)
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('studentnote', 'mrproject').'</strong>');
            $cell2 = new html_table_cell($ai->task->studentnote);
            $row->cells = array($cell1, $cell2);
            $t5->data[] = $row;


            //Evaluated by
            $row = new html_table_row();
            $cell1 = new html_table_cell('<strong>'.get_string('evaluatedby', 'mrproject').'</strong>');
            $cell2 = '';
            if ($ai->task->evaluatedby != 0) {
                $cell2 = new html_table_cell(fullname($ai->mrproject->get_userbyid($ai->task->evaluatedby)));
            }
            $row->cells = array($cell1, $cell2);
            $t5->data[] = $row;




        $o .= html_writer::table($t0);     //Submitted task (header)
        $o .= html_writer::table($t1);     //Submitted task

        $o .= html_writer::table($t2);     //Learning experience (header)
        $o .= html_writer::table($t3);     //Learning experience

        $o .= html_writer::table($t4);     //Evaluation (header)
        $o .= html_writer::table($t5);     //Evaluation

        $o .= $this->output->box_end();
        $o .= $this->output->container_end();
        return $o;
    }


/**************************************** Members: Student team ********************************************/
    /**
     * Render a scheduling list.
     *
     * @param mrproject_students_list $list
     * @return string
     */
    public function render_mrproject_students_list(mrproject_students_list $list) {

        $mtable = new html_table();

        $mtable->id = $list->id;
        $mtable->head[]  = '';
        $mtable->head[]  = get_string('fullname', 'mrproject');
        $mtable->head[]  = get_string('responsibilityfield', 'mrproject');
        $mtable->align = array ('center', 'left');
        foreach ($list->extraheaders as $field) {
            $mtable->head[] = $field;
            $mtable->align[] = 'left';
        }
        
        //$mtable->head[] = get_string('action', 'mrproject');
        $mtable->align[] = 'center';

        $mtable->data = array();
        foreach ($list->lines as $line) {
            $data = array($line->pix, $line->name, $line->responsibility);
            foreach ($line->extrafields as $field) {
                $data[] = $field;
            }
            /*$actions = '';
            if ($line->actions) {
                $menu = new action_menu($line->actions);
                $menu->actiontext = get_string('planmeeting', 'mrproject');
                $actions = $this->render($menu);
            }
            $data[] = $actions;*/
            $mtable->data[] = $data;
        }
        return html_writer::table($mtable);
    }





/************************************** Members: Supervisors *****************************************/
    /**
     * Render a scheduling list.
     *
     * @param mrproject_supervisors_list $list
     * @return string
     */
    public function render_mrproject_supervisors_list(mrproject_supervisors_list $list) {

        $mtable = new html_table();

        $mtable->id = $list->id;
        $mtable->head[]  = '';
        $mtable->head[]  = get_string('fullname', 'mrproject');
        $mtable->head[]  = get_string('rolefield', 'mrproject');
        $mtable->align = array ('center', 'left');
        foreach ($list->extraheaders as $field) {
            $mtable->head[] = $field;
            $mtable->align[] = 'left';
        }
        
        //$mtable->head[] = get_string('action', 'mrproject');
        $mtable->align[] = 'center';

        $mtable->data = array();
        foreach ($list->lines as $line) {
            $data = array($line->pix, $line->name, $line->role);
            foreach ($line->extrafields as $field) {
                $data[] = $field;
            }
            /*$actions = '';
            if ($line->actions) {
                $menu = new action_menu($line->actions);
                $menu->actiontext = get_string('planmeeting', 'mrproject');
                $actions = $this->render($menu);
            }
            $data[] = $actions;*/
            $mtable->data[] = $data;
        }
        return html_writer::table($mtable);
    }
    
    /*****************************************************************************************/


}
