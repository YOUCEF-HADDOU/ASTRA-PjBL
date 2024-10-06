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

$PAGE->set_docs_path('mod/mrproject/individualdeliverablespage');

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
                array('id' => $mrproject->cmid, 'what' => 'individualdeliverablespage', 'scope' => $scope, 'teacherid' => $teacherid));
$returnurl = new moodle_url('/mod/mrproject/view.php', array('id' => $mrproject->cmid));

$PAGE->set_url($taburl);

echo $output->header();


// Print top tabs.
echo $output->teacherview_tabs($mrproject, $permissions, $taburl, 'individualdeliverablessubtab');


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

/*************************print groups select + Team Logo *******************************/
$str1 = '';
$str1 .= '<table class="teamlogo">';
$str1 .= '<tr>';
$str1 .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';


//select groups
$str1 .= groups_print_activity_menu($mrproject->cm, $taburl);

if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {    //Separate groups

    //group logo
    if ($currentgroupid != 0) {
        if ($pictureurl = get_group_picture_url($selectedgroup, $COURSE->id, true, false)) {
            $str1 .= html_writer::img($pictureurl, '', ['title' => $groupname, 'class' => 'imglogo']);
        } else {
            $str1 .= html_writer::div(get_string('grouplogo', 'mrproject'), 'grouplogo');
        }
    }

} else {
    echo $output->notification(get_string('separategroups', 'mrproject'), 'notifyproblem');
}

//group name
//$str1 .= '<span class="team"><strong>'. $groupname .'</strong></span>';


$str1 .= '</tr>';
$str1 .= '</table>';

echo $str1;


