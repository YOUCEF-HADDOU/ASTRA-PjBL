<?PHP
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
 * Library (public API) of the mrproject module
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_mrproject\model\mrproject;


// Library of functions and constants for module mrproject.
require_once($CFG->dirroot.'/mod/mrproject/locallib.php');
require_once($CFG->dirroot.'/mod/mrproject/mailtemplatelib.php');
require_once($CFG->dirroot.'/mod/mrproject/renderer.php');
require_once($CFG->dirroot.'/mod/mrproject/renderable.php');


//Constants should always be in upper case, and always start with plugin name
define('MRPROJECT_TIMEUNKNOWN', 0);    // This is used for tasks for which no time is entered.
define('MRPROJECT_SELF', 0);           // Used for setting conflict search scope.
define('MRPROJECT_OTHERS', 1);         // Used for setting conflict search scope.
define('MRPROJECT_ALL', 2);            // Used for setting conflict search scope.

define ('MRPROJECT_MEAN_GRADE', 0);    // Used for grading strategy.
define ('MRPROJECT_MAX_GRADE', 1);     // Used for grading strategy.



/*************************************************************************************************************/

/*
 * Manage instance
 *
 */


/**
 * Create a new instance and return the id number of the new instance.
 * 
 *
 * @param stdClass $data the current instance
 * @param mod_mrproject_mod_form $mform the form that the user filled
 * @return int the new instance id
 * @uses $DB
 */
function mrproject_add_instance($data, $mform = null) {
    global $DB;

    $cmid = $data->coursemodule;

    $data->timemodified = time();
    //$data->scale = isset($data->grade) ? $data->grade : 0;

    //Insert a record into the 'mrproject' table
    $data->id = $DB->insert_record('mrproject', $data);

    //Set a field 'instance' in the 'course_modules' table
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    

    //Save editor files (function defined in mod_form.php)
    $context = context_module::instance($cmid);
    if ($mform) {
        $mform->save_mod_data($data, $context);   
    }

    //Create grade item for given mrproject (function defined in this file lib.php)
    //mrproject_grade_item_update($data);

    //Completion api
    if (class_exists('\core_completion\api')) {
        $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
        \core_completion\api::update_completion_date_event($data->coursemodule, 'mrproject', $data->id, $completiontimeexpected);
    }

    return $data->id;
}



/**
 * Update an existing instance with new data.
 *
 * @param stdClass $data
 * @param mod_mrproject_mod_form $mform the form that the user filled
 * @return bool the updated instance
 * @uses $DB
 */
function mrproject_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    //$data->scale = $data->grade;

    //Update a record in the 'mrproject' table
    $DB->update_record('mrproject', $data);

    //Save editor files (function defined in mod_form.php)
    $context = context_module::instance($data->coursemodule);
    $mform->save_mod_data($data, $context);

    // Update grade item and grades. (function defined in this file lib.php)
    //mrproject_update_grades($data);

    //Completion api
    if (class_exists('\core_completion\api')) {
        $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
        \core_completion\api::update_completion_date_event($data->coursemodule, 'mrproject', $data->id, $completiontimeexpected);
    }

    return true;
}




/**
 * Delete the instance and any data that depends on it.
 * 
 *
 * @param int $id the instance to be deleted
 * @return bool true if success, false otherwise
 * @uses $DB
 */
function mrproject_delete_instance($id) {
    global $DB;

    if (! $DB->record_exists('mrproject', array('id' => $id))) {
        return false;
    }

    $mrproject = mrproject::load_by_id($id);
    $mrproject->delete();   //defined in /model/mrproject.php

    // Clean up any possibly remaining event records.
    $params = array('modulename' => 'mrproject', 'instance' => $id);
    $DB->delete_records('event', $params);

    return true;
}





/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param object $course the course instance
 * @param object $user the concerned user instance
 * @param object $mod the current course module instance
 * @param object $mrproject the activity module behind the course module instance
 * @return object an information object as defined above
 */
function mrproject_user_outline($course, $user, $mod, $mrproject) {

    $mrproject = mrproject::load_by_coursemodule_id($mod->id);
    $upcoming = count($mrproject->get_upcoming_meetings_for_student($user->id));
    $attended = count($mrproject->get_attended_meetings_for_student($user->id));

    $text = '';

    if ($attended + $upcoming > 0) {
        $a = array('attended' => $attended, 'upcoming' => $upcoming);
        $text .= get_string('outlinetasks', 'mrproject', $a);
    }

    if ($mrproject->uses_grades()) {
        $grade = $mrproject->get_gradebook_info($user->id);
        if ($grade) {
            $text .= get_string('outlinegrade', 'mrproject', $grade->str_long_grade);
        }
    }

    $return = new stdClass();
    $return->info = $text;
    return $return;
}



