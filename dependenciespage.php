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
 * Shows a sortable list of tasks
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

require_once($CFG->libdir.'/tablelib.php');

$PAGE->set_docs_path('mod/mrproject/dependenciespage');

/*$scope = optional_param('scope', 'activity', PARAM_TEXT);
if (!in_array($scope, array('activity', 'course', 'site'))) {
    $scope = 'activity';
}*/
$teacherid = optional_param('teacherid', 0, PARAM_INT);


$scopecontext = $context;


//if (!has_capability('mod/mrproject:seeoverviewoutsideactivity', $context)) {
    $scope = 'activity';
//}
//if (!has_capability('mod/mrproject:canseeotherteachersbooking', $scopecontext)) {
    //$teacherid = 0;
//}

$taburl = new moodle_url('/mod/mrproject/view.php',
                array('id' => $mrproject->cmid, 'what' => 'dependenciespage', 'scope' => $scope, 'teacherid' => $teacherid));
$returnurl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid));

$PAGE->set_url($taburl);

echo $output->header();


// Print top tabs.
echo $output->teacherview_tabs($mrproject, $permissions, $taburl, 'dependenciessubtab');


/********************************** Groups select *************************************/
$currentgroupid = 0;
//$groupmode = groups_get_activity_groupmode($mrproject->cm);
$groupmode = true;
$currentgroupid = groups_get_activity_group($mrproject->cm, true);
//echo html_writer::start_div('dropdownmenu');
//groups_print_activity_menu($mrproject->cm, $taburl);
//echo html_writer::end_div();


// Find groups which can book an task with the current teacher ($groupsthatcanseeme).
$groupsthatcanseeme = '';
if ($groupmode) {
    $groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);
}

/************************************************************************/

//Title
$groupname = '';
foreach ($groupsthatcanseeme as $id => $group) {
    if ($id ==  $currentgroupid) {
        $groupname = $group->name;
    }
}
//echo $output->heading(get_string('membersheader', 'mrproject'). $groupname);


//get a Group (object)
$selectedgroup = null;
foreach ($groupsthatcanseeme as $id => $group) {
    if ($id ==  $currentgroupid) {
        $selectedgroup = $group;
    }
}



//--------------------------- Heading --------------------------
echo $output->heading('<br/>'.get_string('alldependencies', 'mrproject'), 3);


/*************************print groups select + Team Logo *******************************/
$str1 = '';
$str1 .= '<table class="teamlogo">';
$str1 .= '<tr>';
//$str1 .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';


$str1 .= '</tr>';
$str1 .= '</table>';

echo $str1;


if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {    //Separate groups



/***************************************************************************************/


// Getting date list.
$params = array();

               
$limit = 50;

//if ($numrecords) {

    
    $dependencyname = get_string('dependency', 'mrproject');
    $link = get_string('link', 'mrproject');
    $recommendations_count = get_string('recommendations_count', 'mrproject');
    $recommend = get_string('recommend_action', 'mrproject');




    $tablecolumns = array('dependencyname', 'link',
                            'recommendations_count', 'recommend');

    $tableheaders = array($dependencyname, $link, 
                            $recommendations_count, $recommend);

    $table = new flexible_table('mod-mrproject-dependencies');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);

    $table->define_baseurl($taburl);

    
    $table->no_sorting('dependencyname'); 
    $table->no_sorting('link');
    $table->sortable(true, 'recommendations_count');   // Sorted by recommendations_count
    $table->no_sorting('recommend');

    $table->set_attribute('cellspacing', '3');


    $table->collapsible(true);      // Allow column hiding.
    $table->initialbars(true);


    

    $table->column_suppress('dependencyname');      // it is not repeated
    //$table->column_suppress('link');
    //$table->column_suppress('recommendations_count');

    $table->set_attribute('id', 'dates');
    $table->set_attribute('class', 'datelist');

    $table->column_class('course', 'datelist_course');
    $table->column_class('mrproject', 'datelist_mrproject');

    $table->setup();

    // Get extra query parameters from flexible_table behaviour.
    $where = $table->get_sql_where();
    $sort = $table->get_sql_sort();

    //$table->pagesize($limit, 10000);    //$numrecords = 10000  (size of the table)

    /*if (!empty($sort)) {
        $sql .= " ORDER BY $sort";
    }*/


    $sql = "SELECT d.id, d.dependency, d.link, COUNT(r.id) as recommendation_count
            FROM {mrproject_dependency} d 
            JOIN {mrproject_recommendation} r ON d.id = r.dependencyid     
            GROUP BY d.id 
            ORDER BY recommendation_count DESC";

    try {
        $results = $DB->get_records_sql($sql);
    } catch (dml_exception $e) {
        // Log error here.
        $results = [];
    }


    //$results = $DB->get_records('mrproject_recommendation', null, 'ORDER BY '); 


    $meetingfilter = null;
    foreach ($results as $result) {

        //Dependency name
        $dependencyname = $result->dependency;


        //Dependency link
        $dependencylink = html_writer::link(new moodle_url($result->link), $result->link, array('target' => '_blank', 'rel' => 'noopener'));


        //number of recommendations
        $numberofrecommendations = '<strong>'. $result->recommendation_count .'</strong>';
                      
                    
        //Action: Recommend           
        $recommendurl = new moodle_url('/mod/mrproject/view.php',
                                array('id' => $mrproject->cmid, 'what' => 'recommend', 'dependencyid' => $result->id, 'sesskey' => sesskey()));


        //get recommendations
        $recommended = $DB->get_field('mrproject_recommendation', 'id', array('dependencyid' => $result->id, 'recommendedby' => $USER->id));
        if (!$recommended) { 
            $recommend = html_writer::link($recommendurl, get_string('recommend', 'mrproject') . '<br/> &nbsp;&nbsp;&nbsp;' . $output->pix_icon('i/star-o', get_string('exportgrades', 'mrproject')) . $output->pix_icon('i/star-o', get_string('exportgrades', 'mrproject')) . $output->pix_icon('i/star-o', get_string('exportgrades', 'mrproject')) ,  ['class' => 'recommend2']);  

        } else {
            $recommend = html_writer::link($recommendurl, get_string('recommended', 'mrproject') . '<br/> &nbsp;&nbsp;&nbsp;' . $output->pix_icon('i/star-rating', get_string('exportgrades', 'mrproject')) . $output->pix_icon('i/star-rating', get_string('exportgrades', 'mrproject')) . $output->pix_icon('i/star-rating', get_string('exportgrades', 'mrproject')) ,  ['class' => 'recommended2']);  
        
        }



        $dataset = array(
                        $dependencyname,
                        $dependencylink,
                        $numberofrecommendations,
                        $recommend
                    );


        $table->add_data($dataset);


    }
    $table->print_html();

    
    
/*} else {
    notice(get_string('noresults', 'mrproject'), $returnurl);
}*/


}

echo $output->footer();
