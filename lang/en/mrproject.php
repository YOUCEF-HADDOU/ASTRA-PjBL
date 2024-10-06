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
 * Strings for component 'mod_mrproject', language 'en'
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



$string['pluginname'] = 'mrproject';
$string['pluginadministration'] = 'mrproject administration';
$string['modulename'] = 'MRProject';
$string['modulename_help'] = 'The mrproject activity is based on a new teaching method, Multi Role Project (MRP), in which the student works in a group and learns in project mode.';
$string['modulename_link'] = 'mod/mrproject/view';
$string['modulenameplural'] = 'mrprojects';




/* ***** Capabilities ****** */
$string['mrproject:addinstance'] = 'Add a new mrproject';
$string['mrproject:appoint'] = 'Create meetings';
$string['mrproject:attend'] = 'Student attendance';
$string['mrproject:canscheduletootherteachers'] = 'Schedule tasks for students';
$string['mrproject:canseeotherteachersbooking'] = 'See and browse other teachers meetings';
$string['mrproject:disengage'] = '';
$string['mrproject:manage'] = 'Manage your meetings and tasks';
$string['mrproject:managealltasks'] = 'Manage all mrproject data';
$string['mrproject:viewmeetings'] = 'See meetings that are open for appointments (in student screen)';
$string['mrproject:viewfullmeetings'] = 'See all held meetings (in student screen)';
$string['mrproject:seeotherstudentsbooking'] = 'See upcoming meetings';
$string['mrproject:seeotherstudentsresults'] = 'See other meeting student\'s results';
$string['mrproject:seeoverviewoutsideactivity'] = 'See meetings of other teachers.';
$string['mrproject:editallattended'] = 'Mark students in all meetings as attended / not attended.';
$string['mrproject:editallgrades'] = 'Edit grades.';
$string['mrproject:editallnotes'] = 'Edit task description.';
$string['mrproject:isstudent'] = 'See the student side activity';





/* ***** Events ***** */

$string['lmslearningexperience'] = 'Student\'s behaviour';
$string['submittedtask'] = '<strong>Submitted task</strong>';
$string['studentlearningexperience'] = '<strong>Learning experience</strong>';

$string['event_projectpresentationviewed'] = 'mrproject: project presentation viewed';


$string['event_studentresponsibilitiesmodified'] = 'mrproject: student responsibilities modified';
$string['event_supervisorrolesmodified'] = 'mrproject: supervisor roles modified';


$string['event_availablemeetingsviewed'] = 'mrproject: available meetings viewed';
$string['event_meetingaccepted'] = 'mrproject: meeting accepted';
$string['event_meetingcanceled'] = 'mrproject: meeting canceled';


$string['event_meetingadded'] = 'mrproject: meeting added';
$string['event_meetingupdated'] = 'mrproject: meeting updated';
$string['event_meetingdeleted'] = 'mrproject: meeting deleted';
$string['event_meetinglistviewed'] = 'mrproject: meeting list viewed';


$string['event_meetingreportedited'] = 'mrproject: meeting report edited';
$string['event_meetingreportviewed'] = 'mrproject: meeting report viewed';
$string['event_taskadded'] = 'mrproject: task added';
$string['event_taskdeleted'] = 'mrproject: task deleted';

$string['event_taskreportssubmitted'] = 'mrproject: task reports submitted';
$string['event_taskreportsviewed'] = 'mrproject: task reports viewed';


$string['event_dependencyadded'] = 'mrproject: dependency added';
$string['event_dependencydeleted'] = 'mrproject: dependency deleted';

$string['event_gradeadded'] = 'mrproject: grade added';




/* ***** Message types ***** */
$string['messageprovider:invitation'] = 'Invitation to a meeting';
$string['messageprovider:bookingnotification'] = 'Notification when a acceptance of meeting is made or cancelled';
$string['messageprovider:reminder'] = 'Reminder of an upcoming meeting';




/* ***** Search areas ***** */
$string['search:activity'] = 'mrproject - activity information';




/* ***** Privacy API strings **** */

$string['privacy:metadata:mrproject_meeting'] = 'Represents one meeting in a mrproject';

$string['privacy:metadata:mrproject_meeting:teacherid'] = 'Teacher associated with the meeting';
$string['privacy:metadata:mrproject_meeting:starttime'] = 'Start time of the meeting';
$string['privacy:metadata:mrproject_meeting:duration'] = 'Duration of the meeting in minutes';
$string['privacy:metadata:mrproject_meeting:tasklocation'] = 'Task location';
$string['privacy:metadata:mrproject_meeting:notes'] = 'Notes about the meeting';
$string['privacy:metadata:mrproject_meeting:notesformat'] = "Format of the notes";
$string['privacy:metadata:mrproject_meeting:exclusivity'] = "Maximum number of students on the meeting";

$string['privacy:metadata:mrproject_task'] = 'Represents a student task in a mrproject';

$string['privacy:metadata:mrproject_task:studentid'] = "Student who has the task";
$string['privacy:metadata:mrproject_task:attended'] = "Whether the task was attended";
$string['privacy:metadata:mrproject_task:grade'] = "Grade for the task";
$string['privacy:metadata:mrproject_task:tasknote'] = "Note by teacher (visible to student)";
$string['privacy:metadata:mrproject_task:tasknoteformat'] = "Format of teacher note";
$string['privacy:metadata:mrproject_task:teachernote'] = "Note by teacher (private)";
$string['privacy:metadata:mrproject_task:teachernoteformat'] = "Format of private teacher note";
$string['privacy:metadata:mrproject_task:studentnote'] = "Note by student";
$string['privacy:metadata:mrproject_task:studentnoteformat'] = "Format of student note";

$string['privacy:metadata:filepurpose'] = 'File used in notes for the meeting or task';








/* ***** Interface strings ****** */

