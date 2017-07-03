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
            'core/notification',
            'core/templates',
            'core/custom_interaction_events',
            'core/modal',
            'core/modal_registry',
            'core/html5_form_validator',
            'core/bootstrap_form_validation_styles',
            'core_calendar/calendar_repository'
        ],
        function(
            $,
            Notification,
            Templates,
            CustomEvents,
            Modal,
            ModalRegistry,
            HTML5FormValidator,
            BootstrapFormValidationStyles,
            Repository
        ) {

    var registered = false;
    var SELECTORS = {
        MORE_LINK: '[data-action="more"]',
        SAVE_BUTTON: '[data-action="save"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        EVENT_NAME: '[data-event-name]',
        EVENT_TYPE: '[data-event-type]',
        EVENT_COURSE_ID: '[data-event-course-id]',
        EVENT_GROUP_ID: '[data-event-group-id]',
        EVENT_COURSE_SELECT: '[data-region="course-select"]',
        EVENT_GROUP_SELECT: '[data-region="group-select"]',
    };
    var ATTRIBUTES = {
        EVENT_TIME: 'data-event-time'
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalQuickAddEvent = function(root) {
        Modal.call(this, root);
    };

    ModalQuickAddEvent.TYPE = 'core_calendar-modal_quick_add_event';
    ModalQuickAddEvent.prototype = Object.create(Modal.prototype);
    ModalQuickAddEvent.prototype.constructor = ModalQuickAddEvent;

    ModalQuickAddEvent.prototype.hide = function() {
        // Reset the form when the user hides the modal.
        this.getBody().find('form')[0].reset();
        // Apply parent hide function;
        Modal.prototype.hide.call(this);
    };

    ModalQuickAddEvent.prototype.showCourseSelector = function() {
        var courseSelect = this.getBody().find(SELECTORS.EVENT_COURSE_SELECT).removeClass('hidden');
        courseSelect.find(SELECTORS.EVENT_COURSE_ID).prop('disabled', false);
    };

    ModalQuickAddEvent.prototype.hideCourseSelector = function() {
        var courseSelect = this.getBody().find(SELECTORS.EVENT_COURSE_SELECT).addClass('hidden');
        courseSelect.find(SELECTORS.EVENT_COURSE_ID).prop('disabled', true);
    };

    ModalQuickAddEvent.prototype.showGroupSelector = function() {
        var courseSelect = this.getBody().find(SELECTORS.EVENT_GROUP_SELECT).removeClass('hidden');
        courseSelect.find(SELECTORS.EVENT_GROUP_ID).prop('disabled', false);
    };

    ModalQuickAddEvent.prototype.hideGroupSelector = function() {
        var courseSelect = this.getBody().find(SELECTORS.EVENT_GROUP_SELECT).addClass('hidden');
        courseSelect.find(SELECTORS.EVENT_GROUP_ID).prop('disabled', true);
    };

    ModalQuickAddEvent.prototype.getEventProperties = function() {
        var nameElement = this.getBody().find(SELECTORS.EVENT_NAME);
        var typeElement = this.getBody().find(SELECTORS.EVENT_TYPE);
        var courseElement = this.getBody().find(SELECTORS.EVENT_COURSE_ID);
        var groupElement = this.getBody().find(SELECTORS.EVENT_GROUP_ID);
        var saveButton = this.getFooter().find(SELECTORS.SAVE_BUTTON);
        var eventType = typeElement.length ? typeElement.val() : 'user';
        var properties = {
            name: nameElement.val().trim(),
            eventtype: eventType,
            timestart: saveButton.attr(ATTRIBUTES.EVENT_TIME)
        };

        // Only include the course id if the event type isn't
        // a user event.
        if (eventType == 'course') {
            properties.courseid = courseElement.val();
        } else if (eventType == 'group') {
            properties.groupid = groupElement.val();
            properties.courseid = groupElement.val();
        }

        return properties;
    };

    ModalQuickAddEvent.prototype.saveEvent = function() {
        var saveButton = this.getFooter().find(SELECTORS.SAVE_BUTTON);
        var moreButton = this.getFooter().find(SELECTORS.MORE_BUTTON);
        var loadingContainer = saveButton.find(SELECTORS.LOADING_ICON_CONTAINER);
        var form = this.getBody().find('form');

        if (HTML5FormValidator.isValid(form)) {
            loadingContainer.removeClass('hidden');
            saveButton.prop('disabled', true);
            moreButton.prop('disabled', true);

            // save event.
            var eventProperties = this.getEventProperties();
            Repository.createEvent(eventProperties)
                .then(function() {
                    this.hide();
                    // Reload the page so that the new event shows up.
                    window.location.reload();
                }.bind(this))
                .always(function() {
                    loadingContainer.addClass('hidden');
                    saveButton.prop('disabled', false);
                    moreButton.prop('disabled', false);
                });
        }
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalQuickAddEvent.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        var form = this.getBody().find('form');

        // Validate the form as the user fills it out.
        HTML5FormValidator.validateOnBlur(form);
        // Apply the appropriate Bootstrap classes for form validation
        // based on the events triggered by the HTML5FormValidator.
        BootstrapFormValidationStyles.init(
            form,
            HTML5FormValidator.events.INVALID, // The invalid event to listen for.
            HTML5FormValidator.events.VALID, // The valid event to listen for.
            true // We want to show the success styling.
        );

        this.getBody().find(SELECTORS.EVENT_TYPE).on('change', function(e) {
            var typeElement = $(e.target).closest(SELECTORS.EVENT_TYPE);
            var type = typeElement.val();

            if (type == 'course') {
                this.showCourseSelector();
                this.hideGroupSelector();
            } else if (type == 'group') {
                this.showGroupSelector();
                this.hideCourseSelector();
            } else {
                this.hideCourseSelector();
                this.hideGroupSelector();
            }
        }.bind(this));

        // When the user clicks the save button.
        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(e, data) {
            this.saveEvent();
            data.originalEvent.preventDefault();
        }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalQuickAddEvent.TYPE, ModalQuickAddEvent, 'calendar/modal_quick_add_event');
        registered = true;
    }

    return ModalQuickAddEvent;
});
