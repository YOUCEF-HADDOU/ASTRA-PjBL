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
 * Controller for mrproject module.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\permission;

defined('MOODLE_INTERNAL') || die();

/**
 * The base class for controllers.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mrproject_permissions extends permissions_manager {

    /**
     * mrproject_permissions constructor.
     *
     * @param \context $context
     * @param int $userid
     */
    public function __construct(\context $context, $userid) {
        parent::__construct('mod_mrproject', $context, $userid);
    }

    /**
     * teacher_can_see_meeting
     *
     * @param \mod_mrproject\model\meeting $meeting
     * @return bool
     */
    public function teacher_can_see_meeting(\mod_mrproject\model\meeting $meeting) {
        if ($this->has_any_capability(['managealltasks', 'canseeotherteachersbooking'])) {
            return true;
        } else if ($this->has_any_capability(['manage', 'attend'])) {
            return $this->userid == $meeting->teacherid;
        } else {
            return false;
        }
    }

    /**
     * can_edit_meeting
     *
     * @param \mod_mrproject\model\meeting $meeting
     * @return bool
     */
    public function can_edit_meeting(\mod_mrproject\model\meeting $meeting) {
        if ($this->has_capability('manage')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * can_edit_own_meetings
     *
     * @return bool
     */
    public function can_edit_own_meetings() {
        return $this->has_any_capability(['manage', 'managealltasks']);
    }

    /**
     * can_edit_all_meetings
     *
     * @return bool|mixed
     */
    public function can_edit_all_meetings() {
        return $this->has_capability('managealltasks');
    }

    /**
     * can_see_all_meetings
     *
     * @return bool
     */
    public function can_see_all_meetings() {
        return $this->has_any_capability(['managealltasks', 'canseeotherteachersbooking']);
    }

    /**
     * can_see_task
     *
     * @param \mod_mrproject\model\task $app
     * @return bool
     */
    public function can_see_task(\mod_mrproject\model\task $app) {
        if ($this->has_any_capability(['managealltasks', 'canseeotherteachersbooking'])) {
            return true;
        } else if ($this->has_capability('attend') && $this->userid == $app->get_meeting()->teacherid) {
            return true;
        } else if ($this->has_capability('appoint') && $this->userid == $app->studentid) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * can_edit_grade
     *
     * @param \mod_mrproject\model\task $app
     * @return bool
     */
    public function can_edit_grade(\mod_mrproject\model\task $app) {
        if ($this->has_any_capability(['managealltasks', 'editallgrades'])) {
            return true;
        } else {
            return $this->userid == $app->get_meeting()->teacherid;
        }
    }

    /**
     * can_edit_attended
     *
     * @param \mod_mrproject\model\task $app
     * @return bool
     */
    public function can_edit_attended(\mod_mrproject\model\task $app) {
        if ($this->has_any_capability(['managealltasks', 'editallattended'])) {
            return true;
        } else {
            return $this->userid == $app->get_meeting()->teacherid;
        }
    }

    /**
     * can_edit_notes
     *
     * @param \mod_mrproject\model\task $app
     * @return bool
     */
    public function can_edit_notes(\mod_mrproject\model\task $app) {
        if ($this->has_any_capability(['managealltasks', 'editallnotes'])) {
            return true;
        } else {
            return $this->userid == $app->get_meeting()->teacherid;
        }
    }

}