/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course the course instance
 * @param object $user the concerned user instance
 * @param object $mod the current course module instance
 * @param object $mrproject the activity module behind the course module instance
 */
function mrproject_user_complete($course, $user, $mod, $mrproject) {

    global $PAGE;

    $mrproject = mrproject::load_by_coursemodule_id($mod->id);
    $output = $PAGE->get_renderer('mod_mrproject', null, RENDERER_TARGET_GENERAL);

    $tasks = $mrproject->get_tasks_for_student($user->id);

    if (count($tasks) > 0) {
        $table = new mrproject_meeting_table($mrproject);
        $table->showattended = true;
        foreach ($tasks as $app) {
            $table->add_meeting($app->get_meeting(), $app, null, false);
        }

        echo $output->render($table);
    } else {
        echo get_string('notasks', 'mrproject');
    }

    if ($mrproject->uses_grades()) {
        $grade = $mrproject->get_gradebook_info($user->id);
        if ($grade) {
            $info = new mrproject_totalgrade_info($mrproject, $grade);
            echo $output->render($info);
        }
    }

}




/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in mrproject activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course the course instance
 * @param bool $isteacher true tells a teacher uses the function
 * @param int $timestart a time start timestamp
 * @return bool true if anything was printed, otherwise false
 */
function mrproject_print_recent_activity($course, $isteacher, $timestart) {

    return false;
}




/**
 * This function returns whether a scale is being used by a mrproject.
 *
 * @param int $cmid ID of an instance of this module
 * @param int $scaleid the id of the scale in question
 * @return mixed
 * @uses $DB
 **/
function mrproject_scale_used($cmid, $scaleid) {
    global $DB;

    $return = false;

    // Note: scales are assigned using negative index in the grade field of the task (see mod/assignement/lib.php).
    //$rec = $DB->get_record('mrproject', array('id' => $cmid, 'scale' => -$scaleid));

    //if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    //}

    return $return;
}




/**
 * Checks if scale is being used by any instance of mrproject
 *
 * @param int $scaleid the id of the scale in question
 * @return bool True if the scale is used by any mrproject
 * @uses $DB
 */
function mrproject_scale_used_anywhere($scaleid) {
    global $DB;

    //if ($scaleid and $DB->record_exists('mrproject', array('scale' => -$scaleid))) {
    //if ($scaleid) {
        return true;
    /*} else {
        return false;
    }*/
}




/*************************************************************************************************************/

/*
 * Course resetting API
 *
 */




/**
 * Called by course/reset.php
 *
 * @param MoodleQuickForm $mform form passed by reference
 * @uses $COURSE
 * @uses $DB
 */
function mrproject_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'mrprojectheader', get_string('modulenameplural', 'mrproject'));

    if ($DB->record_exists('mrproject', array('course' => $COURSE->id))) {

        $mform->addElement('checkbox', 'reset_mrproject_meetings', get_string('resetmeetings', 'mrproject'));
        $mform->addElement('checkbox', 'reset_mrproject_tasks', get_string('resettasks', 'mrproject'));
        $mform->disabledIf('reset_mrproject_tasks', 'reset_mrproject_meetings', 'checked');
    }
}




/**
 * Default values for the reset form
 *
 * @param stdClass $course the course in which the reset takes place
 */
function mrproject_reset_course_form_defaults($course) {
    return array('reset_mrproject_meetings' => 1, 'reset_mrproject_tasks' => 1);
}




/**
 * This function is used by the remove_course_userdata function in moodlelib.
 * If this function exists, remove_course_userdata will execute it.
 * This function will remove all meetings and tasks from the specified mrproject.
 *
 * @param object $data the reset options
 * @return void
 */
function mrproject_reset_userdata($data) {
    global $CFG, $DB;

    $status = array();
    $componentstr = get_string('modulenameplural', 'mrproject');

    $success = true;

    if (!empty($data->reset_mrproject_tasks) || !empty($data->reset_mrproject_meetings)) {

        $mrprojects = $DB->get_records('mrproject', ['course' => $data->courseid]);

        foreach ($mrprojects as $srec) {
            $mrproject = mrproject::load_by_id($srec->id);

            if (!empty($data->reset_mrproject_meetings) ) {
                $mrproject->delete_all_meetings();
                $status[] = array('component' => $componentstr, 'item' => get_string('resetmeetings', 'mrproject'), 'error' => false);
            } else if (!empty($data->reset_mrproject_tasks) ) {
                foreach ($mrproject->get_all_meetings() as $meeting) {
                    $meeting->delete_all_tasks();
                }
                $status[] = array(
                    'component' => $componentstr,
                    'item' => get_string('resettasks', 'mrproject'),
                    'error' => !$success
                );
            }
        }
    }
    return $status;
}