$string['onedaybefore'] = '1 day before meeting';
$string['oneweekbefore'] = '1 week before meeting';
$string['areatasknote'] = 'Files in task notes';
$string['areameetingnote'] = 'Files in meeting notes';
$string['areateachernote'] = 'Files in confidential notes';
$string['action'] = 'Action';
$string['actions'] = 'Actions';
$string['addtask'] = 'Add another task';
$string['addanotherstudent'] = 'Add another student';
$string['addcommands'] = 'Add a meeting';
$string['addondays'] = 'Add tasks on';
$string['addsession'] = 'Student team meeting';
$string['addsinglemeeting'] = 'Add a meeting';
$string['editresponsibility'] = '<strong>Responsibility of each student in the team</strong>';
$string['editteacherrole'] = 'Edit teachers role';
$string['addmeeting'] = 'You can add additional task meetings at any time.';
$string['addstudenttogroup'] = 'Add this student to task group';
$string['alltasks'] = 'All tasks';
$string['allononepage'] = 'All meetings on one page';
$string['allowgroup'] = 'Exclusive meeting - click to change';
$string['alreadyappointed'] = 'Cannot make the task. The meeting is already fully reserved.';
$string['appointfor'] = 'Make task for';
$string['appointforgroup'] = 'Make tasks for: {$a}';
$string['appointingstudent'] = 'Task for meeting';
$string['appointingstudentinnew'] = 'Task for new meeting';
$string['task'] = 'Task';
$string['taskno'] = 'Task {$a}';
$string['dependencyno'] = 'Dependency ({$a})';
$string['studentno'] = 'Student {$a}';
$string['teachernote'] = 'Teacher note';
$string['tasks'] = 'Tasks';
$string['tasksgrouped'] = 'Tasks grouped by meeting';
$string['appointsolo'] = 'just me';
$string['appointsomeone'] = 'Add new task';
$string['tasksummary'] = 'Task on {$a->startdate} from {$a->starttime} to {$a->endtime} with {$a->teacher}';
$string['attendable'] = 'Attendable';
$string['attendablelbl'] = 'Total candidates for scheduling';
$string['attended'] = 'Attended';
$string['attendedlbl'] = 'Amount of attended students';
$string['attendedmeetings'] = '<strong>Meetings held</strong>';
$string['availablemeetings'] = '<strong>Available meetings</strong>';
$string['availablemeetingsall'] = 'All meetings';
$string['availablemeetingsnotowned'] = 'Not owned';
$string['availablemeetingsowned'] = 'Owned';
$string['bookingformoptions'] = 'Booking form and student-supplied data';
$string['bookinginstructions'] = 'Booking instructions';
$string['bookinginstructions_help'] = 'This text will be displayed to students before they make a booking. It can, for example, instruct students how to fill out the optional message field or which files to upload.';
$string['bookmeeting'] = 'Accept meeting';
$string['acceptdate'] = '<em><u>Accept a date:</u></em> ';
$string['pendingacceptance'] = '<em><u>Pending acceptance!</u></em> ';


$string['bookameeting'] = 'Accept a meeting';
$string['bookingdetails'] = 'Meeting details';
$string['bookwithteacher'] = 'Teacher';
$string['break'] = 'Break between meetings';
$string['breaknotnegative'] = 'Length of the break must not be negative';
$string['cancelbooking'] = 'Cancel meeting';
$string['canbooksingletask'] = 'You can book one task in this mrproject.';
$string['canbook1task'] = 'You can book one more task in this mrproject.';
$string['canbookntasks'] = 'You can book {$a} more tasks in this mrproject.';
$string['canbooknofurthertasks'] = 'You cannot book further tasks in this mrproject.';
$string['canbookunlimitedtasks'] = 'You can book any number of tasks in this mrproject.';
$string['chooseexisting'] = 'Choose existing';
$string['choosingmeetingstart'] = 'Choosing the start time';
$string['comments'] = 'Comments';
$string['meetingpurpose'] = 'Meeting purpose';
$string['meetingoutcomes'] = 'Meeting outcomes';
$string['conflictlocal'] = '{$a->datetime} ({$a->duration} minutes) in this mrproject';
$string['conflictremote'] = '{$a->datetime} ({$a->duration} minutes) in course {$a->courseshortname}, mrproject {$a->mrprojectname}';
$string['contentformat'] = 'Format';
$string['contentformat_help'] = '<p>There are three basic choices for the export format,
     differing in how meetings with several tasks are handled.
     <dl>
         <dt>One line per meeting</dt>:
         <dd>
             The output file will contain one line for each meeting. If a meeting contains multiple
             tasks, then instead of the student\'s name, etc., a marker "(multiple)" will be shown.
         </dd>
         <dt>One line per task</dt>:
         <dd>
             The output file will contain one line for each task. If a meeting contains multiple
             tasks, then it will appear several times in the list (with its data repeated).
         </dd>
         <dt>Tasks grouped by meeting</dt>:
         <dd>
             All tasks of one meeting are grouped together, preceded by a header line that
             indicates the meeting in question. This may not work well with the CSV output file format,
             as the number of columns is not constant.
         </dd>
    </dl>
    You can explore the effect of these options using the "Preview" button.</p>';
