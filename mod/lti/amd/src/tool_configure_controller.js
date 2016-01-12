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
 * Standard Ajax wrapper for Moodle. It calls the central Ajax script,
 * which can call any existing webservice using the current session.
 * In addition, it can batch multiple requests and return multiple responses.
 *
 * @module     mod_lti/tool_configure_controller
 * @class      tool_configure_controller
 * @package    core
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/templates', 'mod_lti/events', 'mod_lti/keys', 'mod_lti/tool_type'],
        function($, ajax, notification, templates, ltiEvents, KEYS, toolType) {

    var SELECTORS = {
        REGISTRATION_FEEDBACK_CONTAINER: '#registration-feedback-container',
        EXTERNAL_REGISTRATION_CONTAINER: '#external-registration-container',
        CARTRIDGE_REGISTRATION_CONTAINER: '#cartridge-registration-container',
        CARTRIDGE_REGISTRATION_FORM: '#cartridge-registration-form',
        TOOL_LIST_CONTAINER: '#tool-list-container',
        TOOL_CREATE_BUTTON: '#tool-create-button',
        REGISTRATION_CHOICE_CONTAINER: '#registration-choice-container',
        TOOL_URL: '#tool-url'
    };

    var getToolCreateButton = function() {
        return $(SELECTORS.TOOL_CREATE_BUTTON);
    };

    var getRegistrationFeedbackContainer = function() {
        return $(SELECTORS.REGISTRATION_FEEDBACK_CONTAINER);
    };

    var getToolListContainer = function() {
        return $(SELECTORS.TOOL_LIST_CONTAINER);
    };

    var getExternalRegistrationContainer = function() {
        return $(SELECTORS.EXTERNAL_REGISTRATION_CONTAINER);
    };

    var getCartridgeRegistrationContainer = function() {
        return $(SELECTORS.CARTRIDGE_REGISTRATION_CONTAINER);
    };

    var getRegistrationChoiceContainer = function() {
        return $(SELECTORS.REGISTRATION_CHOICE_CONTAINER);
    };

    var getURL = function() {
        return $(SELECTORS.TOOL_URL).val();
    };

    var hideExternalRegistration = function() {
        getExternalRegistrationContainer().addClass('hidden');
    };

    var hideCartridgeRegistration = function() {
        getCartridgeRegistrationContainer().addClass('hidden');
    };

    var hideRegistrationChoices = function() {
        getRegistrationChoiceContainer().addClass('hidden');
    };

    var showExternalRegistration = function() {
        hideCartridgeRegistration();
        hideRegistrationChoices();
        getExternalRegistrationContainer().removeClass('hidden');
    };

    var showCartridgeRegistration = function(url) {
        hideExternalRegistration();
        hideRegistrationChoices();
        getCartridgeRegistrationContainer().removeClass('hidden');
        getCartridgeRegistrationContainer().find(SELECTORS.CARTRIDGE_REGISTRATION_FORM).attr('data-cartridge-url', url);
    };

    var showRegistrationChoices = function() {
        if (isRegistrationFeedbackVisible()) {
            // If the registration feedback is visible then we don't need
            // to do anything because it will display this content when it's
            // closed.
            return;
        }

        hideExternalRegistration();
        hideCartridgeRegistration();
        getRegistrationChoiceContainer().removeClass('hidden');
    };

    var hideToolList = function() {
        getToolListContainer().addClass('hidden');
    };

    var showToolList = function() {
        getToolListContainer().removeClass('hidden');
    };

    var getToolTypes = function() {
        var request = {
            methodname: 'mod_lti_get_tool_types',
            args: {}
        };

        var promise = ajax.call([request])[0];

        promise.fail(notification.exception);

        return promise;
    };

    var isRegistrationFeedbackVisible = function() {
        return getRegistrationFeedbackContainer().hasClass('hidden') ? false : true;
    };

    var showRegistrationFeedback = function(data) {
        hideExternalRegistration();
        hideCartridgeRegistration();
        hideRegistrationChoices();

        templates.render('mod_lti/registration_feedback', data).done(function(html) {
            var container = getRegistrationFeedbackContainer();
            container.append(html);
            container.removeClass('hidden');

            setTimeout(function() {
                clearRegistrationFeedback();
            }, 5000);
        });
    };

    var clearRegistrationFeedback = function() {
        var container = getRegistrationFeedbackContainer();
        container.empty();
        container.addClass('hidden');

        showRegistrationChoices();
    };

    var reloadToolList = function() {
        var container = getToolListContainer();
        container.addClass('loading');

        getToolTypes().done(function(types) {
            templates.render('mod_lti/tool_list', {tools: types}).done(function(html, js) {
                container.empty();
                container.append(html);
                container.removeClass('loading');
                templates.runTemplateJS(js);
            });
        });
    };

    var registerEventListeners = function() {

        $(document).on(ltiEvents.NEW_TOOL_TYPE, function() {
            reloadToolList();
        });

        $(document).on(ltiEvents.START_EXTERNAL_REGISTRATION, function() {
            hideToolList();
        });

        $(document).on(ltiEvents.STOP_EXTERNAL_REGISTRATION, function() {
            showToolList();
            showRegistrationChoices();
        });

        $(document).on(ltiEvents.START_CARTRIDGE_REGISTRATION, function(event, url) {
            showCartridgeRegistration(url);
        });

        $(document).on(ltiEvents.STOP_CARTRIDGE_REGISTRATION, function() {
            getCartridgeRegistrationContainer().find(SELECTORS.CARTRIDGE_REGISTRATION_FORM).removeAttr('data-cartridge-url');
            showRegistrationChoices();
        });

        $(document).on(ltiEvents.REGISTRATION_FEEDBACK, function(event, data) {
            showRegistrationFeedback(data);
        });

        var toolButton = getToolCreateButton();
        toolButton.click(function(e) {
            e.preventDefault();
            toolButton.addClass("loading"); // TODO: Function for this.
            var url = getURL();
            if (url == "") {
                return;
            }
            var promise = toolType.isCartridge(url);

            promise.done(function(result) {
                if (result.iscartridge) {
                    $(document).trigger(ltiEvents.START_CARTRIDGE_REGISTRATION, url);
                    toolButton.removeClass("loading"); // TODO: Function for this.
                    $(SELECTORS.TOOL_URL).val('');
                }
            }).fail(function() { stopLoading() });

        });
        toolButton.keypress(function(e) {
            if (!e.metaKey && !e.shiftKey && !e.altKey && !e.ctrlKey) {
                if (e.keyCode == KEYS.ENTER || e.keyCode == KEYS.SPACE) {
                    e.preventDefault();
                    showExternalRegistration();
                }
            }
        });

        var feedbackContainer = getRegistrationFeedbackContainer();
        feedbackContainer.click(function(e) {
            e.preventDefault();
            clearRegistrationFeedback();
        });
        feedbackContainer.keypress(function(e) {
            if (!e.metaKey && !e.shiftKey && !e.altKey && !e.ctrlKey) {
                if (e.keyCode == KEYS.ENTER || e.keyCode == KEYS.SPACE) {
                    e.preventDefault();
                    clearRegistrationFeedback();
                }
            }
        });
    };

    return {
        init: function() {
            registerEventListeners();
            reloadToolList();
        }
    };
});
