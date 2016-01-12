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
 * @module     mod_lti/cartridge_registration_form
 * @class      cartridge_registration_form
 * @package    core
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/ajax', 'core/notification', 'mod_lti/tool_type', 'mod_lti/events', 'mod_lti/keys'],
        function($, ajax, notification, toolType, ltiEvents, KEYS) {

    var SELECTORS = {
        CARTRIDGE_URL: '#cartridge-url',
        CONSUMER_KEY: '#registration-key',
        SHARED_SECRET: '#registration-secret',
        REGISTRATION_FORM: '#cartridge-registration-form',
        REGISTRATION_SUBMIT_BUTTON: '#cartridge-registration-submit',
        REGISTRATION_CANCEL_BUTTON: '#cartridge-registration-cancel',
    };

    var getCartridgeURL = function() {
        return $(SELECTORS.REGISTRATION_FORM).attr('data-cartridge-url');
    };

    var getSubmitButton = function() {
        return $(SELECTORS.REGISTRATION_SUBMIT_BUTTON);
    };

    var getCancelButton = function() {
        return $(SELECTORS.REGISTRATION_CANCEL_BUTTON);
    };

    var getConsumerKey = function() {
        return $(SELECTORS.CONSUMER_KEY).val();
    };

    var getSharedSecret = function() {
        return $(SELECTORS.SHARED_SECRET).val();
    };

    var startLoading = function() {
        getSubmitButton().addClass('loading');
    };

    var stopLoading = function() {
        getSubmitButton().removeClass('loading');
    };

    var isLoading = function() {
        return getSubmitButton().hasClass('loading');
    };

    var submitCartridgeURL = function() {
        if (isLoading()) {
            return;
        }

        var url = getCartridgeURL();
        if (url == "") {
            return;
        }

        startLoading();
        var consumerKey = getConsumerKey();
        var sharedSecret = getSharedSecret();
        var promise = toolType.create({cartridgeurl: url, key: consumerKey, secret: sharedSecret});

        promise.done(function(result) {
            stopLoading();
            $(document).trigger(ltiEvents.NEW_TOOL_TYPE);
            $(document).trigger(ltiEvents.STOP_CARTRIDGE_REGISTRATION);
            // TODO: Language strings
            $(document).trigger(ltiEvents.REGISTRATION_FEEDBACK, {
                status: "Success",
                message: "Successfully created new tool!"
            });
        }).fail(function() { stopLoading() });
    };

    var registerEventListeners = function() {
        var submitButton = getSubmitButton();
        submitButton.click(function(e) {
            e.preventDefault();
            submitCartridgeURL();
        });
        submitButton.keypress(function(e) {
            if (!e.metaKey && !e.shiftKey && !e.altKey && !e.ctrlKey) {
                if (e.keyCode == KEYS.ENTER || e.keyCode == KEYS.SPACE) {
                    e.preventDefault();
                    submitCartridgeURL();
                }
            }
        });

        var cancelButton = getCancelButton();
        cancelButton.click(function(e) {
            e.preventDefault();
            $(document).trigger(ltiEvents.STOP_CARTRIDGE_REGISTRATION);
        });
        cancelButton.keypress(function(e) {
            if (!e.metaKey && !e.shiftKey && !e.altKey && !e.ctrlKey) {
                if (e.keyCode == KEYS.ENTER || e.keyCode == KEYS.SPACE) {
                    e.preventDefault();
                    $(document).trigger(ltiEvents.STOP_CARTRIDGE_REGISTRATION);
                }
            }
        });
    };

    return {
        init: function() {
            registerEventListeners();
        }
    };
});
