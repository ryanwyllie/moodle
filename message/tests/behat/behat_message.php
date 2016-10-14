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
 * Behat message-related steps definitions.
 *
 * @package    core_message
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

/**
 * Messaging system steps definitions.
 *
 * @package    core_message
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_message extends behat_base {

    /**
     * View the contact information of a user in the messages ui.
     *
     * @Given /^I view the "(?P<user_full_name_string>(?:[^"]|\\")*)" contact in messages$/
     * @param string $userfullname
     */
    public function i_view_contact_in_messages($userfullname) {
        // Visit home page and follow messages.
        $this->execute("behat_general::i_am_on_homepage");

        $this->execute("behat_navigation::i_follow_in_the_user_menu", get_string('messages', 'message'));

        $this->i_view_my_contacts_in_messages();

        $this->execute('behat_general::wait_until_the_page_is_ready');

        $this->execute('behat_forms::i_set_the_field_to',
            array(get_string('searchforuserorcourse', 'message'), $this->escape($userfullname))
        );

        $this->execute('behat_general::wait_until_the_page_is_ready');

        // Need to limit the click to the search results because the 'view-contact-profile' elements
        // can occur in two separate divs on the page.
        $this->execute('behat_general::i_click_on_in_the',
            array(
                "//div[@data-action='view-contact-profile']
                [./div[contains(normalize-space(.), '" . $this->escape($userfullname) . "')]]",
                'xpath_element',
                "[data-region='messaging-area'] [data-region='search-results-area']",
                "css_element",
            )
        );

        $this->execute('behat_general::wait_until_the_page_is_ready');
    }

    /**
     * Sends a message to the specified user from the logged user. The user full name should contain the first and last names.
     *
     * @Given /^I send "(?P<message_contents_string>(?:[^"]|\\")*)" message to "(?P<user_full_name_string>(?:[^"]|\\")*)" user$/
     * @param string $messagecontent
     * @param string $userfullname
     */
    public function i_send_message_to_user($messagecontent, $userfullname) {
        $this->i_view_contact_in_messages($userfullname);

        $this->execute("behat_general::i_click_on", array("[data-action='profile-send-message']", 'css_element'));

        $this->execute('behat_general::wait_until_the_page_is_ready');

        $this->execute('behat_forms::i_set_the_field_with_xpath_to',
            array("//textarea[@data-region='send-message-txt']", $this->escape($messagecontent))
        );

        $this->execute("behat_forms::press_button", get_string('send', 'message'));
    }

    /**
     * Confirm a message is visible in the messaging ui.
     *
     * @Given /^I should see "(?P<message_contents_string>(?:[^"]|\\")*)" message in messages$/
     * @param string $messagecontent
     */
    public function i_should_see_message_in_messages($messagecontent) {
        $this->i_view_my_messages_in_messages();

        $this->execute("behat_general::assert_element_contains_text",
            array(
                $this->escape($messagecontent),
                "[data-region='messaging-area'] [data-region='messages']",
                "css_element",
            )
        );
    }

    /**
     * Confirm a message is not visible in the messaging ui.
     *
     * @Given /^I should not see "(?P<message_contents_string>(?:[^"]|\\")*)" message in messages$/
     * @param string $messagecontent
     */
    public function i_should_not_see_message_in_messages($messagecontent) {
        $this->i_view_my_messages_in_messages();

        $this->execute("behat_general::assert_element_not_contains_text",
            array(
                $this->escape($messagecontent),
                "[data-region='messaging-area'] [data-region='messages']",
                "css_element",
            )
        );
    }

    /**
     * Send a message in the messaging ui.
     *
     * @Given /^I select messages from "(?P<user_full_name_string>(?:[^"]|\\")*)" user in messages$/
     * @param string $userfullname
     */
    public function i_select_messages_from_user_in_messages($userfullname) {
        $this->i_view_my_messages_in_messages();

        $this->execute("behat_general::i_click_on_in_the",
            array(
                $this->escape($userfullname),
                "text",
                "[data-region='messaging-area'] [data-region-content='conversations']",
                "css_element",
            )
        );
    }

    /**
     * Confirm you have messages from the user.
     *
     * @Given /^I should see messages from "(?P<user_full_name_string>(?:[^"]|\\")*)" user in messages$/
     * @param string $userfullname
     */
    public function i_should_see_messages_from_user_in_messages($userfullname) {
        $this->i_view_my_messages_in_messages();

        $this->execute("behat_general::assert_element_contains_text",
            array(
                $this->escape($userfullname),
                "[data-region='messaging-area'] [data-region-content='conversations']",
                "css_element",
            )
        );
    }

    /**
     * Confirm you don't have messages from the user.
     *
     * @Given /^I should not see messages from "(?P<user_full_name_string>(?:[^"]|\\")*)" user in messages$/
     * @param string $userfullname
     */
    public function i_should_not_see_messages_from_user_in_messages($userfullname) {
        $this->i_view_my_messages_in_messages();

        $this->execute("behat_general::assert_element_not_contains_text",
            array(
                $this->escape($userfullname),
                "[data-region='messaging-area'] [data-region-content='conversations']",
                "css_element",
            )
        );
    }

    /**
     * Confirm the given user is a contact.
     *
     * @Given /^I should see "(?P<user_full_name_string>(?:[^"]|\\")*)" user in contacts within messages$/
     * @param string $userfullname
     */
    public function i_should_see_user_in_contacts_within_messages($userfullname) {
        $this->i_view_my_contacts_in_messages();

        $this->execute("behat_general::assert_element_contains_text",
            array(
                $this->escape($userfullname),
                "[data-region='messaging-area'] [data-region-content='contacts']",
                "css_element",
            )
        );
    }

    /**
     * Confirm the given user is not a contact.
     *
     * @Given /^I should not see "(?P<user_full_name_string>(?:[^"]|\\")*)" user in contacts within messages$/
     * @param string $userfullname
     */
    public function i_should_not_see_user_in_contacts_within_messages($userfullname) {
        $this->i_view_my_contacts_in_messages();

        $this->execute("behat_general::assert_element_not_contains_text",
            array(
                $this->escape($userfullname),
                "[data-region='messaging-area'] [data-region-content='contacts']",
                "css_element",
            )
        );
    }

    /**
     * Select messages from a user in the messaging ui.
     *
     * @Given /^I send "(?P<message_contents_string>(?:[^"]|\\")*)" message in messages$/
     * @param string $messagecontent
     */
    public function i_send_message_in_messages($messagecontent) {
        $this->execute('behat_forms::i_set_the_field_with_xpath_to',
            array("//textarea[@data-region='send-message-txt']", $this->escape($messagecontent))
        );

        $this->execute("behat_forms::press_button", get_string('send', 'message'));
    }

    /**
     * Blocks a user from messaging another user.
     *
     * @Given /^I block "(?P<user_full_name_string>(?:[^"]|\\")*)" user$/
     * @param string $userfullname
     */
    public function i_block_user($userfullname) {
        $this->i_view_contact_in_messages($userfullname);

        $this->execute("behat_general::i_click_on", array("[data-action='profile-block-contact']", 'css_element'));
    }

    /**
     * Turn on editing mode in the messages ui.
     *
     * @Given /^I turn on editing in messages$/
     */
    public function i_turn_on_editing_in_messages() {
        $this->execute("behat_general::i_click_on",
            array("[data-region='messaging-area'] [data-action='start-delete-messages']", 'css_element'));
    }

    /**
     * Send a message in the messaging ui.
     *
     * @Given /^I click on "(?P<message_contents_string>(?:[^"]|\\")*)" message in messages$/
     * @param string $messagecontent
     */
    public function i_click_on_message_in_messages($messagecontent) {
        $this->execute("behat_general::i_click_on_in_the",
            array(
                $this->escape($messagecontent),
                "text",
                "[data-region='messaging-area'] [data-region='messages']",
                "css_element",
            )
        );
    }

    /**
     * Search for a message in the messaging ui.
     *
     * @Given /^I search for "(?P<message_contents_string>(?:[^"]|\\")*)" message in messages$/
     * @param string $messagecontent
     */
    public function i_search_for_message_in_messages($messagecontent) {
        // Make sure we're on the messages tab.
        $this->i_view_my_messages_in_messages();

        $this->execute('behat_forms::i_set_the_field_to',
            array(get_string('searchmessages', 'message'), $this->escape($messagecontent))
        );

        $this->execute('behat_general::wait_until_the_page_is_ready');
    }

    /**
     * Search for a user in the messaging ui.
     *
     * @Given /^I search for "(?P<user_full_name_string>(?:[^"]|\\")*)" user in messages$/
     * @param string $userfullname
     */
    public function i_search_for_user_in_messages($userfullname) {
        // Make sure we're on the contacts tab.
        $this->i_view_my_contacts_in_messages();

        $this->execute('behat_forms::i_set_the_field_to',
            array(get_string('searchforuserorcourse', 'message'), $this->escape($userfullname))
        );

        $this->execute('behat_general::wait_until_the_page_is_ready');
    }

    /**
     * Check for the given user in the search results.
     *
     * @Given /^I should see "(?P<user_full_name_string>(?:[^"]|\\")*)" user in search results within messages$/
     * @param string $userfullname
     */
    public function i_should_see_user_in_search_results_within_messages($userfullname) {
        $this->execute("behat_general::assert_element_contains_text",
            array(
                $this->escape($userfullname),
                "[data-region='messaging-area'] [data-region='search-results-area']",
                "css_element",
            )
        );
    }

    /**
     * Confirm there are not search results.
     *
     * @Given /^I should not see any search results within messages$/
     */
    public function i_should_not_see_any_search_results_within_messages() {
        $node = $this->get_selected_node('css_element',
            "[data-region='messaging-area'] [data-region='search-results-area'] .noresults");

        $this->ensure_node_is_visible($node);
    }

    /**
     * Confirm the given user is not in the search results.
     *
     * @Given /^I should not see "(?P<user_full_name_string>(?:[^"]|\\")*)" user in search results within messages$/
     * @param string $userfullname
     */
    public function i_should_not_see_user_in_search_results_within_messages($userfullname) {
        $this->execute("behat_general::assert_element_not_contains_text",
            array(
                $this->escape($userfullname),
                "[data-region='messaging-area'] [data-region='search-results-area']",
                "css_element",
            )
        );
    }

    /**
     * View the contacts tab in the messages ui.
     *
     * @Given /^I view my contacts in messages$/
     */
    public function i_view_my_contacts_in_messages() {
        $this->execute('behat_general::i_click_on',
            array("[data-region='messaging-area'] [data-action='contacts-view']", 'css_element'));
    }

    /**
     * View the messages tab in the messages ui.
     *
     * @Given /^I view my messages in messages$/
     */
    public function i_view_my_messages_in_messages() {
        $this->execute('behat_general::i_click_on',
            array("[data-region='messaging-area'] [data-action='conversations-view']", 'css_element'));
    }
}
