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
 * Steps definitions related with the mrproject activity.
 *
 * @package    mod_mrproject
 * @category   test
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given, Behat\Behat\Context\Step\When as When, Behat\Gherkin\Node\TableNode as TableNode;
/**
 * mrproject-related steps definitions.
 *
 * @category test
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_mrproject extends behat_base {

    /**
     * Adds a series of meetings to the mrproject
     *
     * @Given /^I add a meeting (\d+) days ahead at (\d+) in "(?P<activityname_string>(?:[^"]|\\")*)" mrproject and I fill the form with:$/
     *
     * @param int $daysahead
     * @param int $time
     * @param string $activityname
     * @param TableNode $fielddata
     */
    public function i_add_a_meeting_days_ahead_at_in_mrproject_and_i_fill_the_form_with(
                              $daysahead, $time, $activityname, TableNode $fielddata) {

        $hours = floor($time / 100);
        $mins  = $time - 100 * $hours;
        $startdate = time() + $daysahead * DAYSECS;

        $this->execute('behat_general::click_link', $this->escape($activityname));
        $this->execute('behat_general::i_click_on', array('Add meetings', 'link'));
        $this->execute('behat_general::click_link', 'Add single meeting');

        $this->execute('behat_forms::i_expand_all_fieldsets');

        $rows = array();
        $rows[] = array('starttime[day]', date("j", $startdate));
        $rows[] = array('starttime[month]', date("F", $startdate));
        $rows[] = array('starttime[year]', date("Y", $startdate));
        $rows[] = array('starttime[hour]', $hours);
        $rows[] = array('starttime[minute]', $mins);
        $rows[] = array('duration', '45');
        foreach ($fielddata->getRows() as $row) {
            if ($row[0] == 'studentid[0]') {
                $this->execute('behat_forms::i_open_the_autocomplete_suggestions_list');
                $this->execute('behat_forms::i_click_on_item_in_the_autocomplete_list', $row[1]);
            } else {
                $rows[] = $row;
            }
        }
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode($rows));

        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));
    }


    /**
     * Adds a series of meetings to the mrproject
     *
     * @Given /^I add (\d+) meetings (\d+) days ahead in "(?P<activityname_string>(?:[^"]|\\")*)" mrproject and I fill the form with:$/
     *
     * @param int $meetingcount
     * @param int $daysahead
     * @param string $activityname
     * @param TableNode $fielddata
     */
    public function i_add_meetings_days_ahead_in_mrproject_and_i_fill_the_form_with(
                        $meetingcount, $daysahead, $activityname, TableNode $fielddata) {

        $startdate = time() + $daysahead * DAYSECS;

        $this->execute('behat_general::click_link', $this->escape($activityname));
        $this->execute('behat_general::i_click_on', array('Add meetings', 'link'));
        $this->execute('behat_general::click_link', 'Add repeated meetings');

        $rows = array();
        $rows[] = array('rangestart[day]', date("j", $startdate));
        $rows[] = array('rangestart[month]', date("F", $startdate));
        $rows[] = array('rangestart[year]', date("Y", $startdate));
        $rows[] = array('Saturday', '1');
        $rows[] = array('Sunday', '1');
        $rows[] = array('starthour', '1');
        $rows[] = array('endhour', $meetingcount + 1);
        $rows[] = array('duration', '45');
        $rows[] = array('break', '15');
        foreach ($fielddata->getRows() as $row) {
            $rows[] = $row;
        }

        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode($rows));

        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));

    }

    /**
     * Add the "upcoming events" block, globally on every page.
     *
     * This is useful as it provides an easy way of checking a user's calendar entries.
     *
     * @Given /^I add the upcoming events block globally$/
     */
    public function i_add_the_upcoming_events_block_globally() {

        $home = $this->escape(get_string('sitehome'));

        $this->execute('behat_data_generators::the_following_entities_exist', array('users',
                        new TableNode(array(
                            array('username', 'firstname', 'lastname', 'email'),
                            array('globalmanager1', 'GlobalManager', '1', 'globalmanager1@example.com')
                        )) ) );

        $this->execute('behat_data_generators::the_following_entities_exist', array('system role assigns',
                        new TableNode(array(
                            array('user', 'role'),
                            array('globalmanager1', 'manager')
                        )) ) );
        $this->execute('behat_auth::i_log_in_as', 'globalmanager1');
        $this->execute('behat_general::click_link', $home);
        $this->execute('behat_navigation::i_navigate_to_in_current_page_administration', array('Turn editing on'));
        $this->execute('behat_blocks::i_add_the_block', 'Upcoming events');

        $this->execute('behat_blocks::i_open_the_blocks_action_menu', 'Upcoming events');
        $this->execute('behat_general::click_link', 'Configure Upcoming events block');
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode(array(
                            array('Page contexts', 'Display throughout the entire site')
                        )) );
        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));
        $this->execute('behat_auth::i_log_out');

    }

    /**
     * Select item from the nth autocomplete list.
     *
     * @Given /^I click on "([^"]*)" item in autocomplete list number (\d+)$/
     *
     * @param string $item
     * @param int $listnumber
     */
    public function i_click_on_item_in_the_nth_autocomplete_list($item, $listnumber) {

        $downarrowtarget = "(//span[contains(@class,'form-autocomplete-downarrow')])[$listnumber]";
        $this->execute('behat_general::i_click_on', [$downarrowtarget, 'xpath_element']);

        $xpathtarget = "(//ul[@class='form-autocomplete-suggestions']//*[contains(concat('|', string(.), '|'),'|" . $item . "|')])[$listnumber]";

        $this->execute('behat_general::i_click_on', [$xpathtarget, 'xpath_element']);

        $this->execute('behat_general::i_press_key_in_element', ['13', 'body', 'xpath_element']);
    }
}
