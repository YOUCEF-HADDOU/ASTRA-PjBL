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
 * Export mrproject data to a file.
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
$course_record->lang = 'en';              //force langage in this course to 'english'
$DB->update_record('course', $course_record);    


//course_modules table
$cm_record = new stdClass();
$cm_record->id = $mrproject->cmid;      // course_modules id
$cm_record->groupmode = 1;              // 1 = Separate groups  (for the activity)
$DB->update_record('course_modules', $cm_record);  

//$DB->set_field('course_modules', 'groupmode', 1, array('id' => $mrproject->cmid));

/******************************************************************************************/

require_once(dirname(__FILE__).'/exportform.php');

$PAGE->set_docs_path('mod/mrproject/export');

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($mrproject->cm);
$currentgroupid = groups_get_activity_group($mrproject->cm, true);


$actionurl = new moodle_url('/mod/mrproject/view.php', array('what' => 'export', 'id' => $mrproject->cmid));
//$returnurl = new moodle_url('/mod/mrproject/view.php', array('what' => 'view', 'id' => $mrproject->cmid));
$returnurl = new moodle_url('/mod/mrproject/view.php', array('what' => 'deliverablespage', 'subpage' =>'individualdeliverablessubtab', 'id' => $mrproject->cmid));
        
$PAGE->set_url($actionurl);

$mform = new mrproject_export_form($actionurl, $mrproject);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

$data = $mform->get_data();
if ($data) {
    $availablefields = mrproject_get_export_fields($mrproject);
    $selectedfields = array();
    foreach ($availablefields as $field) {
        $inputid = 'field-'.$field->get_id();
        if (isset($data->{$inputid}) && $data->{$inputid} == 1) {
            $selectedfields[] = $field;
            $field->set_renderer($output);
        }
    }
    $userid = $USER->id;
    if (isset($data->includewhom) && $data->includewhom == 'all') {
        $permissions->ensure($permissions->can_see_all_meetings());
        $userid = 0;
    }
    $pageperteacher = isset($data->paging) && $data->paging == 'perteacher';
    $preview = isset($data->preview);
} else {
    $preview = false;
}

if (!$data || $preview) {
    echo $OUTPUT->header();

    // Print top tabs.
    $taburl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid, 'what' => 'export'));
    echo $output->teacherview_tabs($mrproject, $permissions, $taburl, 'exporttab');

    //print group select
    groups_print_activity_menu($mrproject->cm, $taburl);
    

    //Heading
    echo $output->heading(get_string('exporthdr', 'mrproject'), 2);
    echo html_writer::div(get_string('exportmessage', 'mrproject'));

    $mform->display();

    if ($preview) {
        $canvas = new mrproject_html_canvas();
        $export = new mrproject_export($canvas);

        $export->build($mrproject,
                        $selectedfields,
                        $data->content,
                        $userid,
                        $currentgroupid,
                        $data->timerange,
                        $data->includeemptymeetings,
                        $pageperteacher);

        $limit = 20;
        echo $canvas->as_html($limit, false);

        echo html_writer::div(get_string('previewlimited', 'mrproject', $limit), 'previewlimited');
    }

    echo $output->footer();
    exit();
}

switch ($data->outputformat) {
    case 'csv':
        $canvas = new mrproject_csv_canvas('comma');
        break;
    case 'xls':
        $canvas = new mrproject_excel_canvas($mrproject->cmid);
        break;
    case 'pdf':
        $canvas = new mrproject_pdf_canvas('landscape'); //portrait or landscape
        break;
    /*case 'ods':
        $canvas = new mrproject_ods_canvas();
        break;
    case 'html':
        $canvas = new mrproject_html_canvas($returnurl);
        break;*/
    
}

$export = new mrproject_export($canvas);

$export->build($mrproject,
               $selectedfields,
               $data->content,
               $userid,
               $currentgroupid,
               $data->timerange,
               $data->includeemptymeetings,
               $pageperteacher);

//$filename = clean_filename(format_string($course->shortname).'_'.format_string($mrproject->name));
$filename = clean_filename(format_string($mrproject->name).'_exportedOn_'. $mrproject->usertime( time() ));
$canvas->send($filename);