$string['complete'] = 'Reserved';
$string['confirmbooking'] = "Confirm reservation";
$string['confirmdelete-all'] = 'This will delete <b>all</b> meetings in this mrproject. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-mine'] = 'This will delete all your meetings in this mrproject. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-myunused'] = 'This will delete all your unused meetings in this mrproject. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-selected'] = 'This will delete the selected meetings. Deletion cannot be undone. Continue anyway?';
$string['confirmdelete-one'] = 'Delete meeting?';
$string['confirmdelete-unused'] = 'This will delete all unused meetings in this mrproject. Deletion cannot be undone. Continue anyway?';
$string['confirmrevoke'] = 'Revoke all tasks in the current meeting?';
$string['conflictingmeetings'] = 'The meeting on {$a} cannot be created due to conflicting meetings:';
$string['copytomyself'] = 'Send a copy to myself';
$string['course'] = 'Course';
$string['createexport'] = 'Export file';
$string['csvformat'] = 'CSV';
$string['csvfieldseparator'] = 'Field separator for CSV';
$string['cumulatedduration'] = 'Summed duration of tasks';
$string['datatoinclude'] = 'Data to include';
$string['datatoinclude_help'] = 'Select the fields that should be included in the export. Each of these will appear in one column of the output file.';
$string['date'] = 'Date';
$string['proposeddates'] = 'Proposed meeting dates';
$string['proposed'] = 'Proposed dates';
$string['datelist'] = 'Overview';
$string['defaultmeetingduration'] = 'Default meeting duration';
$string['defaultmeetingduration_help'] = 'The default length (in minutes) for task meetings that you set up';
$string['deleteallmeetings'] = 'Delete all meetings';
$string['deleteallunusedmeetings'] = 'Delete unused meetings';
$string['deletecommands'] = 'Delete meetings';
$string['deletemymeetings'] = 'Delete all my meetings';
$string['deleteselection'] = 'Delete selected meetings';
$string['deletethesemeetings'] = 'Delete these meetings';
$string['deleteunusedmeetings'] = 'Delete my unused meetings';
$string['deleteonsave'] = 'Delete this task (when saving the report)';
$string['deletedependency'] = 'Delete this dependency (when saving changes)';
$string['deletedconflictingmeetings'] = 'For the meeting on {$a}, conflicting meetings have been deleted:';
$string['department'] = 'From where?';
$string['disengage'] = 'Drop my tasks';
$string['displayfrom'] = 'Display meeting to students from';
$string['distributedgrade'] = 'Evaluate the whole group with a collective grade';
$string['divide'] = 'Divide into meetings?';
$string['duration'] = 'Duration'; 
$string['consumedtime'] = 'Consumed time'; 
$string['dependencynote'] = 'Please specify the dependencies of the external tools used while carrying out this task. This reflects your behaviour during the process.'; 
$string['competencies'] = 'Competencies and Skills';
$string['durationrange'] = 'Meeting duration must be between {$a->min} and {$a->max} minutes.';
$string['editbooking'] = 'Meeting report';
$string['taskreport'] = '<strong>Task report</strong>';
$string['seedetails'] = 'Meeting report ';
$string['meetingreport'] = '<strong>Meeting report</strong>';
$string['meetingreportbutton'] = 'Edit meeting report';
$string['emailreminder'] = 'Email a reminder';
$string['emailreminderondate'] = 'Send an email reminder on';
$string['end'] = 'End';
$string['enddate'] = 'Repeat time meetings until';
$string['excelformat'] = 'Excel';
$string['exclusive'] = 'Exclusive';
$string['exclusivity'] = 'Exclusivity';
$string['exclusivitypositive'] = 'The number of students per meeting needs to be 1 or more.';
$string['exclusivityoverload'] = 'The meeting has {$a} appointed students, more than allowed by this setting.';
$string['explaingeneralconfig'] = 'These options can only be setup at site level and will apply to all mrprojects of this Moodle installation.';
$string['export'] = 'Export';
$string['exporthdr'] = '<strong>Export meetings, tasks, and student grades</strong>';
$string['exporttimerange'] = 'Time range';
$string['exporttimerangeall'] = 'Future and past meetings';
$string['exporttimerangefuture'] = 'Only future meetings';
$string['exporttimerangepast'] = 'Only past meetings';
$string['everyone'] = 'Everyone';
$string['field-date'] = 'Date';
$string['field-starttime'] = 'Start time';
$string['field-endtime'] = 'End time';
$string['field-location'] = 'Location';
$string['field-maxstudents'] = 'Max. students';
$string['field-studentfullname'] = 'Student full name';
$string['field-studentfirstname'] = 'Student first name';
$string['field-studentlastname'] = 'Student last name';
$string['field-studentemail'] = 'Student email';
$string['field-studentusername'] = 'Student user name';
$string['field-studentidnumber'] = 'Student id number';
$string['field-attended'] = 'Attended';
$string['field-meetingnotes'] = 'Meeting purpose';
$string['field-tasknote'] = 'Task';
$string['field-teachernote'] = 'Confidential note (teacher only)';
$string['field-studentnote'] = 'Note (Appreciation)';
$string['field-filecount'] = 'Number of uploaded files';
$string['field-grade'] = 'Grade';
$string['field-groupssingle'] = 'Group';
$string['field-groupssingle-label'] = 'Group';
$string['field-groupsmulti'] = 'Groups (several columns)';
$string['fileformat'] = 'File format';
$string['fileformat_help'] = 'The following file formats are available:
     <ul>
          <li>Comma Separated Value (CSV) text files. The field separator, by default a comma, can be chosen below.
               CSV files can be opened in most spreadshet applications;</li>
          <li>Microsoft Excel files (Excel 2007 format);</li>
          <li>Open Document spreadsheets (ODS);</li>
          <li>HTML format - a web page displaying the output table,
                which can be printed using the browser\'s print feature;</li>
          <li>PDF documents. You can choose between landscape and portrait orientation.</li>
     </ul>';
$string['totalgrade'] = 'Final grade (Average score):';
$string['firstmeetingavailable'] = 'The first meeting will be open on: {$a}';
$string['forbidgroup'] = 'Group meeting - click to change';
$string['forcewhenoverlap'] = 'Force when overlap';
$string['forcourses'] = 'Choose students in courses';
$string['friday'] = 'Friday';
$string['generalconfig'] = 'General configuration';
$string['grade'] = 'Grade';
$string['gradeingradebook'] = '<u><strong>Final grade:</strong></u>';
$string['gradingstrategy'] = 'Grading strategy';
$string['gradingstrategy_help'] = 'In a mrproject where students can have several tasks, select how grades are aggregated.
    The gradebook can show either <ul><li>the mean grade or</li><li>the maximum grade</li></ul> that the student has achieved.';
