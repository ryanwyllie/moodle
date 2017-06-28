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
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
            'jquery',
            'core/notification',
            'core/custom_interaction_events',
            'core/modal',
            'core/modal_registry'
        ],
        function(
            $,
            Notification,
            CustomEvents,
            Modal,
            ModalRegistry
        ) {

    var registered = false;
    var SELECTORS = {
        MORE_LINK: '[data-action="more"]',
        SAVE_BUTTON: '[data-action="save"]',
        EVENT_DATE_TIME: '[data-event-date-time]',
        EVENT_TYPE: '[data-event-type]',
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

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalQuickAddEvent.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalQuickAddEvent.TYPE, ModalQuickAddEvent, 'calendar/modal_quick_add_event');
        registered = true;
    }

    return ModalQuickAddEvent;
});
