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
 * @module     mod_lti/external_registration
 * @class      external_registration
 * @package    core
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/templates', 'mod_lti/events',
        'mod_lti/tool_proxy', 'mod_lti/tool_type', 'mod_lti/keys'],
        function($, ajax, notification, templates, ltiEvents, toolProxy, toolType, KEYS) {

    var SELECTORS = {
        REGISTRATION_FORM_CONTAINER: '#external-registration-form-container',
        REGISTRATION_URL: '#external-registration-url',
        REGISTRATION_SUBMIT_BUTTON: '#external-registration-submit',
        REGISTRATION_CANCEL_BUTTON: '#external-registration-cancel',
        EXTERNAL_REGISTRATION_CONTAINER: '#external-registration-page-container',
        EXTERNAL_REGISTRATION_TEMPLATE_CONTAINER: '#external-registration-template-container',
        EXTERNAL_REGISTRATION_CANCEL_BUTTON: '#cancel-external-registration',
        TOOL_TYPE_CAPABILITIES_CONTAINER: '#tool-type-capabilities-container',
        TOOL_TYPE_CAPABILITIES_TEMPLATE_CONTAINER: '#tool-type-capabilities-template-container',
        CAPABILITIES_AGREE_CONTAINER: '.capabilities-container',
    };

    var getRegistrationURL = function() {
        return $(SELECTORS.EXTERNAL_REGISTRATION_CONTAINER).attr('data-registration-url');
    };

    var getRegistrationSubmitButton = function() {
        return $(SELECTORS.REGISTRATION_SUBMIT_BUTTON);
    };

    var getRegistrationCancelButton = function() {
        return $(SELECTORS.REGISTRATION_CANCEL_BUTTON);
    };

    var getExternalRegistrationCancelButton = function() {
        return $(SELECTORS.EXTERNAL_REGISTRATION_CANCEL_BUTTON);
    };

    var getRegistrationFormContainer = function() {
        return $(SELECTORS.REGISTRATION_FORM_CONTAINER);
    };

    var getExternalRegistrationContainer = function() {
        return $(SELECTORS.EXTERNAL_REGISTRATION_CONTAINER);
    };

    var getExternalRegistrationTemplateContainer = function() {
        return $(SELECTORS.EXTERNAL_REGISTRATION_TEMPLATE_CONTAINER);
    };

    var getToolTypeCapabilitiesContainer = function() {
        return $(SELECTORS.TOOL_TYPE_CAPABILITIES_CONTAINER);
    };

    var getToolTypeCapabilitiesTemplateContainer = function() {
        return $(SELECTORS.TOOL_TYPE_CAPABILITIES_TEMPLATE_CONTAINER);
    };

    var startLoadingCapabilitiesContainer = function() {
        getToolTypeCapabilitiesContainer().addClass('loading');
    };

    var stopLoadingCapabilitiesContainer = function() {
        getToolTypeCapabilitiesContainer().removeClass('loading');
    };

    var startLoadingCancel = function() {
        getExternalRegistrationCancelButton().addClass('loading');
    };

    var stopLoadingCancel = function() {
        getExternalRegistrationCancelButton().removeClass('loading');
    };

    var startLoading = function() {
        getRegistrationSubmitButton().addClass('loading');
    };

    var stopLoading = function() {
        getRegistrationSubmitButton().removeClass('loading');
    };

    var isLoading = function() {
        return getRegistrationSubmitButton().hasClass('loading');
    };

    var hideToolTypeCapabilitiesContainer = function() {
        getToolTypeCapabilitiesContainer().addClass('hidden');
    };

    var showToolTypeCapabilitiesContainer = function() {
        getToolTypeCapabilitiesContainer().removeClass('hidden');
    };

    var hideExternalRegistrationContent = function() {
        getExternalRegistrationContainer().addClass('hidden');
    };

    var showExternalRegistrationContent = function() {
        getExternalRegistrationContainer().removeClass('hidden');
    };

    var hideRegistrationForm = function() {
        getRegistrationFormContainer().addClass('hidden');
    };

    var showRegistrationForm = function() {
        getRegistrationFormContainer().removeClass('hidden');
    };

    var setToolProxyId = function(id) {
        var button = getExternalRegistrationCancelButton();
        button.attr('data-tool-proxy-id', id);
    };

    var getToolProxyId = function() {
        var button = getExternalRegistrationCancelButton();
        return button.attr('data-tool-proxy-id');
    };

    var clearToolProxyId = function() {
        var button = getExternalRegistrationCancelButton();
        button.removeAttr('data-tool-proxy-id');
    };

    var hasCreatedToolProxy = function() {
        return getToolProxyId() ? true : false;
    };

    var getRegistrationRequest = function(id) {
        var request = {
            methodname: 'mod_lti_get_tool_proxy_registration_request',
            args: {
                id: id
            }
        };

        return ajax.call([request])[0];
    };

    var renderExternalRegistrationWindow = function(registrationRequest) {
        var promise = templates.render('mod_lti/tool_proxy_registration_form', registrationRequest);

        promise.done(function(html, js) {
            var container = getExternalRegistrationTemplateContainer();
            container.append(html);
            templates.runTemplateJS(js);

            container.find('form').submit();
            showExternalRegistrationContent();
            hideRegistrationForm();
        });

        return promise;
    };

    var setTypeStatusActive = function(typeData) {
        var promise = $.Deferred();
        startLoadingCapabilitiesContainer();

        toolType.update({
            id: typeData.id,
            state: toolType.constants.state.configured
        }).always(function() {
            stopLoadingCapabilitiesContainer();
            promise.resolve();
        });

        return promise;
    };

    var promptForToolTypeCapabilitiesAgreement = function(typeData) {
        var promise = $.Deferred();

        templates.render('mod_lti/tool_type_capabilities_agree', typeData).done(function(html, js) {
            var container = getToolTypeCapabilitiesTemplateContainer();

            hideRegistrationForm();
            hideExternalRegistrationContent();

            templates.replaceNodeContents(container, html, js)
            showToolTypeCapabilitiesContainer();

            var choiceContainer = container.find(SELECTORS.CAPABILITIES_AGREE_CONTAINER);

            choiceContainer.on(ltiEvents.CAPABILITIES_AGREE, function() {
                setTypeStatusActive(typeData).always(function() {
                    container.empty();
                    promise.resolve();
                });
            });

            choiceContainer.on(ltiEvents.CAPABILITIES_DECLINE, function() {
                container.empty();
                promise.resolve();
            });
        });

        promise.done(function() {
            hideToolTypeCapabilitiesContainer();
        });

        return promise;
    };

    var submitExternalRegistration = function() {
        var promise = $.Deferred();
        var url = getRegistrationURL();

        promise.done(function() { stopLoading() });

        if (url == "") {
            // No URL has been input.
            promise.resolve();
        } else {
            startLoading();

            toolProxy.create({regurl: url}).done(function(result) {
                var id = result.id;
                var regURL = result.regurl;

                // Save the id on the DOM to cleanup later.
                setToolProxyId(id);

                getRegistrationRequest(id).done(function(registrationRequest) {

                    registrationRequest.reg_url = regURL;
                    renderExternalRegistrationWindow(registrationRequest).done(function() {

                        promise.resolve();

                    }).fail(promise.fail);

                }).fail(promise.fail);

            }).fail(promise.fail);
        }

        return promise;
    };

    var finishExternalRegistration = function() {
        if (hasCreatedToolProxy()) {
            clearToolProxyId();
        };

        hideExternalRegistrationContent();
        showRegistrationForm();
        var container = getExternalRegistrationTemplateContainer();
        container.empty();

        $(document).trigger(ltiEvents.STOP_EXTERNAL_REGISTRATION);
    };

    var cancelRegistration = function() {
        startLoadingCancel();
        var promise = $.Deferred();

        if (hasCreatedToolProxy()) {
            var id = getToolProxyId();
            toolProxy.delete(id).done(function() {
                promise.resolve();
            });
        } else {
            promise.resolve();
        }

        promise.done(function() {
            finishExternalRegistration();
            stopLoadingCancel();
        });
    };

    var registerEventListeners = function() {

        $(document).on(ltiEvents.START_EXTERNAL_REGISTRATION, function() {
            submitExternalRegistration();
        });

        var cancelExternalRegistrationButton = getExternalRegistrationCancelButton();
        cancelExternalRegistrationButton.click(function(e) {
            e.preventDefault();
            cancelRegistration();
        });
        cancelExternalRegistrationButton.keypress(function(e) {
            if (!e.metaKey && !e.shiftKey && !e.altKey && !e.ctrlKey) {
                if (e.keyCode == KEYS.ENTER || e.keyCode == KEYS.SPACE) {
                    e.preventDefault();
                    cancelRegistration();
                }
            }
        });

        var cancelRegistrationButton = getRegistrationCancelButton();
        cancelRegistrationButton.click(function(e) {
            e.preventDefault();
            cancelRegistration();
        });
        cancelRegistrationButton.keypress(function(e) {
            if (!e.metaKey && !e.shiftKey && !e.altKey && !e.ctrlKey) {
                if (e.keyCode == KEYS.ENTER || e.keyCode == KEYS.SPACE) {
                    e.preventDefault();
                    cancelRegistration();
                }
            }
        });

        // This is gross but necessary due to isolated jQuery scopes between
        // child iframe and parent windows. There is no other way to communicate.
        window.triggerExternalRegistrationComplete = function(data) {
            var status = data.status;
            var message = "";
            var promise = $.Deferred();

            if (data.error == "") {
                message = data.message;
            } else {
                message = data.error;
            }

            promise.done(function() {
                finishExternalRegistration();
                $(document).trigger(ltiEvents.REGISTRATION_FEEDBACK, {status: status, message: message});
            });

            if (status == "success") {
                promise.done(function() {
                    $(document).trigger(ltiEvents.NEW_TOOL_TYPE);
                });

                if (hasCreatedToolProxy()) {
                    var proxyId = getToolProxyId();

                    toolType.getFromToolProxyId(proxyId).done(function(types) {
                        if (types && types.length) {
                            // There should only be one result.
                            var typeData = types[0];

                            if (typeData.hascapabilitygroups) {
                                promptForToolTypeCapabilitiesAgreement(typeData).always(function() {
                                    promise.resolve();
                                });
                            } else {
                                promise.resolve();
                            }
                        } else {
                            promise.resolve();
                        }
                    }).fail(function() {
                        promise.resolve();
                    });
                }
            }
        };
    };

    return {
        init: function() {
            registerEventListeners();
        }
    };
});
