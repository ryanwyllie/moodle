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
],
function(
    $,
    Repository,
    CustomEvents
) {

    var SELECTORS = {
        SETTINGS: '[data-region="settings"]',
        PREFERENCE_CONTROL: '[data-region="preference-control"]',
        CHECKBOX: '[data-region="checkbox"]',
        LOADINGICON: '.loading-icon'
    };

    var PREFERENCES_ON = {
        'blocknoncontacts': [
                    {
                        type: 'message_blocknoncontacts',
                        value: "1",
                    }],

        'emailnotifications': [
                    {
                        type: 'message_provider_moodle_instantmessage_loggedoff',
                        value: 'email'
                    },
                    {
                        type: 'message_provider_moodle_instantmessage_loggedin',
                        value: 'email'
                    }]
        };

    var PREFERENCES_OFF = {
        'blocknoncontacts': [
                    {
                        type: 'message_blocknoncontacts',
                        value: "0",
                    }],

        'emailnotifications': [
                    {
                        type: 'message_provider_moodle_instantmessage_loggedoff',
                        value: 'none'
                    },
                    {
                        type: 'message_provider_moodle_instantmessage_loggedin',
                        value: 'none'
                    }]
        };

    /**
     * Load Preferences and check boxes for preferences already set.
     *
     */
    var loadPreferences = function(root, loggedInUserid) {
        var SettingsContainer = root.find(SELECTORS.SETTINGS);

        var storedPreferences = Repository.getPreferences(loggedInUserid)
            .then(function(allpreferences) {

                SettingsContainer.find(SELECTORS.PREFERENCE_CONTROL)
                    .each(function(index, setting) {
                        var setting = $(setting);
                        var checkbox = setting.find(SELECTORS.CHECKBOX);
                        var preference = setting.attr('data-preference');

                        if (preference in PREFERENCES_ON) {
                            checkpreferences = PREFERENCES_ON[preference];
                            var found = 0;
                            checkpreferences.forEach(function(checkpreference) {
                                setpreference = allpreferences.preferences.find(function(pref) {
                                    if (pref.name === checkpreference.type &&
                                        pref.value === checkpreference.value ) {
                                        return true;
                                    }
                                })
                                if (setpreference) {
                                    found++;
                                }
                            });
                            if (checkpreferences.length == found) {
                                checkbox.prop('checked', true);
                            }
                        }
                    });
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
            SELECTORS.PREFERENCE_CONTROL,
            function(e) {
                var setting = $(e.target).closest(SELECTORS.PREFERENCE_CONTROL);
                var loadingicon = setting.find(SELECTORS.LOADINGICON);
                var checkbox = setting.find(SELECTORS.CHECKBOX);

                var preference = setting.attr('data-preference');
                var ischecked = checkbox.prop('checked');

                var preferences = ischecked ? PREFERENCES_ON[preference] : PREFERENCES_OFF[preference];

                Repository.savePreferences(loggedInUserid, preferences)
                    .then(function() {
                        setting.closest(SELECTORS.LOADINGICON).toggleClass('hidden');
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