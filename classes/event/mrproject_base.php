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
 * Defines a base class for mrproject events.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mrproject\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_mrproject abstract base event class.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mrproject_base extends \core\event\base {

    /**
     * @var \mod_mrproject\model\mrproject the mrproject associated with this event
     */
    protected $mrproject;

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    /**
     * Retrieve base data for this event from a mrproject.
     *
     * @param \mod_mrproject\model\mrproject $mrproject
     * @return array
     */
    protected static function base_data(\mod_mrproject\model\mrproject $mrproject) {
        return array(
            'context' => $mrproject->get_context(),
            'objectid' => $mrproject->id
        );
    }

    /**
     * Set the mrproject associated with this event.
     *
     * @param \mod_mrproject\model\mrproject $mrproject
     */
    protected function set_mrproject(\mod_mrproject\model\mrproject $mrproject) {
        $this->add_record_snapshot('mrproject', $mrproject->data);
        $this->mrproject = $mrproject;
        $this->data['objecttable'] = 'mrproject';
    }

    /**
     * Get mrproject instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \mod_mrproject\model\mrproject
     */
    public function get_mrproject() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_mrproject() is intended for event observers only');
        }
        if (!isset($this->mrproject)) {
            debugging('mrproject property should be initialised in each event', DEBUG_DEVELOPER);
            global $CFG;
            require_once($CFG->dirroot . '/mod/mrproject/locallib.php');
            $this->mrproject = \mod_mrproject\model\mrproject::load_by_coursemodule_id($this->contextinstanceid);
        }
        return $this->mrproject;
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
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'mrproject';
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
