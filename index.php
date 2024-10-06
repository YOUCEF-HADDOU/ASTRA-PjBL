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
 * Main file of the mrproject package.
 *
 * It lists all the instances of mrproject in a particular course.
 * URL of the index page:  http://localhost/moodle/mod/mrproject/index.php?id=7  
 * id=7 ---> id of the course
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');


$id = required_param('id', PARAM_INT);   // returns the Course id.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);  //Get the record from the table 'course' where id = $id. (Return the course)


//$PAGE is a global variable used to track the state of the page that is being returned
$PAGE->set_url('/mod/mrproject/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');


$coursecontext = context_course::instance($id);   //Returns context instance of the course
require_login($course->id);                       //It verifies that user is logged in before accessing the course



//Event: course_module_instance_list_viewed
$event = \mod_mrproject\event\course_module_instance_list_viewed::create(array(
    'context' => $coursecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();



// Get all required strings.
$strmrprojects = get_string('modulenameplural', 'mrproject');
$strmrproject  = get_string('modulename', 'mrproject');


// Print the header.
$title = $course->shortname . ': ' . $strmrprojects;
$PAGE->set_title($title);                           //set the title (Course1: mrprojects)
$PAGE->set_heading($course->fullname);              //displayed at the top of the page (Course 1) 
echo $OUTPUT->header($course);                      //output the header



// Get all the appropriate data. (get all active instances of the 'schedule' module in the course)
if (!$mrprojects = get_all_instances_in_course('mrproject', $course)) { 
    notice(get_string('nomrprojects', 'mrproject'), "../../course/view.php?id=$course->id");
    die;
}



// Print the list of instances.
$timenow = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic  = get_string('topic');

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('CENTER', 'LEFT');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);           //table with two columns (Topic, Name)
    $table->align = array ('CENTER', 'LEFT', 'LEFT', 'LEFT');
} else {
    $table->head  = array ($strname);
    $table->align = array ('LEFT', 'LEFT', 'LEFT');
}

foreach ($mrprojects as $mrproject) {
    $url = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->coursemodule));  //URL of each instance (id in the table course_modules)
    // Show dimmed if the mod is hidden.
    $attr = $mrproject->visible ? null : array('class' => 'dimmed');
    $link = html_writer::link($url, $mrproject->name, $attr);
    if ($mrproject->visible or has_capability('moodle/course:viewhiddenactivities', $coursecontext)) {
        if ($course->format == 'weeks' or $course->format == 'topics') {
            $table->data[] = array ($mrproject->section, $link);      //Array of row objects containing the data (num section, link of the instance)
        } else {
            $table->data[] = array ($link);
        }
    }
}

//Print the table that contains the list of mrproject instances in the course
echo html_writer::table($table);

// Finish the page. output the footer
echo $OUTPUT->footer($course);