/**
 * Determine whether a certain feature is supported by mrproject.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function mrproject_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}



/*************************************************************************************************************/

/* Gradebook API */
/*
 * add xxx_update_grades() function into mod/xxx/lib.php
 * add xxx_grade_item_update() function into mod/xxx/lib.php
 * patch xxx_update_instance(), xxx_add_instance() and xxx_delete_instance() to call xxx_grade_item_update()
 * patch all places of code that change grade values to call xxx_update_grades()
 * patch code that displays grades to students to use final grades from the gradebook
 */




/**
 * Create grade item for given mrproject
 *
 * @param object $mrproject object
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function mrproject_grade_item_update($mrproject, $grades=null) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($mrproject->courseid)) {
        $mrproject->courseid = $mrproject->course;
    }
    $moduleid = $DB->get_field('modules', 'id', array('name' => 'mrproject'));
    $cmid = $DB->get_field('course_modules', 'id', array('module' => $moduleid, 'instance' => $mrproject->id));

    
        $params = array('itemname' => $mrproject->name, 'idnumber' => $cmid);
        
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = 20;   //grading
        $params['grademin']  = 0;

        if ($grades === 'reset') {
            $params['reset'] = true;
            $grades = null;
        }

        return grade_update('mod/mrproject', $mrproject->courseid, 'mod', 'mrproject', $mrproject->id, 0, $grades, $params);
    
}




/**
 * Update activity grades
 *
 * @param object $mrprojectrecord
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone not used
 * @uses $CFG
 * @uses $DB
 */
function mrproject_update_grades($mrprojectrecord, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $mrproject = mrproject::load_by_id($mrprojectrecord->id);

    if ($grades = $mrproject->get_user_grades($userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        mrproject_grade_item_update($mrprojectrecord, $grades);

    } else {
        mrproject_grade_item_update($mrprojectrecord);
    }
}




/**
 * Update all grades in gradebook.
 */
function mrproject_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
        FROM {mrproject} s, {course_modules} cm, {modules} m
        WHERE m.name='mrproject' AND m.id=cm.module AND cm.instance=s.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT s.*, cm.idnumber AS cmidnumber, s.course AS courseid
        FROM {mrproject} s, {course_modules} cm, {modules} m
        WHERE m.name='mrproject' AND m.id=cm.module AND cm.instance=s.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        $pbar = new progress_bar('mrprojectupgradegrades', 500, true);
        $i = 0;
        foreach ($rs as $mrproject) {
            $i++;
            upgrade_set_timeout(60 * 5); // Set up timeout, may also abort execution.
            mrproject_update_grades($mrproject);
            $pbar->update($i, $count, "Updating mrproject grades ($i/$count).");
        }
        upgrade_set_timeout(); // Reset to default timeout.
    }
    $rs->close();
}




/**
 * Delete grade item for given mrproject
 *
 * @param object $mrproject object
 * @return object mrproject
 */
function mrproject_grade_item_delete($mrproject) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($mrproject->courseid)) {
        $mrproject->courseid = $mrproject->course;
    }

    return grade_update('mod/mrproject', $mrproject->courseid, 'mod', 'mrproject', $mrproject->id, 0, null, array('deleted' => 1));
}



/*************************************************************************************************************/

/*
 * File API
 */



/**
 * Lists all browsable file areas
 *
 * @package  mod_mrproject
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function mrproject_get_file_areas($course, $cm, $context) {
    return array(
            'bookinginstructions' => get_string('bookinginstructions', 'mrproject'),
            'meetingnote' => get_string('areameetingnote', 'mrproject'),
            'tasknote' => get_string('areatasknote', 'mrproject'),
            'teachernote' => get_string('areateachernote', 'mrproject')
    );
}




/**
 * File browsing support for mrproject module.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info_stored file_info_stored instance or null if not found
 */
