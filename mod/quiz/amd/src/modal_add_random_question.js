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
 * Contain the logic for the add random question modal.
 *
 * @module     mod_quiz/modal_add_random_question
 * @package    mod_quiz
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/yui',
    'core/notification',
    'core/modal',
    'core/modal_events',
    'core/modal_registry',
    'core/fragment',
    'core/templates',
],
function(
    $,
    Y,
    Notification,
    Modal,
    ModalEvents,
    ModalRegistry,
    Fragment,
    Templates
) {

    var registered = false;
    var SELECTORS = {
        EXISTING_CATEGORY_CONTAINER: '[data-region="existing-category-container"]',
        NEW_CATEGORY_CONTAINER: '[data-region="new-category-container"]',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalAddRandomQuestion = function(root) {
        Modal.call(this, root);
        this.contextId = null;
        this.addOnPageId = null;
        this.initialised = false;
    };

    ModalAddRandomQuestion.TYPE = 'mod_quiz-quiz-add-random-question';
    ModalAddRandomQuestion.prototype = Object.create(Modal.prototype);
    ModalAddRandomQuestion.prototype.constructor = ModalAddRandomQuestion;

    /**
     * Save the Moodle context id that the question bank is being
     * rendered in.
     *
     * @method setContextId
     * @param {int} id
     */
    ModalAddRandomQuestion.prototype.setContextId = function(id) {
        this.contextId = id;
    };

    /**
     * Retrieve the saved Moodle context id.
     *
     * @method getContextId
     * @return {int}
     */
    ModalAddRandomQuestion.prototype.getContextId = function() {
        return this.contextId;
    };

    /**
     * Set the id of the page that the question should be added to
     * when the user clicks the add to quiz link.
     *
     * @method setAddOnPageId
     * @param {int} id
     */
    ModalAddRandomQuestion.prototype.setAddOnPageId = function(id) {
        this.addOnPageId = id;
    };

    /**
     * Returns the saved page id for the question to be added it.
     *
     * @method getAddOnPageId
     * @return {int}
     */
    ModalAddRandomQuestion.prototype.getAddOnPageId = function() {
        return this.addOnPageId;
    };

    ModalAddRandomQuestion.prototype.show = function() {
        Modal.prototype.show.call(this);
        var existingCategoryContainer = this.getBody().find(SELECTORS.EXISTING_CATEGORY_CONTAINER);
        var newCategoryContainer = this.getBody().find(SELECTORS.NEW_CATEGORY_CONTAINER);

        Fragment.loadFragment(
            'mod_quiz',
            'add_random_question_form',
            this.getContextId(),
            {
                existingcategory: true
            }
        )
        .then(function(html, js) {
            Templates.replaceNodeContents(existingCategoryContainer, html, js)
            return;
        })
        .fail(Notification.exception);

        Fragment.loadFragment(
            'mod_quiz',
            'add_random_question_form',
            this.getContextId(),
            {
                existingcategory: false
            }
        )
        .then(function(html, js) {
            Templates.replaceNodeContents(existingCategoryContainer, html, js)
            return;
        })
        .fail(Notification.exception);
    };

    /**
     * Replaces the current body contents with a new version of the question
     * bank.
     *
     * The contents of the question bank are generated using the provided
     * query string.
     *
     * @method reloadBodyContent
     * @param {string} queryString URL encoded string.
     */
    ModalAddRandomQuestion.prototype.reloadBodyContent = function(queryString) {
        // Load the question bank fragment to be displayed in the modal.
        var promise = Fragment.loadFragment(
            'mod_quiz',
            'quiz_question_bank',
            this.getContextId(),
            {
                querystring: queryString
            }
        ).fail(Notification.exception);

        this.setBody(promise);
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalAddRandomQuestion.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        // Disable the form change checker when the body is rendered.
        this.getRoot().on(ModalEvents.bodyRendered, function() {
            // Make sure the form change checker is disabled otherwise it'll
            // stop the user from navigating away from the page once the modal
            // is hidden.
            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.reset_form_dirty_state();
            });
        });
    };

    // Automatically register with the modal registry the first time this module is
    // imported so that you can create modals of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(
            ModalAddRandomQuestion.TYPE,
            ModalAddRandomQuestion,
            'mod_quiz/modal_add_random_question'
        );

        registered = true;
    }

    return ModalAddRandomQuestion;
});