$string['group'] = 'group ';
$string['groupbreakdown'] = 'By group size';
$string['groupbookings'] = 'Reservation in groups';
$string['groupbookings_help'] = 'Allow students to accept a meeting for all members of their group.
(Note that this is separate from the "group mode" setting, which controls the meetings a student can see.)';
$string['groupmodeyourgroups'] = 'Group mode: {$a->groupmode}. Only students in {$a->grouplist} can make meetings with you.';
$string['groupmodeyourgroupsempty'] = 'Group mode: {$a->groupmode}. You are not member of any group, therefore students cannot make meetings with you.';
$string['groupscheduling'] = 'Enable group scheduling';
$string['groupscheduling_desc'] = 'Allow entire groups to be scheduled at once.
(Apart from the global option, the setting "Booking in groups" must be enabled in the respective mrproject instance.)';

$string['separategroups'] = 'Note that this is a <strong>Separate groups mode</strong> that controls which groups a teacher/student can see (you cannot 
see the other groups). <em>Select your group from the <u>"My groups"</u> list.</em>';

$string['groupsession'] = 'Group';
$string['groupsize'] = 'Group size';
$string['guardtime'] = 'Guard time';
$string['guestscantdoanything'] = 'Guests can\'t do anything here.';
$string['htmlformat'] = 'HTML';
$string['howtoaddstudents'] = '';
$string['ignoreconflicts'] = 'Ignore scheduling conflicts';
$string['ignoreconflicts_help'] = 'If this box is ticked, then the meeting will be moved to the requested date and time, even if other meetings exist at the same time. This may lead to overlapping tasks for some teachers or students, and should therefore be used with care.';
$string['ignoreconflicts_link'] = 'mod/mrproject/conflict';
$string['includeemptymeetings'] = 'Include empty meetings';
$string['includemeetingsfor'] = 'Include meetings for';
$string['incourse'] = ' in course ';
$string['mixindivgroup'] = 'Mix individual and group bookings';
$string['mixindivgroup_desc'] = 'Where group scheduling is enabled, allow individual bookings as well.';
$string['context'] = 'Context';
$string['problem'] = 'Problem';
$string['objective'] = 'Objective';
$string['isnonexclusive'] = 'Non-exclusive';
$string['landscape'] = 'Landscape';
$string['lengthbreakdown'] = 'By meeting duration';
$string['limited'] = 'Limited ({$a} left)';
$string['location'] = 'Location';
$string['markseen'] = 'After you have had an task with a student please mark them as "Seen" by clicking the checkbox near to their user picture above.';
$string['markasseennow'] = 'Mark as seen now';
$string['maxgrade'] = 'Take the highest grade';
$string['maxstudentspermeeting'] = 'Maximum number of students per meeting';
$string['maxstudentspermeeting_desc'] = 'Group meetings / non-exclusive meetings can have at most this number of students. Note that in addition, the setting "unlimited" can always be chosen for a meeting.';
$string['maxstudentlistsize'] = 'Maximum length of student list';
$string['maxstudentlistsize_desc'] = 'The maximum length of the list of students who need to make an task, as shown in the teacher view of the mrproject. If there are more students than this, no list will be displayed.';
$string['meangrade'] = 'Take the mean grade';
//$string['meetingwith'] = 'Meeting with your';
$string['meetingwithplural'] = 'Meeting with your';
$string['message'] = 'Message';
$string['messagesent'] = 'Message sent to {$a} recipients';
$string['messagesubject'] = 'Subject';
$string['messagebody'] = 'Message body';
$string['minutes'] = 'minutes';
$string['minutespermeeting'] = 'minutes per meeting';
$string['missingstudents'] = '{$a} students still need to make an task';
$string['missingstudentsmany'] = '{$a} students still need to make an task. No list is being displayed due to size.';
$string['mode'] = 'Mode';
$string['modeintro'] = 'Students can register';
$string['modetasks'] = 'task(s)';
$string['modeoneonly'] = 'in this mrproject';
$string['modeoneatatime'] = 'at a time';
$string['monday'] = 'Monday';
$string['multiple'] = '(multiple)';
$string['mytasks'] = 'My tasks';
$string['myself'] = 'Myself';
$string['name'] = 'mrproject name';
$string['projectname'] = 'Project name';
$string['needteachers'] = 'Meetings cannot be added as this course has no teachers';
$string['negativerange'] = 'Range is negative. This can\'t be.';
$string['never'] = 'Never';
$string['nfiles'] = '{$a} files';
$string['notasks'] = 'No tasks';
$string['noexistingstudents'] = 'No students available';
$string['selectgroup'] = 'Please select a group above';
$string['nogroups'] = 'No group available.';
$string['noresults'] = 'No results. ';
$string['nomrprojects'] = 'There are no mrprojects';
$string['nomeetings'] = 'There are no task meetings available.';
$string['nomeetingsavailable'] = 'No meetings are available for booking at this time.';
$string['nomeetingsopennow'] = 'No meetings are open for booking right now.';
$string['nostudents'] = 'No students scheduled';
$string['nostudenttobook'] = 'No student to book';
$string['note'] = 'Grade';
$string['noteacherformeeting'] = 'No teacher for the meetings';
$string['noteachershere'] = 'No teacher available';
$string['notenoughplaces'] = 'Sorry, there are not enough free tasks in this meeting.';
$string['notesrequired'] = 'You must enter text into this field';
$string['notifications'] = 'Notifications';
$string['notseen'] = 'Not seen';
$string['now'] = 'Now';
$string['occurrences'] = 'Occurrences';
$string['odsformat'] = 'ODS';
$string['on'] = 'on';
$string['onelinepertask'] = 'One line per task';
$string['onelinepermeeting'] = 'One line per meeting';
$string['onemeetingadded'] = '1 meeting added';
$string['teachersrolesupdated'] = 'The role of teachers has been updated';
$string['studentsresponsibilitiesupdated'] = 'The responsibility of students has been updated';
$string['onemeetingdeleted'] = '1 meeting deleted';
$string['onthemorningoftask'] = 'On the morning of the task';
$string['options'] = 'Options';
$string['otherstudents'] = 'Participants';
$string['outlinetasks'] = '{$a->attended} tasks attended, {$a->upcoming} upcoming. ';
$string['outlinegrade'] = 'Grade: {$a}.';
$string['overall'] = 'Overall';
$string['overlappings'] = 'Some other meetings are overlapping';
$string['pageperteacher'] = 'One page for each {$a}';
$string['pagination'] = 'Pagination';
$string['pagination_help'] = 'Choose whether the export should contain a separate page for each teacher.
   In Excel and in ODS file format, these pages correspond to tabs (worksheets) in the workbook.';
