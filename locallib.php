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
 * General library for the mrproject module.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once(dirname(__FILE__).'/customlib.php');


/* Events related functions */

/**
 * Will delete calendar events for a given mrproject meeting, and not complain if the record does not exist.
 * The only argument this function requires is the complete database record of a mrproject meeting.
 * @param object $meeting the meeting instance
 * @uses $DB
 * @return bool true if success, false otherwise
 */
function mrproject_delete_calendar_events($meeting) {
    global $DB;

    $mrproject = $DB->get_record('mrproject', array('id' => $meeting->mrprojectid));

    if (!$mrproject) {
        return false;
    }

    $teachereventtype = "SSsup:{$meeting->id}:{$mrproject->course}";
    $studenteventtype = "SSstu:{$meeting->id}:{$mrproject->course}";

    $teacherdeletionsuccess = $DB->delete_records('event', array('eventtype' => $teachereventtype));
    $studentdeletionsuccess = $DB->delete_records('event', array('eventtype' => $studenteventtype));

    return ($teacherdeletionsuccess && $studentdeletionsuccess);
    // This return may not be meaningful if the delete records functions do not return anything meaningful.
}



/**************************** Summary of a student during grading *********************************/

/**
 * Prints a summary of a user in a nice little box.
 *
 * @uses $CFG
 * @uses $USER
 * @param user $user A {@link $USER} object representing a user
 * @param course $course A {@link $COURSE} object representing a course
 * @param bool $messageselect whether to include a checkbox to select the user
 * @param bool $return whether the HTML fragment is to be returned as a string (otherwise printed)
 * @return string HTML fragment, if so selected
 */
function mrproject_print_user($user, $course, $cmid, $messageselect=false, $return=false) {

    global $CFG, $USER, $OUTPUT;

    $output = '';

    static $string;
    static $datestring;
    static $countries;

    $context = context_course::instance($course->id);
    if (isset($user->context->id)) {
        $usercontext = $user->context;
    } else {
        $usercontext = context_user::instance($user->id);
    }

    if (empty($string)) {     // Cache all the strings for the rest of the page.

        $string = new stdClass();
        $string->email       = get_string('email');
        $string->lastaccess  = get_string('lastaccess');
        $string->activity    = get_string('activity');
        $string->loginas     = get_string('loginas');
        $string->fullprofile = get_string('fullprofile');
        $string->role        = get_string('role');
        $string->name        = get_string('name');
        $string->never       = get_string('never');

        $datestring = new stdClass();
        $datestring->day     = get_string('day');
        $datestring->days    = get_string('days');
        $datestring->hour    = get_string('hour');
        $datestring->hours   = get_string('hours');
        $datestring->min     = get_string('min');
        $datestring->mins    = get_string('mins');
        $datestring->sec     = get_string('sec');
        $datestring->secs    = get_string('secs');
        $datestring->year    = get_string('year');
        $datestring->years   = get_string('years');

    }

    // Get the hidden field list.
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    $output .= '<table class="userinfobox">';
    $output .= '<tr>';
    $output .= '<td class="left side">';
    $output .= $OUTPUT->user_picture($user, array('size' => 100));
    $output .= '</td>';
    $output .= '<td class="content">';


    $output .= '<h4 style="font-weight: bold;">'.fullname($user, has_capability('moodle/site:viewfullnames', $context)).'</h4>';
    $output .= '<div class="info">';
    if (!empty($user->role) and ($user->role <> $course->teacher)) {
        $output .= '<u>'.$string->role .':</u>  '. $user->role .'<br />';
    }

    $extrafields = mrproject_get_user_fields($user, $context);
    foreach ($extrafields as $field) {
        $output .= '<u>'.$field->title . ':</u>  ' . $field->value . '<br />';
    }

    if (!isset($hiddenfields['lastaccess'])) {
        if ($user->lastaccess) {
            $output .= '<u>'.$string->lastaccess .':</u>  '. userdate($user->lastaccess);
            $output .= '&nbsp; ('. format_time(time() - $user->lastaccess, $datestring) .')';
        } else {
            $output .= $string->lastaccess .': '. $string->never;
        }
    }


    //Activity
    if (has_capability('moodle/site:viewreports', $context) or has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
        
        //Today's traces 
        $output .= '<br/><br/> <strong>' .get_string('lmslearningexperience', 'mrproject'). ':</strong>  &nbsp;&nbsp; <div class="traces">'.'<a href="'. $CFG->wwwroot .'/course/user.php?id='. $course->id .'&amp;user='. $user->id . '&amp;modid='. $cmid . '", target= "_blank", rel= "noopener">'.          //target= '_blank' ---> Open the link in a new tab
                    get_string('todaytraces', 'mrproject') .'</a>'.'</div>';
    
        //All traces
        $output .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <div class="traces">'.'<a href="'. $CFG->wwwroot .'/report/log/index.php?id='. $course->id .'&amp;user='. $user->id .'&amp;logreader='. 'logstore_standard' . '&amp;chooselog='. 1 . '&amp;modid='. $cmid . '", target= "_blank", rel= "noopener">'.         //target= '_blank' ---> Open the link in a new tab
                    get_string('alltraces', 'mrproject') .'</a>'.'</div>';
        
    }


    $output .= '</div></td><td class="links">';
    


    //Full profil
    //$output .= '<a href="'. $CFG->wwwroot .'/user/profile.php?id='. $user->id .'">'. $string->fullprofile .'</a><br />';

    

    //Activity
    /*if (has_capability('moodle/site:viewreports', $context) or
            has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
        $output .= '<a href="'. $CFG->wwwroot .'/course/user.php?id='. $course->id .'&amp;user='. $user->id .'">'.
                    get_string('studentlearningexperience', 'mrproject') .'</a><br />';
    }*/
    


    //Notes.
    /*if (!empty($CFG->enablenotes) and (has_capability('moodle/notes:manage', $context)
            || has_capability('moodle/notes:view', $context))) {
        $output .= '<a href="'.$CFG->wwwroot.'/notes/index.php?course=' . $course->id. '&amp;user='.$user->id.'">'.
                    get_string('notes', 'notes').'</a><br />';
    }*/


    
    // Link to blogs.
   /*if ($CFG->bloglevel > 0) {
        $output .= '<a href="'.$CFG->wwwroot.'/blog/index.php?userid='.$user->id.'">'.get_string('blogs', 'blog').'</a>';
    }*/


    if (!empty($messageselect)) {
        $output .= '<br /><input type="checkbox" name="user'.$user->id.'" /> ';
    }
    $output .= '</td></tr></table>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }

    

    
    

    
}




