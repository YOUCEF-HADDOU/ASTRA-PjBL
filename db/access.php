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
 * mrproject module capability definition
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(


    /***********************************************Teacher side***********************************************************/
    

    //Teacher side: Controls whether a Teacher may create a new instance of the activity (Allows a teacher to add an Schedule activity to the course)
    'mod/mrproject:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',                   //Permission
        'contextlevel' => CONTEXT_COURSE,       //A Course context
        'archetypes' => array(                  //Allowed roles: This capability is allowed for the default roles of manager and editingteacher (using the permission 'CAP_ALLOW')
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),
    
    //Teacher side: Attend students.
    'mod/mrproject:attend' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,       //An activity module context
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    ),

    //Teacher side: Create and manage your own meetings and tasks. (This capability allows a user to see the teacher screen, to create meetings for himself (and offer them to students), as wel as to edit these meetings afterwards.)
    'mod/mrproject:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'student' => CAP_ALLOW
        )
    ),

    //Teacher side: Schedule tasks for other staff members. (This is valuable when a third-party staff member (e.g., an administrator) has to set up tasks for other teachers.)
    'mod/mrproject:canscheduletootherteachers' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    //Teacher side: Browse other teacher's tasks. (in the "All tasks" tab.)
    'mod/mrproject:canseeotherteachersbooking' => array(     
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    //Teacher side: Use the "overview screen" in order to see data from other mrprojects. (teachers are allowed to see data "outside the current activity"), for seeing other teachers' bookings
    'mod/mrproject:seeoverviewoutsideactivity' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    //isteacher ? Boolean: to manage tasks in the "All tasks" tab. (Manage all mrproject data, in particular meetings for other teachers.)
    'mod/mrproject:managealltasks' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),


     //Teacher side: allow users without the 'managealltasks' capability to edit the attended flag in all tasks of all teachers (in the task screen only.)
    'mod/mrproject:editallattended' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                    'editingteacher' => CAP_ALLOW,
                    'coursecreator' => CAP_ALLOW,
                    'manager' => CAP_ALLOW
            )
    ),

    //Teacher side: allow users without the 'managealltasks' capability to edit the grades (in the task screen only.)
    'mod/mrproject:editallgrades' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                    'teacher' => CAP_ALLOW,
                    'editingteacher' => CAP_ALLOW,
                    'coursecreator' => CAP_ALLOW,
                    'manager' => CAP_ALLOW
            )
    ),

    //Teacher side: allow users without the 'managealltasks' capability to edit the teacher notes (in the task screen only.)
    'mod/mrproject:editallnotes' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                    'teacher' => CAP_ALLOW,
                    'editingteacher' => CAP_ALLOW,
                    'coursecreator' => CAP_ALLOW,
                    'manager' => CAP_ALLOW
            )
    ),


    /***********************************************Student side*************************************************/
    

    //Student side: Book an task which has been offered by a teacher, using the student screen. 
    'mod/mrproject:appoint' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    //Student side: View tasks that are available for booking. (The capability does not allow students to actually book a meeting)
    'mod/mrproject:viewmeetings' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/mrproject:appoint'
    ),

    //Student side: Allows students to see meetings in the future even if they are already fully booked.
    'mod/mrproject:viewfullmeetings' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    //Student side & Teacher side: See which other students have booked a meeting. (This applies both to meetings which the current student has booked, and to meetings which are displayed to the student for booking.)
    'mod/mrproject:seeotherstudentsbooking' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    //Student side: See other meeting student's results (grade), in meetings which the current student has booked an task
    'mod/mrproject:seeotherstudentsresults' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),


    /************************************************************************************************************/


    //Teacher side & Student side
    'mod/mrproject:disengage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
    

    //is student
    'mod/mrproject:isstudent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ),


/********************************************************************************************/
   /* //manage groups in the course (to allow students to edit logo and group name)
    'moodle/course:managegroups' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),*/
/********************************************************************************************/

    
);


