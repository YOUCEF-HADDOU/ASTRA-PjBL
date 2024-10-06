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
 * Base class for dependency-based events.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_mrproject abstract base event class for dependency-based events.
 *
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class dependency_base extends \core\event\base {


    /**
     * @var \mod_mrproject\model\dependency the dependency associated with this event
     */
    protected $dependency;

    /**
     * Return the base data fields for an dependency
     *
     * @param \mod_mrproject\model\dependency $dependency the tdependencyask in question
     * @return array
     */
    protected static function base_data(\mod_mrproject\model\dependency $dependency) {
        return array(
            'context' => $dependency->get_mrproject()->get_context(),
            'objectid' => $dependency->id,
            //'relateduserid' => $dependency->studentid,
            'other' => $dependency->taskid
        );
    }

    /**
     * Set data of the event from an dependency record.
     *
     * @param \mod_mrproject\model\dependency $dependency
     */
    protected function set_dependency(\mod_mrproject\model\dependency $dependency) {
        //$this->add_record_snapshot('mrproject_dependency', $dependency->data);
        $this->add_record_snapshot('mrproject_meeting', $dependency->get_meeting()->data);
        $this->add_record_snapshot('mrproject', $dependency->get_mrproject()->data);
        $this->dependency = $dependency;
        $this->data['objecttable'] = 'mrproject_dependency';
    }

    /**
     * Get dependency object.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \mod_mrproject\model\dependency
     */
    public function get_dependency() {   
        if ($this->is_restored()) {
            throw new \coding_exception('get_dependency() is intended for event observers only');
        }
        return $this->dependency;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/mrproject/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