if (groups_is_member($currentgroupid, $USER->id) || $currentgroupid == 0) {    //Separate groups

//--------------------------- Heading --------------------------
echo $output->heading('<br/>'.get_string('tasksdefined', 'mrproject'), 3);

$teachercaps = ['mod/mrproject:managealltasks']; 
$isteacher = has_any_capability($teachercaps, $context);
if ($isteacher) {
    echo html_writer::div(get_string('evaluationnote', 'mrproject'));
} else {
    echo html_writer::div(get_string('submityourtask', 'mrproject'));
}

/***************************************************************************************/


// Getting date list.
$params = array();
//$params['teacherid']   = $teacherid == 0 ? $USER->id : $teacherid;
$params['teacherid']   = $USER->id;
$params['studentid']   = $USER->id;
$params['courseid']    = $mrproject->courseid;
$params['mrprojectid'] = $mrproject->id;

$scopecond = '';
if ($scope == 'activity') {
    $scopecond = ' AND sc.id = :mrprojectid';
} else if ($scope == 'course') {
    $scopecond = ' AND c.id = :courseid';
}

//u1: student,   u2: teacher
$sql = "SELECT a.id AS id, ".
               user_picture::fields('u1', array('email', 'department'), 'studentid', 'student').", ".
               $DB->sql_fullname('u1.firstname', 'u1.lastname')." AS studentfullname,
               a.tasknote,
               a.tasknoteformat,
               a.collectivetask,
               a.studentid,
               a.grade,
               a.duedate,
               a.submissiondate,
               sc.name,
               sc.id AS mrprojectid,
               c.shortname AS courseshort,
               c.id AS courseid, ".
               user_picture::fields('u2', null, 'teacherid').",
               s.id AS sid,
               s.starttime,
               s.duration,
               s.tasklocation,
               s.meetingpurpose,
               s.meetingpurposeformat,
               s.feedbackbyteacher,
               s.feedbackbyteacherformat
          FROM {course} c,
               {mrproject} sc,
               {mrproject_task} a,
               {mrproject_meeting} s,
               {user} u1,
               {user} u2
         WHERE c.id = sc.course AND
               sc.id = s.mrprojectid AND
               a.meetingid = s.id AND
               u1.id = a.studentid AND
               u2.id = s.teacherid AND
               s.meetingheld = 1 ".
               $scopecond;

               //s.teacherid = :teacherid

$sqlcount =
       "SELECT COUNT(*)
          FROM {course} c,
               {mrproject} sc,
               {mrproject_task} a,
               {mrproject_meeting} s
         WHERE c.id = sc.course AND
               sc.id = s.mrprojectid AND
               a.meetingid = s.id AND
               s.meetingheld = 1 ".
               $scopecond;

               //s.teacherid = :teacherid
               
$numrecords = $DB->count_records_sql($sqlcount, $params);


$limit = 50;

if ($numrecords) {

    // Make the table of results.
    //$coursestr = get_string('course', 'mrproject');
    $meetingtstr = get_string('meeting', 'mrproject');
    //$whenstr = get_string('when', 'mrproject');
    //$wherestr = get_string('where', 'mrproject');
    $whatstr = get_string('what', 'mrproject');
    $whostr = get_string('who', 'mrproject');
    //$wherefromstr = get_string('department', 'mrproject');
    $whatresultedstr = get_string('whatresulted', 'mrproject');
    //$whathappenedstr = get_string('whathappened', 'mrproject');

    $tablecolumns = array('mrprojectid', 'meetingpurpose',
                            'studentfullname', 'grade');
    $tableheaders = array($meetingtstr, $whatstr, 
                            $whostr, $whatresultedstr);

    $table = new flexible_table('mod-mrproject-individualdeliverables');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);

    $table->define_baseurl($taburl);

    
    $table->sortable(true, 'mrprojectid'); // Sorted by date by default.
    $table->no_sorting('meetingpurpose');
    $table->no_sorting('studentfullname');
    $table->no_sorting('grade');

    $table->set_attribute('cellspacing', '3');


    $table->collapsible(true);      // Allow column hiding.
    $table->initialbars(true);

    

    //$table->column_suppress('courseshort');
    $table->column_suppress('mrprojectid');
    //$table->column_suppress('starttime');
    //$table->column_suppress('meetingpurpose');
    //$table->column_suppress('studentfullname');

    $table->set_attribute('id', 'dates');
    $table->set_attribute('class', 'datelist');

    $table->column_class('course', 'datelist_course');
    $table->column_class('mrproject', 'datelist_mrproject');

    $table->setup();

    // Get extra query parameters from flexible_table behaviour.
    $where = $table->get_sql_where();
    $sort = $table->get_sql_sort();
    $table->pagesize($limit, $numrecords);

    if (!empty($sort)) {
        $sql .= " ORDER BY $sort";
    }

    $results = $DB->get_records_sql($sql, $params);

    $meetingfilter = null;
    foreach ($results as $id => $row) {

        if ($row->starttime < time()) {    //Meetings held

            if ($row->studentid != 0) {
                
                $teachercaps = ['mod/mrproject:managealltasks']; 
                $isteacher = has_any_capability($teachercaps, $context, $row->studentid);
                if (!$isteacher) {

                    $courseurl = new moodle_url('/course/view.php', array('id' => $row->courseid));
                    $coursedata = html_writer::link($courseurl, format_string($row->courseshort));
                    $meetingurl = new moodle_url('/mod/mrproject/view.php', array('a' => $row->mrprojectid));
                    

                    //Meeting (date, ...)
                    $a = mod_mrproject_renderer::meetinginfos($row->starttime, $row->duration, $row->tasklocation);
                    $meetingdata = get_string('meetingdatetime', 'mrproject', $a);



                    //$link = '?id=149&sesskey=GEXWEyBe4o&subpage=collectivedeliverablessubtab&offset=-1&what=viewmeeting&taskid=15';
                    /*$tsk = (string)$row->id;
                    $link = format_string('?'.'id='.(string)$mrproject->cmid.'&sesskey='.(string)sesskey().'&subpage=collectivedeliverablessubtab&offset=-1&what=viewmeeting&taskid='.$tsk);
                    $meetingdata = html_writer::link ($link, get_string('meetingdatetime', 'mrproject', $a));*/



                    //Defined tasks (div)
                    $whatdata =  html_writer::div($row->tasknote, 'definedtask');
                    //$whatdata = $output->format_notes($row->tasknote, $row->tasknoteformat, $context, 'tasknote', $row->id);


                    //Assigned to students 
                    $teachercaps = ['mod/mrproject:managealltasks']; 
                    $isteacher = has_any_capability($teachercaps, $context);

                    if ($isteacher) {       //Teacher view: Evaluate student task
                        
                        if ($row->collectivetask == null || $row->collectivetask == '0') {       //Individual task
                            $whourl = new moodle_url('/mod/mrproject/view.php',
                                        array('what' => 'viewstudent', 'a' => $row->mrprojectid, 'taskid' => $row->id, 'studentid' => $row->studentid));

                            $whodata = html_writer::link($whourl, $row->studentfullname).'<br/>';


                        } else {     //Collective task
                            $whodata = '';
                            $studentids = explode('+' ,$row->collectivetask);
                            foreach ($studentids as $studentid) {
                                $whourl = new moodle_url('/mod/mrproject/view.php',
                                        array('what' => 'viewstudent', 'a' => $row->mrprojectid, 'taskid' => $row->id, 'studentid' => $studentid));

                                $whodata .= html_writer::link($whourl, fullname($mrproject->get_userbyid($studentid))) .'<br/>';

                            }
                        }

                        //notyetsubmitted, submitted
                        if ($row->submissiondate == 0 && $row->duedate <= time()) {
                            $whodata .= html_writer::link('', get_string('notyetsubmitted', 'mrproject'), array('class' => 'notyetsubmitted'));   //notyetsubmitted
                        } 
    
                        if ($row->submissiondate > 0) {
                            $whodata .= html_writer::link('', get_string('tasksubmitted', 'mrproject'), array('class' => 'submitted'));    //submitted
                        } 


                    } else {        //Student view: submit task report
                        
                            if ($row->collectivetask == null || $row->collectivetask == '0') {     //Individual task
                                if ($row->studentid == $USER->id) {   //can only submit for his tasks
                                    $whourl = new moodle_url('/mod/mrproject/view.php',
                                                array('what' => 'submittaskreport', 'sesskey' => sesskey(), 'a' => $row->mrprojectid, 'taskid' => $row->id, 'studentid' => $row->studentid));
                                    
                                    $whodata = html_writer::link($whourl, $row->studentfullname, ['class' => 'enabledstudent']).'<br/>';   
                                } else {

                                    //$whourl = null;
                                    $whourl = new moodle_url('/mod/mrproject/view.php',
                                                array('what' => 'submittaskreport', 'sesskey' => sesskey(), 'a' => $row->mrprojectid, 'taskid' => $row->id, 'studentid' => $row->studentid));
                                    
                                    $whodata = html_writer::link($whourl, $row->studentfullname, ['class' => 'disabledstudent']).'<br/>';   
                                }

                            } else {       //Collective task
                                $whodata = '';
                                $studentids = explode('+', $row->collectivetask);
                                foreach ($studentids as $student) {
                                    if (intval($student) == $USER->id) {      //can only submit for his tasks
                                        $whourl = new moodle_url('/mod/mrproject/view.php',
                                                array('what' => 'submittaskreport', 'sesskey' => sesskey(), 'a' => $row->mrprojectid, 'taskid' => $row->id, 'studentid' => intval($student)));
                                    
                                        $whodata .= html_writer::link($whourl, fullname($mrproject->get_userbyid(intval($student))), ['class' => 'enabledstudent']) .'<br/>';
                                    
                                    } else {
                                        //$whourl = null;
                                        $whourl = new moodle_url('/mod/mrproject/view.php',
                                                array('what' => 'submittaskreport', 'sesskey' => sesskey(), 'a' => $row->mrprojectid, 'taskid' => $row->id, 'studentid' => intval($student)));
                                    
                                        $whodata .= html_writer::link($whourl, fullname($mrproject->get_userbyid(intval($student))), ['class' => 'disabledstudent']) .'<br/>';
                                    }
                                }
                            }          
                    }
                    
                    

                    
                    
                    
                    //Grade
                    //$gradedata = $row->scale == 0 ? '' : $output->format_grade($row->scale, $row->grade);   
                    $gradedata = '<strong>'.$row->grade.'</strong>';

                    


                    $dataset = array(
                                    $meetingdata,
                                    $whatdata,
                                    //$output->format_task_notes($mrproject, $row),
                                    $whodata,
                                    $gradedata
                                );


                    //Display meetings with group filter
                    if ($row->studentid && groups_is_member($currentgroupid, $row->studentid) && groups_is_member($currentgroupid, $row->teacherid) && groups_is_member($currentgroupid, $USER->id) && $meetingfilter != $row) {
                        
                       //can view only the tasks of the group (other teacher tasks in the group)
                        $table->add_data($dataset);
                        $meetingfilter = $row;
                        

                    } else {  //Display all meetings
                        if ($currentgroupid == 0 && $meetingfilter != $row) {

                            
                            foreach ($groupsthatcanseeme as $id => $group) {
                                if (groups_is_member($id, $row->teacherid) && groups_is_member($id, $row->studentid) && groups_is_member($id, $USER->id) && $meetingfilter != $row) {
                                    $table->add_data($dataset);
                                    $meetingfilter = $row;
                                }
                            }

                            //if (groups_is_member($currentgroupid, $row->teacherid)) {   //can view only the tasks of the group (other teacher tasks in the group)
                                //$table->add_data($dataset);
                                //$meetingfilter = $row;
                            //}
                        }
                    }

                }
            }
        }
    }
    $table->print_html();


    //Export students' results in this project (Average grades)
    $teachercaps = ['mod/mrproject:managealltasks']; 
    $isteacher = has_any_capability($teachercaps, $context);
    if ($isteacher) {
        $exporturl = new moodle_url($CFG->wwwroot .'/grade/export/xls/index.php?id='. $COURSE->id);
        echo html_writer::link($exporturl, '<strong>'.get_string('exportgrades', 'mrproject').'</strong>', array('target' => '_blank', 'rel' => 'noopener'));
        echo $output->action_icon($exporturl, new pix_icon('i/grades', get_string('exportgrades', 'mrproject'))) . '<br/><br/>';
    
    } else {
        //Total grade (the mean grade) ----> for student
        $totalgradeinfo = new mrproject_totalgrade_info($mrproject, $mrproject->get_gradebook_info($USER->id));
        if ($mrproject->uses_grades()) {
            $totalgradeinfo->showtotalgrade = true;
            $totalgradeinfo->totalgrade = $mrproject->get_user_grade($USER->id);
            echo $output->render($totalgradeinfo) . '<br/>';
        }
    }

    
    
} else {
    notice(get_string('noresults', 'mrproject'), $returnurl);
}


}

echo $output->footer();
