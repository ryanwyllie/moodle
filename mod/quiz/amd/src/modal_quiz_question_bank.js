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
 * Contain the logic for the save/cancel modal.
 *
 * @module     core_calendar/modal_delete
 * @class      modal_delete
 * @package    core_calendar
 * @copyright  2017 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/yui',
    'core/notification',
    'core/modal',
    'core/modal_registry',
    'core/fragment'
],
function(
    $,
    Y,
    Notification,
    Modal,
    ModalRegistry,
    Fragment
) {

    var registered = false;
    var SELECTORS = {
        ADD_TO_QUIZ_CONTAINER: 'td.addtoquizaction',
        PREVIEW_CONTAINER:   'td.previewaction',
        SEARCH_OPTIONS:      '#advancedsearch',
        DISPLAY_OPTIONS: '#displayoptions',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalQuizQuestionBank = function(root) {
        Modal.call(this, root);
    };

    ModalQuizQuestionBank.TYPE = 'mod_quiz-quiz-question-bank';
    ModalQuizQuestionBank.prototype = Object.create(Modal.prototype);
    ModalQuizQuestionBank.prototype.constructor = ModalQuizQuestionBank;

    ModalQuizQuestionBank.prototype.setContextId = function(id) {
        this.contextId = id;
    };

    ModalQuizQuestionBank.prototype.getContextId = function(id) {
        return this.contextId;
    };

    ModalQuizQuestionBank.prototype.setAddOnPageId = function(id) {
        this.addOnPageId = id;
    };

    ModalQuizQuestionBank.prototype.getAddOnPageId = function() {
        return this.addOnPageId;
    };

    ModalQuizQuestionBank.prototype.show = function() {
        this.reloadBodyContent(window.location.search);
        Modal.prototype.show.call(this);
    };

    ModalQuizQuestionBank.prototype.reloadBodyContent = function(queryString) {
        var promise = Fragment.loadFragment(
            'mod_quiz',
            'quiz_question_bank',
            this.getContextId(),
            { querystring: queryString }
        ).fail(Notification.exception);

        this.setBody(promise);

        promise.then(function() {
            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.reset_form_dirty_state();
            });
        });
    };

    ModalQuizQuestionBank.prototype.registerDisplayOptionListeners = function() {
        var handleFormEvent = function(e) {
            // Stop propagation to prevent other wild event handlers
            // from submitting the form on change.
            e.stopPropagation();
            e.preventDefault();

            var form = $(e.target).closest(SELECTORS.DISPLAY_OPTIONS);
            var queryString = '?' + form.serialize();
            this.reloadBodyContent(queryString);
        }.bind(this);

        // Listen for changes to the display options form.
        this.getModal().on('change', SELECTORS.DISPLAY_OPTIONS, function(e) {
            // Get the element that was changed.
            var modifiedElement = $(e.target);
            if (modifiedElement.attr('aria-autocomplete')) {
                // If the element that was change is the autocomplete
                // input then we should ignore it because that is for
                // display purposes only.
                return;
            }

            handleFormEvent(e);
        }.bind(this));

        // Listen for the display options form submission.
        this.getModal().on('submit', SELECTORS.DISPLAY_OPTIONS, handleFormEvent.bind(this));
    };

    ModalQuizQuestionBank.prototype.handleAddToQuizEvent = function(e, anchorElement) {
        // If the user clicks the plus icon to add the question to the page
        // directly then we need to intercept the click in order to adjust the
        // href and include the correct add on page id before the page is
        // redirected.
        var href = anchorElement.attr('href') + '&addonpage=' + this.getAddOnPageId();
        anchorElement.attr('href', href);
    };

    ModalQuizQuestionBank.prototype.handlePreviewContainerEvent = function(e, anchorElement) {
        var popupOptions = [
            'height=600',
            'width=800',
            'top=0',
            'left=0',
            'menubar=0',
            'location=0',
            'scrollbars',
            'resizable',
            'toolbar',
            'status',
            'directories=0',
            'fullscreen=0',
            'dependent'
        ];
        window.openpopup(e, {
            url: anchorElement.attr('href'),
            name: 'questionpreview',
            options: popupOptions.join(',')
        });
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalQuizQuestionBank.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.registerDisplayOptionListeners();

        this.getModal().on('click', 'a[href]', function(e) {
            var anchorElement = $(e.currentTarget);

            // Add question to quiz. mofify the URL, then let it work as normal.
            if (anchorElement.closest(SELECTORS.ADD_TO_QUIZ_CONTAINER).length) {
                this.handleAddToQuizEvent(e, anchorElement);
                return;
            }

            // Question preview. Needs to open in a pop-up.
            if (anchorElement.closest(SELECTORS.PREVIEW_CONTAINER).length) {
                this.handlePreviewContainerEvent(e, anchorElement);
                return;
            }

            // Click on expand/collaspse search-options. Has its own handler.
            // We should not interfere.
            if (anchorElement.closest(SELECTORS.SEARCH_OPTIONS).length) {
                return;
            }

            // Anything else means reload the pop-up contents.
            e.preventDefault();
            this.reloadBodyContent(anchorElement.prop('search'));
        }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is
    // imported so that you can create modals of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(
            ModalQuizQuestionBank.TYPE,
            ModalQuizQuestionBank,
            'core/modal'
        );

        registered = true;
    }

    return ModalQuizQuestionBank;
});