function mrproject_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    // Note: 'intro' area is handled in file_browser automatically.

    if (!has_any_capability(array('mod/mrproject:appoint', 'mod/mrproject:attend',
                                  'mod/mrproject:viewotherteachersbooking', 'mod/mrproject:managealltasks'), $context)) {
        return null;
    }

    require_once(dirname(__FILE__).'/locallib.php');

    $validareas = array_keys(mrproject_get_file_areas($course, $cm, $context));
    if (!in_array($filearea, $validareas)) {
        return null;
    }

    if (is_null($itemid)) {
        return new mrproject_file_info($browser, $course, $cm, $context, $areas, $filearea);
    }

    try {
        $mrproject = mrproject::load_by_coursemodule_id($cm->id);
        $permissions = new \mod_mrproject\permission\mrproject_permissions($context, $USER->id);

        if ($filearea === 'bookinginstructions') {
            $cansee = true;
            $canwrite = has_capability('moodle/course:manageactivities', $context);
            $name = get_string('bookinginstructions', 'mrproject');

        } else if ($filearea === 'meetingnote') {
            $meeting = $mrproject->get_meeting($itemid);
            $cansee = true;
            $canwrite = $permissions->can_edit_meeting($meeting);
            $name = get_string('meeting', 'mrproject'). ' '.$itemid;

        } else if ($filearea === 'tasknote') {
            if (!$mrproject->uses_tasknotes()) {
                return null;
            }
            list($meeting, $app) = $mrproject->get_meeting_task($itemid);
            $cansee = $permissions->can_see_task($app);
            $canwrite = $permissions->can_edit_notes($app);
            $name = get_string('task', 'mrproject'). ' '.$itemid;

        } else if ($filearea === 'teachernote') {
            if (!$mrproject->uses_teachernotes()) {
                return null;
            }

            list($meeting, $app) = $mrproject->get_meeting_task($itemid);
            $cansee = $permissions->teacher_can_see_meeting($meeting);
            $canwrite = $permissions->can_edit_notes($app);
            $name = get_string('task', 'mrproject'). ' '.$itemid;
        }

        $fs = get_file_storage();
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        if (!$storedfile = $fs->get_file($context->id, 'mod_mrproject', $filearea, $itemid, $filepath, $filename)) {
            return null;
        }

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $name, true, true, $canwrite, false);
    } catch (Exception $e) {
        return null;
    }
}




/**
 * Serves the files embedded in various rich text fields, or uploaded by students
 *
 * @package  mod_mrproject
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClsss $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function mrproject_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_any_capability(array('mod/mrproject:appoint', 'mod/mrproject:attend'), $context)) {
        return false;
    }

    try {
        $mrproject = mrproject::load_by_coursemodule_id($cm->id);
        $permissions = new \mod_mrproject\permission\mrproject_permissions($context, $USER->id);

        $entryid = (int)array_shift($args);
        $relativepath = implode('/', $args);

        if ($filearea === 'meetingnote') {
            if (!$mrproject->get_meeting($entryid)) {
                return false;
            }
            // No further access control required - everyone can see meetings notes.

        } else if ($filearea === 'tasknote') {
            if (!$mrproject->uses_tasknotes()) {
                return false;
            }

            list($meeting, $app) = $mrproject->get_meeting_task($entryid);
            if (!$app) {
                return false;
            }

            $permissions->ensure($permissions->can_see_task($app));

        } else if ($filearea === 'teachernote') {
            if (!$mrproject->uses_teachernotes()) {
                return false;
            }

            list($meeting, $app) = $mrproject->get_meeting_task($entryid);
            if (!$app) {
                return false;
            }

            $permissions->ensure($permissions->teacher_can_see_meeting($meeting));

        } else if ($filearea === 'bookinginstructions') {
            $caps = array('moodle/course:manageactivities', 'mod/mrproject:appoint');
            if (!has_any_capability($caps, $context)) {
                return false;
            }

        } else if ($filearea === 'studentfiles') {
            if (!$mrproject->uses_studentfiles()) {
                return false;
            }

            list($meeting, $app) = $mrproject->get_meeting_task($entryid);
            if (!$app) {
                return false;
            }

            $permissions->ensure($permissions->can_see_task($app));

        } else {
            // Unknown file area.
            return false;
        }
    } catch (Exception $e) {
        // Typically, records that are not found in the database.
        return false;
    }

    $fullpath = "/$context->id/mod_mrproject/$filearea/$entryid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}




/*************************************************************************************************************/



/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_mrproject_core_calendar_provide_event_action(calendar_event $event,
                                                            \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['mrproject'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
            get_string('view'),
            new \moodle_url('/mod/mrproject/view.php', ['id' => $cm->id]),
            1,
            true
    );
}

