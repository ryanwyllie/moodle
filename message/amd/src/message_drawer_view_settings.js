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
 * @module     core_message/message_drawer_view_overview
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/notification',
    'core_message/message_repository',
    'core/custom_interaction_events',
],
function(
    $,
    Notification,
    Repository,
    CustomEvents
) {

    var SELECTORS = {
        SETTINGS: '[data-region="settings"]',
        PREFERENCE_CONTROL: '[data-region="preference-control"]',
        CHECKBOX: 'input[type="checkbox"]',
        LOADING_PLACEHOLDER: '[data-region="loading-placeholder"]'
    };

    var PREFERENCES = {
        'message_blocknoncontacts': {
            type: 'blocknoncontacts',
            enabled: '1',
            disabled: '0',
        },
        'message_provider_moodle_instantmessage_loggedoff': {
            type: 'emailnotifications',
            enabled: 'email',
            disabled: 'none'
        },
        'message_provider_moodle_instantmessage_loggedin': {
            type: 'emailnotifications',
            enabled: 'email',
            disabled: 'none'
        }
    };

    /**
     * Get a preference element
     * @param  {Object} body The settings body element.
     * @param  {String} preferenceName Name of the preference.
     * @return {Object} The preference container element.
     */
    var getPreferenceElement = function(body, preferenceName) {
        return body.find('[data-preference="' + preferenceName + '"]');
    };

    /**
     * Check if a preference is enabled.
     * 
     * @param  {Object} body The settings body element.
     * @param  {String} preferenceName Name of the preference.
     * @return {Bool} Is preference enabled.
     */
    var isPreferenceElementEnabled = function(preferenceElement) {
        var checkbox = preferenceElement.find(SELECTORS.CHECKBOX);
        return checkbox.prop('checked');
    };

    /**
     * Set preference checked in UI.
     * 
     * @param {Object} preferenceElement The preference container element.
     * @param {Number} isEnabled 1 for enabled 0 for disabled.
     */
    var updatePreferenceElement = function(preferenceElement, isEnabled) {
        preferenceElement.find(SELECTORS.CHECKBOX).prop('checked', isEnabled);
    };

    /**
     * Load Preferences and check boxes for preferences already set.
     * 
     * @param {Object} body The settings body element.
     * @param {Number} loggedInUserId The logged in user id.
     */
    var loadPreferences = function(body, loggedInUserId) {
        var settingsContainer = body.find(SELECTORS.SETTINGS);
        var loadingPlaceholder = body.find(SELECTORS.LOADING_PLACEHOLDER);

        Repository.getPreferences(loggedInUserId)
            .then(function(result) {
                // The server returns an array of all preferences with a name and value. We treat
                // a few preferences as one so we need to group them by type and check each one is
                // enabled / disabled.
                // E.g.
                // Input:
                // [
                //   {name: "message_provider_moodle_instantmessage_loggedoff", value: "email"},
                //   {name: "message_provider_moodle_instantmessage_loggedin", value: "email"},
                //   {name: "message_blocknoncontacts", value: "0"}
                // ]
                //
                // Output:
                // {
                //      blocknoncontacts: [false],
                //      emailnotifications: [true, true]
                // }
                var preferencesByType = result.preferences.reduce(function(carry, preference) {
                    if (preference.name in PREFERENCES) {
                        var config = PREFERENCES[preference.name];
                        var value = preference.value;
                        var isEnabled = value === config.enabled;
                        var type = config.type;

                        if (type in carry) {
                            carry[type].push(isEnabled);
                        } else {
                            carry[type] = [isEnabled];
                        }
                    }

                    return carry;
                }, {});

                Object.keys(preferencesByType).forEach(function(type) {
                    // Only consider this type enabled it all associated preferences are enabled.
                    var isEnabled = preferencesByType[type].every(function(enabled) {
                        return enabled;
                    });
                    var preferenceElement = getPreferenceElement(body, type);
                    updatePreferenceElement(preferenceElement, isEnabled);
                });
            })
            .then(function() {
                settingsContainer.removeClass('hidden');
                loadingPlaceholder.addClass('hidden');
            })
            .catch(Notification.exception);
    }
    /**
     * Create all of the event listeners for the message preferences page.
     *
     * @method registerEventListeners
     * @param {Object} body The settings body element.
     * @param {Number} loggedInUserId The logged in user id.
     */
    var registerEventListeners = function(body, loggedInUserId) {

        var settingsContainer = body.find(SELECTORS.SETTINGS);

        CustomEvents.define(settingsContainer, [
            CustomEvents.events.activate
        ]);

        settingsContainer.on(CustomEvents.events.activate, SELECTORS.CHECKBOX, function(e) {
                var setting = $(e.target).closest(SELECTORS.PREFERENCE_CONTROL);
                var type = setting.attr('data-preference');
                var element = getPreferenceElement(body, type);
                var isEnabled = isPreferenceElementEnabled(element);
                var preferences = Object.keys(PREFERENCES).reduce(function(carry, preference) {
                    var config = PREFERENCES[preference];

                    if (config.type === type) {
                        carry.push({
                            type: preference,
                            value: isEnabled ? config.enabled : config.disabled
                        });
                    }

                    return carry;
                }, []);

                Repository.savePreferences(loggedInUserId, preferences)
                    .catch(Notification.exception);
            }
        );
    };

    /**
     * Initialise the settings page by adding event listeners to
     * the checkboxes.
     *
     * @param {Object} header The settings header element.
     * @param {Object} body The settings body element.
     * @param {Number} loggedInUserId The logged in user id.
     */
    var show = function(header, body, loggedInUserId) {
        if (!body.attr('data-init')) {
            registerEventListeners(body, loggedInUserId);
            loadPreferences(body, loggedInUserId);
            body.attr('data-init', true);
        }
    };

    return {
        show: show,
    };
});