$string['pdfformat'] = 'PDF';
$string['pdforientation'] = 'PDF page orientation';
$string['portrait'] = 'Portrait';
$string['preview'] = 'Preview ';
$string['previewlimited'] = '(Preview is limited to {$a} rows.)';
$string['purgeunusedmeetings'] = 'Purge unused meetings in the past';
$string['recipients'] = 'Recipients';
$string['registeredlbl'] = 'Student appointed';
$string['reminder'] = 'Reminder';
$string['requireupload'] = 'File upload required';
$string['resetmeetings'] = 'Delete mrproject meetings';
$string['resettasks'] = 'Delete tasks and grades';
$string['revealteachernotes'] = 'Reveal teacher notes in privacy exports';
$string['revealteachernotes_desc'] = 'If this option is selected, then confidential teacher notes (which are normally not visible to students)
will be revealed to students in data export requests, i.e., via the privay API. You should decide based on individual usage of this field
whether it needs to be included in data exports for students under the GDPR.';
$string['return'] = 'Back to course';
$string['revoke'] = 'Revoke the task';
$string['saturday'] = 'Saturday';
$string['save'] = 'Save';
$string['savechoice'] = 'Save my choice';
$string['saveseen'] = 'Save seen';
$string['schedule'] = 'Schedule';
$string['schedulemeeting'] = '<strong>Plan a meeting for:</strong> {$a}';
$string['schedulecancelled'] = '{$a} : Your task cancelled or moved';
$string['schedulegroups'] = '<strong>Plan student team meetings</strong>';
$string['scheduleinnew'] = 'Schedule in a new meeting';
$string['scheduleinmeeting'] = 'Plan a meeting';
$string['mrproject'] = 'mrproject';
$string['schedulestudents'] = 'Schedule by student';
$string['scopemenu'] = 'Show meetings in: {$a}';
$string['scopemenuself'] = 'Show my meetings in: {$a}';
$string['seen'] = 'Seen';
$string['selectedtoomany'] = 'You have selected too many meetings. You can select no more than {$a}.';
$string['sendmessage'] = 'Send message';
$string['sendinvitation'] = 'Send invitation';
$string['sendreminder'] = 'Send reminder';
$string['sendreminders'] = 'Send e-mail reminders for tasks not yet submitted';
$string['sepcolon'] = 'Colon';
$string['sepcomma'] = 'Comma';
$string['sepsemicolon'] = 'Semicolon';
$string['septab'] = 'Tab';
$string['showemailplain'] = 'Show e-mail addresses in plain text';
$string['showemailplain_desc'] = 'In the teacher\'s view of the mrproject, show the e-mail addresses of students needing an task in plain text, in addition to mailto: links.';
$string['showparticipants'] = 'Show participants';
$string['meeting_is_just_in_use'] = 'Sorry, the task has just been chosen by another student! Please try again.';
$string['meetingdatetime'] = '{$a->shortdatetime} <br/> Duration: {$a->duration} minutes <br/> Location: {$a->tasklocation}';
$string['meetingdatetimelong'] = '{$a->date}, {$a->starttime} &ndash; {$a->endtime}';
$string['meetingdatetimelabel'] = 'Date and time';
$string['meetingdescription'] = '{$a->status} on {$a->startdate} from {$a->starttime} to {$a->endtime} at {$a->location} with {$a->facilitator}.';
$string['meeting'] = 'Meeting';
$string['meet'] = 'Meeting {$a->duration}';


