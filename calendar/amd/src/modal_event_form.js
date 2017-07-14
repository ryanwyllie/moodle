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
            'core_calendar/repository'
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
            Repository
        ) {

    var registered = false;
    var SELECTORS = {
        MORE_LINK: '[data-action="more"]',
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
        this.reloadBody = false;
        this.saveButton = this.getFooter().find(SELECTORS.SAVE_BUTTON);
        this.moreButton = this.getFooter().find(SELECTORS.MORE_BUTTON);
    };

    ModalEventForm.TYPE = 'core_calendar-modal_event_form';
    ModalEventForm.prototype = Object.create(Modal.prototype);
    ModalEventForm.prototype.constructor = ModalEventForm;

    ModalEventForm.prototype.setEventId = function(id) {
        this.reloadBody = true;
        this.eventId = id;
    };

    ModalEventForm.prototype.getEventId = function() {
        return this.eventId;
    };

    ModalEventForm.prototype.hasEventId = function() {
        return this.eventid === null;
    };

    ModalEventForm.prototype.disableButtons = function() {
        this.saveButton.prop('disabled', true);
        this.moreButton.prop('disabled', true);
    };

    ModalEventForm.prototype.enableButtons = function() {
        this.saveButton.prop('disabled', false);
        this.moreButton.prop('disabled', false);
    };

    ModalEventForm.prototype.show = function() {
        if (!this.isAttached || this.reloadBody) {
            this.disableButtons();

            var contextId = this.saveButton.attr('data-context-id');
            var args = {};
            var titlePromise;

            if (this.hasEventId()) {
                args.eventid = this.getEventId();
                titlePromise = Str.get_string('editevent', 'calendar');
            } else {
                titlePromise = Str.get_string('newevent', 'calendar');
            }

            titlePromise.done(function(string) {
                this.setTitle(string);
            }.bind(this));

            var promise = Fragment.loadFragment('calendar', 'event_form', contextId, args);
            promise.fail(Notification.exception);
            promise.done(function() {
                this.enableButtons();
            }.bind(this));

            this.setBody(promise);
        }

        Modal.prototype.show.call(this);
    };

    ModalEventForm.prototype.hide = function() {
        // Reset the form when the user hides the modal.
        this.getBody().find('form')[0].reset();
        // Apply parent hide function;
        Modal.prototype.hide.call(this);
    };

    ModalEventForm.prototype.getFormData = function() {
        return this.getBody().find('form').serialize();
    };

    ModalEventForm.prototype.save = function() {
        var loadingContainer = this.saveButton.find(SELECTORS.LOADING_ICON_CONTAINER);

        loadingContainer.removeClass('hidden');
        this.disableButtons();

        // save event.
        var form = this.getBody().find('form');
        var formData = this.getFormData();
        Repository.submitEventForm(formData)
            .then(function(response) {
                if (response.validationerror) {
                    response.errorelements.forEach(function(error) {
                        var element = form.find('#id_' + error.name);

                        if (element.length) {
                            element.trigger(Event.Events.FORM_FIELD_VALIDATION, error.message);
                        }
                    });
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

        // When the user clicks the save button.
        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(e, data) {
            this.save();
            data.originalEvent.preventDefault();
            e.stopPropagation();
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
