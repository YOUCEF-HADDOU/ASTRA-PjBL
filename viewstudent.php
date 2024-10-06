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
 * Prints the screen that displays a single student to a teacher.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/********************************* Force separate groups **********************************/
global $DB;

//course table
$course_record = new stdClass();
$course_record->id = $COURSE->id;         // course id
//$course_record->groupmode = 1;          // 1 = Separate groups  (for the course)     
$course_record->groupmodeforce = 0;       //disable Forcing group mode for all activities in this course
$DB->update_record('course', $course_record);    


//course_modules table
$cm_record = new stdClass();
$cm_record->id = $mrproject->cmid;      // course_modules id
$cm_record->groupmode = 1;              // 1 = Separate groups  (for the activity)
$DB->update_record('course_modules', $cm_record);  

//$DB->set_field('course_modules', 'groupmode', 1, array('id' => $mrproject->cmid));

/******************************************************************************************/

require_once($CFG->dirroot.'/mod/mrproject/locallib.php');
//require_once(dirname(__FILE__).'/exportform.php');

$taskid = required_param('taskid', PARAM_INT);
$student = required_param('studentid', PARAM_INT);    //student id

list($meeting, $task) = $mrproject->get_meeting_task($taskid);
//$studentid = $task->studentid;

$permissions->ensure($permissions->can_see_task($task));

$urlparas = array('what' => 'viewstudent',
    'id' => $mrproject->cmid,
    'taskid' => $taskid,
    'studentid' => $student,
    'course' => $mrproject->courseid,
    'sesskey' => sesskey()
    );

$taburl = new moodle_url('/mod/mrproject/view.php', $urlparas);
//$taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'individualdeliverablespage'));

$actionurl = new moodle_url($taburl, array('page' => 'thistask'));


$previousurl = new moodle_url('/mod/mrproject/view.php',
                array('id' => $mrproject->cmid, 'what' => 'individualdeliverablespage'));

$PAGE->set_url($taburl);

//$appts = $mrproject->get_tasks_for_student($studentid);


if ($action != 'thistask') {
    //require_once($CFG->dirroot.'/mod/mrproject/teacherview.controller.php');
}


//require_once($CFG->dirroot.'/mod/mrproject/studentview.controller.php');


$pages = array('thistask');

$pages[] = 'othertasks';

//$pages[] = 'studentlearningexperience';


if (!in_array($subpage, $pages) ) {
    $subpage = 'thistask';
}


// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($mrproject->cm);
$currentgroupid = groups_get_activity_group($mrproject->cm, true);

// Find groups which can book an task with the current teacher ($groupsthatcanseeme).
$groupsthatcanseeme = '';
$groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);

//group name
$groupname = '';
    foreach ($groupsthatcanseeme as $id => $group) {
        if ($id ==  $currentgroupid) {
            $groupname = $group->name;
        }
    }



//---------------------------------------------------------------------------------------    
    //Completion rate (progress bar)
    function emojiPercentBar($done, $total=100)
    {
        $green=html_entity_decode('&#x1F7E9;', 0, 'UTF-8');
        $white=html_entity_decode('&#x2B1C;', 0, 'UTF-8');

        $perc = round(($done * 100) / $total);
        $bar = round((10 * $perc) / 100);

        return sprintf("%s%s", str_repeat($green, $bar), str_repeat($white, 10-$bar));

    }

//--------------------------- 'thistask' tab: form --------------------------------------


if ($subpage == 'thistask') {
    require_once($CFG->dirroot.'/mod/mrproject/taskforms.php');

    $actionurl = new moodle_url($taburl, array('page' => 'thistask'));
    $returnurl = new moodle_url($taburl, array('page' => 'thistask'));

    $distribute = ($meeting->get_task_count() > 1);
    $gradeedit = $permissions->can_edit_grade($task);
    $mform = new mrproject_edittask_form($task, $actionurl, $permissions, $distribute);
    $mform->set_data($mform->prepare_task_data($task));

    if ($mform->is_cancelled()) {
        redirect($previousurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_task_data($formdata, $task);
        redirect($returnurl);
    }
}



echo $output->header();

//heading
echo $output->heading(get_string('evaluation', 'mrproject'), 3);



/************************************** Print user summary ***************************************/
mrproject_print_user($DB->get_record('user', array('id' => $student)), $course, $mrproject->cmid);



/********************************** Print tabs (menu) *******************************************/

$tabrows = array();
$row  = array();
if (count($pages) > 1) {
    foreach ($pages as $tabpage) {
        $tabname = get_string('tab-'.$tabpage, 'mrproject');
        $row[] = new tabobject($tabpage, new moodle_url($taburl, array('subpage' => $tabpage)), $tabname);
    }
    $tabrows[] = $row;
    print_tabs($tabrows, $subpage);
}




/****************************** Subpages: thistask, othertasks **********************************/

//get all tasks of this student
$appts = $mrproject->get_tasksheld_for_student($student);

if ($subpage == 'thistask') {

    //task + dependencies
    $ai = mrproject_submitted_task::make_for_teacher($meeting, $task, $actionurl, $student);
    echo $output->render($ai);

    //Event: task_reports_viewed
    //\mod_mrproject\event\task_reports_viewed::create_from_meeting($meeting)->trigger();


    //evaluation
    $mform->display();
    
    //Gradebook
    //echo $output->render($totalgradeinfo);

} else if ($subpage == 'othertasks') {

    // Print table of other tasks of the same student.
    $studenturl = new moodle_url($taburl, array('page' => 'thistask'));
    $table = new mrproject_task_table($mrproject, true, $studenturl);
    //$table->showattended = false;
    //$table->showteachernotes = true;
    $table->showeditlink = true;
    $table->showlocation = false;

    foreach ($appts as $appt) {
        $table->add_meeting($appt->get_meeting(), $appt, null, false);
    }
    echo $output->render($table);

    //Total grade (the mean grade) ----> All tasks
    $totalgradeinfo = new mrproject_totalgrade_info($mrproject, $mrproject->get_gradebook_info($student));
    if ($mrproject->uses_grades()) {
        $totalgradeinfo->showtotalgrade = true;
        $totalgradeinfo->totalgrade = $mrproject->get_user_grade($student);
        echo $output->render($totalgradeinfo);
    }

} 


echo ('<br/><br/>');
echo $output->continue_button($previousurl);

echo $output->footer($course);
exit;