$string['meetingnember'] = 'Meeting: {$i}';
$string['meetings'] = '<strong>My meetings</strong>';
$string['meetingsadded'] = '{$a} meetings have been added';
$string['meetingsdeleted'] = '{$a} meetings have been deleted';
$string['meetingtype'] = 'Meeting type';
$string['meetingupdated'] = '1 meeting updated';
$string['meetingreportadded'] = '1 meeting report added';
$string['meetingwarning'] = '<strong>Warning:</strong> Moving this meeting to the selected time conflicts with the meeting(s) listed below. Tick "Ignore scheduling conflicts" if you want to move the meeting nevertheless.';
$string['staffbreakdown'] = 'By {$a}';
$string['staffrolename'] = 'Role name of the teacher';
$string['start'] = 'Start';
$string['startpast'] = 'You can\'t start an empty task meeting in the past';
$string['expiredmeeting'] = 'The appointment is over You cannot edit this meeting';
$string['expiredtask'] = 'The task has timed out, you are submitting late';
$string['evaluatedtask'] = 'The task has been evaluated, and you cannot modify the submission.';
$string['disablemeetingreport'] = 'You cannot modify the meeting report, task evaluation has started.';
$string['statistics'] = 'Statistics';
$string['student'] = 'Student';
$string['studentbreakdown'] = 'By student';
$string['studentcomments'] = 'Student\'s message';
$string['studentdetails'] = 'Student details';
$string['studentfiles'] = 'Uploaded files';
$string['studentmultiselect'] = 'Each student can be selected only once in this meeting';
$string['studentnote'] = 'Note (Appreciation)';
$string['students'] = 'Student team';
$string['studentsteam'] = '<em>Student team</em>';
$string['studentprovided'] = 'Student provided: {$a}';
$string['sunday'] = 'Sunday';
$string['tab-thistask'] = 'This task';
$string['tab-othertasks'] = 'All tasks of this student';
$string['tab-studentlearningexperience'] = 'Student\'s Learning Experience';
$string['teacher'] = 'Teacher';
$string['teachersmenu'] = 'Show meetings for: {$a}';
$string['thismrproject'] = 'this mrproject';
$string['thiscourse'] = 'this course';
$string['thissite'] = 'the entire site';
$string['thursday'] = 'Thursday';
$string['timefrom'] = 'From:';
$string['timerange'] = 'Time range';
$string['timeto'] = 'To:';
$string['tuesday'] = 'Tuesday';
$string['unattended'] = 'Unattended';
$string['unlimited'] = 'Unlimited';
$string['unregisteredlbl'] = 'Unappointed students';
$string['upcomingmeetings'] = '<strong>Upcoming meetings</strong>';
$string['updategrades'] = 'Update grades';
$string['updatesinglemeeting'] = 'Update meeting';
$string['uploadrequired'] = 'You must upload files here before booking the meeting.';
$string['uploadstudentfiles'] = 'Upload task files';
$string['taskdependencies'] = 'Task dependencies';
$string['dependency'] = 'Dependency name';
$string['link'] = 'Dependency <br/> link';

$string['recommend'] = 'Recommend';
$string['recommended'] = 'Recommended';
$string['recommend_action'] = '';

$string['recommendations_count'] = 'Number of recommendations';

$string['filesubmissions'] = 'File submissions';
$string['uploadmaxfiles'] = 'Maximum number of uploaded files';
$string['uploadmaxfiles_help'] = 'The maximum number of files that a student can upload in the booking form. File upload is optional unless the "File upload required" box is ticked. If set to 0, students will not see a file upload box.';
$string['uploadmaxsize'] = 'Maximum file size';
$string['uploadmaxsize_help'] = 'Maximum file size for student uploads. This limit applies per file.';
$string['uploadmaxfilesglobal'] = 'Maximum number of uploaded files';
$string['uploadmaxfilesglobal_desc'] = 'The maximum number of files that a student can upload when submitting task files.';
$string['usebookingform'] = 'Use booking form';
$string['usebookingform_help'] = 'If enabled, student see a separate booking screen before they can book a meeting. The booking screen may require them to enter data, upload files, or solve a captcha; see options below.';
$string['usebookingform_link'] = 'mod/mrproject/bookingform';
$string['usecaptcha'] = 'Use CAPTCHA for new bookings';
$string['usecaptcha_help'] = 'If enabled, students will need to solve a CAPTCHA security question before making a new booking.
Use this setting if you suspect that students use automated programs to snap up available meetings.
<p>No captcha will be displayed if the student edits an existing booking.</p>';
$string['usenotes'] = 'Use notes for tasks';
$string['usenotesnone'] = 'none';
$string['usenotesstudent'] = 'Task note, visible to teacher and student';
$string['usenotesteacher'] = 'Confidential note, visible to teachers only';
$string['usenotesboth'] = 'Both types of notes';
$string['usestudentnotes'] = 'Let students enter a message';
$string['usestudentnotes_help'] = 'If enabled, the booking screen will contain a text box in which students can enter a message. Use the "booking instructions" above to instruct students what information they should supply.';
$string['viewbooking'] = 'See details';
$string['wednesday'] = 'Wednesday';
$string['welcomebackstudent'] = 'You can book additional meetings by clicking on the corresponding "Book meeting" button below.';
$string['welcomenewstudent'] = 'The table below shows all available meetings for an task. Make your choice by clicking on the corresponding "Book meeting" button. If you need to make a change later you can revisit this page.';
$string['welcomenewteacher'] = 'Please click on the button below to add team meetings.';
$string['what'] = 'Defined tasks';
$string['definetasks'] = 'Define tasks';
$string['whathappened'] = 'Teacher note';
$string['whatresulted'] = 'Grade';
$string['when'] = 'DateTime & Location';
$string['where'] = 'Location';
$string['who'] = 'Assigned to students';
$string['whosthere'] = 'Who\'s there ?';
$string['xdaysbefore'] = '{$a} days before meeting';
$string['xweeksbefore'] = '{$a} weeks before meeting';
$string['yesallgroups'] = 'Yes, for all groups';
$string['yesingrouping'] = 'Yes, in grouping {$a}';
$string['yesoptional'] = 'Yes, optional for student';
$string['yesrequired'] = 'Yes, student must enter a message';
$string['yourtasknote'] = 'Comments for your eyes';
$string['yourmeetingnotes'] = 'Comments on the meeting';
$string['yourteachernote'] = 'Supervisors\' feedback';
$string['editteachernote'] = 'Edit feedback';
$string['yourtotalgrade'] = 'Your total grade in this activity is <strong>{$a}</strong>.';   //$a is an object, string or number that can be used within translation strings

$string['formalside'] = '<strong>Formal side</strong>';
$string['informalside'] = '<strong>Informal side</strong>';


