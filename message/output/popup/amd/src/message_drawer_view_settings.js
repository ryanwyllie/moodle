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
 * Controls the message drawer.
 *
 * @module     message_popup/message_drawer_view_overview
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core_message/message_repository',
    'core/custom_interaction_events',
    'core/log'
],
function(
    $,
    Repository,
    CustomEvents,
    Log
) {

    var SELECTORS = {
        SETTINGS: '[data-region="settings"]',
        PREFERENCE_CONTROL: '[data-region="preference-control"]',
        PREFERENCE: '[data-preference]',
        BLOCK_NON_CONTACTS: '[data-block-non-contacts]',
        LOADINGICON: '.loading-icon'
    };

    var loadPreferences = function(root) {
        var SettingsContainer = root.find(SELECTORS.SETTINGS);

        SettingsContainer.find(SELECTORS.PREFERENCE_CONTROL)
            .each(function(e) {
                var preference = $(this).find(SELECTORS.PREFERENCE)
                    .attr('data-preference');
            });
    }
    /**
     * Create all of the event listeners for the message preferences page.
     *
     * @method registerEventListeners
     */
    var registerEventListeners = function(root, loggedInUserid) {

        var SettingsContainer = root.find(SELECTORS.SETTINGS);

        CustomEvents.define(SettingsContainer, [
            CustomEvents.events.activate
        ]);

        SettingsContainer.on(CustomEvents.events.activate,
            SELECTORS.PREFERENCE,
            function(e, data) {
                var setting = $(e.target);
                setting.parent(SELECTORS.PREFERENCE_CONTROL)
                    .find(SELECTORS.LOADINGICON).toggleClass('hidden');

                var preference = setting.attr('data-preference');
                var ischecked = setting.prop('checked');
                var value = ischecked ? 1 : 0;
                Repository.savePreference(loggedInUserid, preference, value)
                    .then(function() {
                        setting.parent(SELECTORS.PREFERENCE_CONTROL)
                            .find(SELECTORS.LOADINGICON).toggleClass('hidden');
                    });
            }
        );
    };

    /**
     * Initialise the settings page by adding event listeners to
     * the checkboxes.
     * 
     * @param {object} root The root element for the settings page
     */
    var show = function(root, loggedInUserid) {
        root = $(root);

        loadPreferences(root, loggedInUserid);
        
        registerEventListeners(root, loggedInUserid);
    };

    return {
        show: show,
    };
});