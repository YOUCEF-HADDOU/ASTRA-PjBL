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
 * Process ajax requests
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

use \mod_mrproject\model\mrproject;
use \mod_mrproject\permission\mrproject_permissions;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$cm = get_coursemodule_from_id('mrproject', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$mrproject = mrproject::load_by_coursemodule_id($id);

require_login($course, true, $cm);
require_sesskey();

$permissions = new mrproject_permissions($mrproject->context, $USER->id);

$return = 'OK';

switch ($action) {
    case 'saveseen':

        $appid = required_param('taskid', PARAM_INT);
        list($meeting, $app) = $mrproject->get_meeting_task($appid);
        $newseen = required_param('seen', PARAM_BOOL);

        //$permissions->ensure($permissions->can_edit_attended($app));

        $app->attended = $newseen;
        $meeting->save();

        break;
}

echo json_encode($return);
die;