//MRP Tabs headar (Menu)
$string['welcometab'] = '<strong><em>Welcome</em></strong>';
$string['memberstab'] = '<strong><em>Members</em></strong>';
$string['meetingstab'] = '<strong><em>Meetings</em></strong>';
$string['mymeetingssubtab'] = '<em>My meetings</em>';
$string['upcomingmeetingssubtab'] = '<em>Upcoming meetings</em>';
$string['deliverablestab'] = '<strong><em>Deliverables</em></strong>';
$string['collectivedeliverablessubtab'] = '<em>Collective deliverables</em>';
$string['individualdeliverablessubtab'] = '<em>Individual deliverables</em>';
$string['dependenciessubtab'] = '<em>Recommended dependencies</em>';
$string['exporttab'] = '<strong><em>Export project data</em></strong>';
$string['emptysubtab'] = '';

$string['teamname'] = 'Team';
$string['studentlist'] = 'Students'; 
$string['planmeeting'] = '';


$string['noupcomingmeetings'] = 'There are no upcoming meetings.';
$string['nomeetingsavailable'] = 'No meetings are available.';
$string['noheldmeetings'] = 'No meetings held.';


$string['feedbacknote'] = 'Here you can view meeting reports and give your feedback on the meetings held.';
$string['acceptmeetingnote'] = 'Choose and accept a meeting from the proposed dates.';

$string['evaluationnote'] = 'Click on the student\'s name to evaluate the assigned task.';
$string['submityourtask'] = 'Click on your name to submit your assigned task.';


$string['alldependencies'] = '<strong>All recommended dependencies throughout this project</strong>';
$string['tasksdefined'] = '<strong>Tasks defined during meetings</strong>';
$string['tasknote'] = 'Task description';
$string['submittaskreport'] = 'Submit task report';
$string['meetingwith'] = 'Meeting with';
$string['changemeetingwith'] = ' <em>select to change</em> ';
$string['add'] = 'Add';
$string['data'] = 'Select the data to export';
$string['evaluation'] = '<strong>Evaluation</strong>';

$string['membersheader'] = 'Current group: ';
$string['memberstudents'] = '<strong>Student team</strong>';
$string['membersteachers'] = '<br/><strong>Supervisors</strong>';
$string['withsupervisor'] = '&nbsp; (<u>Supervisor</u>)';

$string['role'] = '<strong>Role</strong>';
$string['assignrole'] = 'Assign this <strong>Role</strong> to: ';
$string['rolefield'] = '<u>Role</u>';
$string['editroles'] = '<strong>Role of each supervisor in the team</strong>';
$string['responsibility'] = '<strong>Responsibility</strong>';
$string['responsibilityfield'] = '<u>Responsibility</u>';
$string['editresponsibilities'] = 'Edit student responsibilities';
$string['fullname'] = 'Full name';

$string['tutor'] = 'Tutor';
$string['expert'] = 'Expert';
$string['client'] = 'Client';


$string['copyright'] = '<strong><u>Copyright © 2024 Youcef Haddou (youcef.haddou@univ-tiaret.dz)</u> <br/>Some Rights Reserved!</strong>';
$string['madewith'] = 'Made with researchers from the Faculty of Mathematics and Computer Science of Ibn Khaldoun University of Tiaret, Algeria <br/>(Omar Talbi, Abdelkader Ouared, Abdelhafid Chadli)';




$string['projectpresentation'] = 'Project presentation';
$string['context'] = 'Context';
$string['problem'] = 'Problem';
$string['objective'] = 'Objective';
$string['projectstartdate'] = 'Project start date';
$string['projecttitle'] = 'Project title ';
$string['projectenddate'] = 'Project end date';

$string['grouplogo'] = '<em>Your team logo here</em>';
$string['smallgrouplogo'] = '<em>Logo</em>';

$string['editproject'] = 'Edit';
$string['editprojectpresentation'] = 'Project presentation';
$string['editgroups'] = 'Project teams';
$string['editgroupnamelogo'] = 'Group name & logo';
$string['editgroup'] = '<strong>Edit group name and logo for:</strong> {$a}';

$string['expireddates'] = '<em>Expired dates!</em>';
$string['acceptthisdate'] = '<u><em>Accept this date:</em></u>';



$string['completionrate'] = 'Completion rate';
$string['submissiondate'] = 'Submission date';
$string['afterdeadline'] = '&nbsp; <sub> (Submitted after the deadline!)</sub>';
$string['submittedontime'] = '&nbsp; <sub> (Submitted on time)</sub>';

$string['tasksubmitted'] = '<sub>(Submitted)</sub>';
$string['notyetsubmitted'] = '<sub>(Not yet Submitted)</sub>';


$string['meetingmode'] = 'Meeting mode';
$string['meetingmode0'] = 'Face-to-face meeting';
$string['meetingmode1'] = 'Remote meeting';
$string['meetingmode2'] = 'Hybrid meeting';


$string['startingdate'] = 'Starting date';
$string['duedate'] = 'Due date';


$string['finalgrade'] = 'Final grade';
$string['evaluatedby'] = 'Evaluated by';

$string['exportmessage'] = 'Export only past meetings.';


$string['todaytraces'] = 'Today\'s traces';
$string['alltraces'] = 'All traces';

$string['attendees'] = 'Attendees';
$string['selectattendees'] = 'Select participants';

$string['waspresent'] = '<em><u>Was present:</u></em> &nbsp;';
$string['wasabsent'] = '<em><u>Was absent:</u></em> &nbsp;&nbsp;';

$string['exportgrades'] = '<em><u>Export students\' results in this project (Average grades)</u></em> &nbsp;';




/* ***********  Help strings from here on ************ */

$string['forcewhenoverlap_help'] = '
<h3>Forcing meeting creation when meetings overlap</h3>
<p>This setting determines how new meetings will be handled if they overlap with other, already existing meetings.</p>
<p>If enabled, the overlapping existing meeting will be deleted and the new meeting created.</p>
<p>If disabled, the overlapping existing meeting will be kept and a new meeting will <em>not</em> be created.</p>
';
$string['forcewhenoverlap_link'] = 'mod/mrproject/conflict';

