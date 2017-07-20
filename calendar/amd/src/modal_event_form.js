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
 * Contain the logic for the quick add event modal.
 *
 * @module     calendar/modal_quick_add_event
 * @class      modal_quick_add_event
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
            'jquery',
            'core/event',
            'core/str',
            'core/notification',
            'core/templates',
            'core/custom_interaction_events',
            'core/modal',
            'core/modal_registry',
            'core/fragment',
            'core_calendar/repository',
            'core_calendar/event_form'
        ],
        function(
            $,
            Event,
            Str,
            Notification,
            Templates,
            CustomEvents,
            Modal,
            ModalRegistry,
            Fragment,
            Repository,
            EventForm
        ) {

    var registered = false;
    var SELECTORS = {
        MORELESS_BUTTON: '[data-action="more-less-toggle"]',
        SAVE_BUTTON: '[data-action="save"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalEventForm = function(root) {
        Modal.call(this, root);
        this.eventId = null;
        this.saveButton = this.getFooter().find(SELECTORS.SAVE_BUTTON);
        this.moreLessButton = this.getFooter().find(SELECTORS.MORELESS_BUTTON);
    };

    ModalEventForm.TYPE = 'core_calendar-modal_event_form';
    ModalEventForm.prototype = Object.create(Modal.prototype);
    ModalEventForm.prototype.constructor = ModalEventForm;

    ModalEventForm.prototype.setEventId = function(id) {
        this.eventId = id;
    };

    ModalEventForm.prototype.getEventId = function() {
        return this.eventId;
    };

    ModalEventForm.prototype.hasEventId = function() {
        return this.eventid !== null;
    };

    ModalEventForm.prototype.getForm = function() {
        return this.getBody().find('form');
    };

    ModalEventForm.prototype.disableButtons = function() {
        this.saveButton.prop('disabled', true);
        this.moreLessButton.prop('disabled', true);
    };

    ModalEventForm.prototype.enableButtons = function() {
        this.saveButton.prop('disabled', false);
        this.moreLessButton.prop('disabled', false);
    };

    ModalEventForm.prototype.setMoreButton = function() {
        this.moreLessButton.attr('data-collapsed', 'true');
        Str.get_string('more', 'calendar').then(function(string) {
            this.moreLessButton.text(string);
        }.bind(this));;
    };

    ModalEventForm.prototype.setLessButton = function() {
        this.moreLessButton.attr('data-collapsed', 'false');
        Str.get_string('less', 'calendar').then(function(string) {
            this.moreLessButton.text(string);
        }.bind(this));;
    };

    ModalEventForm.prototype.toggleMoreLessButton = function() {
        var form = this.getForm();

        if (this.moreLessButton.attr('data-collapsed') == 'true') {
            form.trigger(EventForm.events.SHOW_ADVANCED);
            this.setLessButton();
        } else {
            form.trigger(EventForm.events.HIDE_ADVANCED);
            this.setMoreButton();
        }
    };

    ModalEventForm.prototype.reloadTitleContent = function() {
        var titlePromise;

        if (this.hasEventId()) {
            titlePromise = Str.get_string('editevent', 'calendar');
        } else {
            titlePromise = Str.get_string('newevent', 'calendar');
        }

        titlePromise.then(function(string) {
            this.setTitle(string);
            return string;
        }.bind(this));

        return titlePromise;
    };

    ModalEventForm.prototype.reloadBodyContent = function(formData, hasError) {
        this.disableButtons();

        var contextId = this.saveButton.attr('data-context-id');
        var args = {};

        if (this.hasEventId()) {
            args.eventid = this.getEventId();
        }

        if (typeof formData !== 'undefined') {
            args.formdata = formData;
        }

        args.haserror = (typeof hasError == 'undefined') ? false : hasError;

        var promise = Fragment.loadFragment('calendar', 'event_form', contextId, args);
        promise.fail(Notification.exception);

        this.setBody(promise);

        promise.done(function(html, js) {
            this.enableButtons();
        }.bind(this));

        return promise;
    };

    ModalEventForm.prototype.reloadAllContent = function() {
        return $.when(this.reloadTitleContent(), this.reloadBodyContent());
    };

    ModalEventForm.prototype.show = function() {
        this.reloadAllContent();

        Modal.prototype.show.call(this);
    };

    ModalEventForm.prototype.hide = function() {
        Modal.prototype.hide.call(this);
        this.setEventId(null);
    };

    ModalEventForm.prototype.getFormData = function() {
        return this.getForm().serialize();
    };

    ModalEventForm.prototype.save = function() {
        var loadingContainer = this.saveButton.find(SELECTORS.LOADING_ICON_CONTAINER);

        loadingContainer.removeClass('hidden');
        this.disableButtons();

        // save event.
        var formData = this.getFormData();
        Repository.submitEventForm(formData)
            .then(function(response) {
                if (response.validationerror) {
                    return this.reloadBodyContent(formData, true);
                } else {
                    this.hide();
                    // Reload the page so that the new event shows up.
                    window.location.reload();
                }
            }.bind(this))
            .always(function() {
                loadingContainer.addClass('hidden');
                this.enableButtons();
            }.bind(this))
            .fail(Notification.exception);
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalEventForm.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        // When the user clicks the save button we trigger the form submission. We need to
        // trigger an actual submission because there is some JS code in the form that is
        // listening for this event and doing some stuff (e.g. saving draft areas etc).
        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(e, data) {
            this.getForm().submit();
            data.originalEvent.preventDefault();
            e.stopPropagation();
        }.bind(this));

        // Catch the submit event before it is actually processed by the browser and
        // prevent the submission. We'll take it from here.
        this.getModal().on('submit', function(e) {
            this.save();

            // Stop the form from actually submitting and prevent it's
            // propagation because we have already handled the event.
            e.preventDefault();
            e.stopPropagation();
        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.MORELESS_BUTTON, function(e, data) {
            this.toggleMoreLessButton();

            data.originalEvent.preventDefault();
            e.stopPropagation();
        }.bind(this));

        this.getModal().on(EventForm.events.ADVANCED_SHOWN, function() {
            this.setLessButton();
        }.bind(this));

        this.getModal().on(EventForm.events.ADVANCED_HIDDEN, function() {
            this.setMoreButton();
        }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalEventForm.TYPE, ModalEventForm, 'calendar/modal_event_form');
        registered = true;
    }

    return ModalEventForm;
});