/**
 * File browsing support class
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_file_info extends file_info {
    /** @var stdClass Course object */
    protected $course;
    /** @var stdClass Course module object */
    protected $cm;
    /** @var array Available file areas */
    protected $areas;
    /** @var string File area to browse */
    protected $filearea;
    /** @var mrproject The mrproject that this file area refers to */
    protected $mrproject;

    /**
     * Constructor
     *
     * @param file_browser $browser file_browser instance
     * @param stdClass $course course object
     * @param stdClass $cm course module object
     * @param stdClass $context module context
     * @param array $areas available file areas
     * @param string $filearea file area to browse
     */
    public function __construct($browser, $course, $cm, $context, $areas, $filearea) {
        parent::__construct($browser, $context);
        $this->course   = $course;
        $this->cm       = $cm;
        $this->areas    = $areas;
        $this->filearea = $filearea;
        $this->mrproject = mrproject::load_by_coursemodule_id($cm->id);
    }

    /**
     * Returns list of standard virtual file/directory identification.
     * The difference from stored_file parameters is that null values
     * are allowed in all fields
     * @return array with keys contextid, filearea, itemid, filepath and filename
     */
    public function get_params() {
        return array('contextid' => $this->context->id,
                     'component' => 'mod_mrproject',
                     'filearea'  => $this->filearea,
                     'itemid'    => null,
                     'filepath'  => null,
                     'filename'  => null);
    }

    /**
     * Returns localised visible name.
     * @return string
     */
    public function get_visible_name() {
        return $this->areas[$this->filearea];
    }

    /**
     * Can I add new files or directories?
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Is directory?
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Returns list of children.
     * @return array of file_info instances
     */
    public function get_children() {
        return $this->get_filtered_children('*', false, true);
    }

    /**
     * Helper function to return files matching extensions or their count
     *
     * @param string|array $extensions either '*' or array of lowercase extensions, i.e. array('.gif','.jpg')
     * @param bool|int $countonly if false returns the children, if an int returns just the
     *    count of children but stops counting when $countonly number of children is reached
     * @param bool $returnemptyfolders if true returns items that don't have matching files inside
     * @return array|int array of file_info instances or the count
     * @uses $DB
     */
    private function get_filtered_children($extensions = '*', $countonly = false, $returnemptyfolders = false) {
        global $DB;

        $params = array('contextid' => $this->context->id,
                        'component' => 'mod_mrproject',
                        'filearea' => $this->filearea);
        $sql = "SELECT DISTINCT f.itemid AS id
                           FROM {files} f
                          WHERE f.contextid = :contextid
                                AND f.component = :component
                                AND f.filearea = :filearea";
        if (!$returnemptyfolders) {
            $sql .= ' AND filename <> :emptyfilename';
            $params['emptyfilename'] = '.';
        }
        list($sql2, $params2) = $this->build_search_files_sql($extensions, 'f');
        $sql .= ' '.$sql2;
        $params = array_merge($params, $params2);

        $rs = $DB->get_recordset_sql($sql, $params);
        $children = array();
        foreach ($rs as $record) {
            if ($child = $this->browser->get_file_info($this->context, 'mod_mrproject', $this->filearea, $record->id)) {
                if ($returnemptyfolders || $child->count_non_empty_children($extensions)) {
                    $children[] = $child;
                }
            }
            if ($countonly !== false && count($children) >= $countonly) {
                break;
            }
        }
        $rs->close();
        if ($countonly !== false) {
            return count($children);
        }
        return $children;
    }

    /**
     * Returns list of children which are either files matching the specified extensions
     * or folders that contain at least one such file.
     *
     * @param string|array $extensions either '*' or array of lowercase extensions, i.e. array('.gif','.jpg')
     * @return array of file_info instances
     */
    public function get_non_empty_children($extensions = '*') {
        return $this->get_filtered_children($extensions, false);
    }

    /**
     * Returns the number of children which are either files matching the specified extensions
     * or folders containing at least one such file.
     *
     * @param string|array $extensions for example '*' or array('.gif','.jpg')
     * @param int $limit stop counting after at least $limit non-empty children are found
     * @return int
     */
    public function count_non_empty_children($extensions = '*', $limit = 1) {
        return $this->get_filtered_children($extensions, $limit);
    }

    /**
     * Returns parent file_info instance
     *
     * @return file_info or null for root
     */
    public function get_parent() {
        return $this->browser->get_file_info($this->context);
    }
}