$string['taskmode'] = 'Setting the task mode';
$string['taskmode_help'] = '<p>You may choose here some variants in the way tasks can be taken. </p>
<p><ul>
<li><strong>"<emph>n</emph> tasks in this mrproject":</strong> The student can only book a fixed number of tasks in this activity. Even if the teacher marks them as "seen", they will not be allowed to book further meetings. The only way to reset ability of a student to book is to delete the old "seen" records.</li>
<li><strong>"<emph>n</emph> tasks at a time":</strong> The student can book a fixed number of tasks. Once the meeting is over and the teacher has marked the student as "seen", the student can book further tasks. However the student is limited to <emph>n</emph> "open" (unseen) meetings at any given time.
</li>
</ul>
</p>';

$string['appointagroup_help'] = 'Choose whether you want to make the task only for yourself, or for an entire group.';

$string['bookwithteacher_help'] = 'Choose a teacher for the task.';

$string['choosingmeetingstart_help'] = 'Change (or choose) the task start time. If this task collides with some other meetings, you\'ll be asked
if this meeting replaces all conflicting tasks. Note that the new meeting parameters will override all previous
settings.';

$string['exclusivity_help'] = '<p>You can set a limit on the number of students that can apply for a given meeting. </p>
<p>Setting a limit of 1 (default) will mean that the meeting is exclusive to a single student.</p>
<p>Setting a limit of, e.g., 3  will mean that up to three students can book into the meeting.</p>
<p>If disabled, any number of students can book the meeting; it will never be considered "full".</p>';

$string['location_help'] = 'Specify the scheduled location of the meeting.';

$string['notifications_help'] = 'When this option is enabled, teachers and students will receive notifications when tasks are applied for or cancelled.';

$string['staffrolename_help'] = '
The label for the role who attends students. This is not necessarily a "teacher".';

$string['guardtime_help'] = 'A guard time prevents students from changing their booking shortly before the task.
<p>If the guard time is enabled and set to, for example, 2 hours, then students will be unable to book a meeting that starts in less than 2 hours time from now,
and they will be unable to drop an task if it start in less than 2 hours.</p>';








/* ***********  E-mail templates from here on ************ */

$string['email_applied_subject'] = '{$a->course_short}: Meeting accepted';

$string['email_applied_html'] = '<p> The meeting on: <strong>{$a->date}</strong> at <strong>{$a->time} </strong>, location: <strong>{$a->location}</strong> <br/>
for the project: "<em><a href="{$a->mrproject_url}">{$a->module}</a></em>", </br>
<strong><span class="error">has been accepted</span></strong>, by: {$a->staffrole} <strong><a href="{$a->attendant_url}">{$a->attendant}</a></strong> (<u>team:</u> <em>{$a->team}</em>).</br></p>';


$string['email_applied_plain'] = 'The meeting on: {$a->date} at {$a->time}, location: {$a->location}
for the project: {$a->module}, 
has been accepted, by: {$a->staffrole} {$a->attendant} (team: {$a->team}).';




$string['email_cancelled_subject'] = '{$a->course_short}: Task cancelled or moved by a student';

$string['email_cancelled_plain'] = 'Your task on  {$a->date} at {$a->time},
with the student {$a->attendee} for course:

{$a->course_short} : {$a->course}

in the mrproject titled "{$a->module}" on the website : {$a->site}

has been cancelled or moved.';

$string['email_cancelled_html'] = '<p>Your task on <strong>{$a->date}</strong> at <strong>{$a->time}</strong>,<br/>
with the student <strong><a href="{$a->attendee_url}">{$a->attendee}</a></strong> for course :</p>

<p><strong>{$a->course_short} : <a href="{$a->course_url}">{$a->course}</a></strong></p>

<p>in the mrproject titled "<em><a href="{$a->mrproject_url}">{$a->module}</a></em>" on the website : <strong><a href="{$a->site_url}">{$a->site}</a></strong></p>

<p><strong><span class="error">has been cancelled or moved</span></strong>.</p>';



$string['email_reminder_subject'] = '{$a->course_short}: Task reminder';

$string['email_reminder_plain'] = 'You have a task that has not yet been submitted, 
defined during the meeting of {$a->date} from {$a->time} to {$a->endtime}
with {$a->staffrole} {$a->attendant}.';

$string['email_reminder_html'] = '<p>You have a task that has not yet been submitted,
defined during the meeting of <strong>{$a->date}</strong> from <strong>{$a->time}</strong> to <strong>{$a->endtime}</strong><br/>
with {$a->staffrole} <strong><a href="{$a->attendant_url}">{$a->attendant}</a></strong>.</p>';




$string['email_teachercancelled_subject'] = '{$a->course_short}: Meeting cancelled';

$string['email_teachercancelled_html'] = '<p> The meeting on: <strong>{$a->date}</strong> at <strong>{$a->time} </strong>, location: <strong>{$a->location}</strong> <br/>
for the project: "<em><a href="{$a->mrproject_url}">{$a->module}</a></em>", </br>
<strong><span class="error">has been cancelled</span></strong>, by: {$a->staffrole} <strong><a href="{$a->attendant_url}">{$a->attendant}</a></strong> (<u>team:</u> <em>{$a->team}</em>).</br></p>';


$string['email_teachercancelled_plain'] = 'The meeting on: {$a->date} at {$a->time}, location: {$a->location}
for the project: {$a->module}, 
has been cancelled, by: {$a->staffrole} {$a->attendant} (team: {$a->team}).';




$string['email_invite_subject'] = 'Invitation: {$a->module}';
$string['email_invite_html'] = '<p>Please choose a time meeting for an task at:</p> <p>{$a->mrproject_url}</p>';

$string['email_invitereminder_subject'] = 'Reminder: {$a->module}';
$string['email_invitereminder_html'] = '<p>This is just a reminder that you have not yet set up your task. Please choose a time meeting as soon as possible at:</p><p>{$a->mrproject_url}</p>';
