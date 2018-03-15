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
        TAB_CONTENT: '[data-region="tab-content"]',
        ADD_ON_PAGE_FORM_ELEMENT: '[name="addonpage"]',
        SUBMIT_BUTTON_ELEMENT: 'input[type="submit"]',
        CANCEL_BUTTON_ELEMENT: 'input[type="submit"][name="cancel"]',
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
        this.category = null;
        this.returnUrl = null;
        this.cmid = null;
        this.loadedForm = false;
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
        this.getBody().find(SELECTORS.ADD_ON_PAGE_FORM_ELEMENT).val(id);
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

    /**
     * Set the category for this form. The category is a comma separated
     * category id and category context id.
     *
     * @method setCategory
     * @param {string} category
     */
    ModalAddRandomQuestion.prototype.setCategory = function(category) {
        this.category = category;
    };

    /**
     * Returns the saved category.
     *
     * @method getCategory
     * @return {string}
     */
    ModalAddRandomQuestion.prototype.getCategory = function() {
        return this.category;
    };

    /**
     * Set the return URL for the form.
     *
     * @method setReturnUrl
     * @param {string} url
     */
    ModalAddRandomQuestion.prototype.setReturnUrl = function(url) {
        this.returnUrl = url;
    };

    /**
     * Returns the return URL for the form.
     *
     * @method getReturnUrl
     * @return {string}
     */
    ModalAddRandomQuestion.prototype.getReturnUrl = function() {
        return this.returnUrl;
    };

    /**
     * Set the course module id for the form.
     *
     * @method setCMID
     * @param {int} id
     */
    ModalAddRandomQuestion.prototype.setCMID = function(id) {
        this.cmid = id;
    };

    /**
     * Returns the course module id for the form.
     *
     * @method getCMID
     * @return {int}
     */
    ModalAddRandomQuestion.prototype.getCMID = function() {
        return this.cmid;
    };

    ModalAddRandomQuestion.prototype.moveFormElementIntoTab = function(formElement, tabElement) {
        var submitButtons = formElement.find(SELECTORS.SUBMIT_BUTTON_ELEMENT);
        var footer = $('<div class="modal-footer m-t-1" data-region="footer"></div>');
        // Hide the header because the tabs show us which part of the form we're
        // looking at.
        formElement.find('legend').addClass('hidden');
        // Move the element inside a tab.
        formElement.wrap(tabElement);
        // Remove the buttons container element.
        submitButtons.closest('.fitem').remove();
        // Put the button inside a footer.
        submitButtons.appendTo(footer);
        // Add the footer to the end of the category form element.
        footer.appendTo(formElement);
    };

    ModalAddRandomQuestion.prototype.moveTabsIntoTabContent = function(form) {
        // Empty it to remove the loading icon.
        var tabContent = this.getBody().find(SELECTORS.TAB_CONTENT).empty();
        // Make sure all tabs are inside the tab content element.
        form.find('[role="tabpanel"]').wrapAll(tabContent);
    };

    ModalAddRandomQuestion.prototype.moveCancelButtonToTabs = function(form) {
        var cancelButton = form.find(SELECTORS.CANCEL_BUTTON_ELEMENT).addClass('m-r-1');
        var tabFooters = form.find('[data-region="footer"]');
        // Remove the buttons container element.
        cancelButton.closest('.fitem').remove();
        cancelButton.clone().prependTo(tabFooters);
    }

    ModalAddRandomQuestion.prototype.loadForm = function() {
        return Fragment.loadFragment(
            'mod_quiz',
            'add_random_question_form',
            this.getContextId(),
            {
                addonpage: this.getAddOnPageId(),
                cat: this.getCategory(),
                returnurl: this.getReturnUrl(),
                cmid: this.getCMID()
            }
        )
        .then(function(html, js) {
            var form = $(html);
            var existingCategoryFormElement = form.find("#id_existingcategoryheader");
            var existingCategoryTab = this.getBody().find(SELECTORS.EXISTING_CATEGORY_CONTAINER);
            var newCategoryFormElement = form.find("#id_newcategoryheader");
            var newCategoryTab = this.getBody().find(SELECTORS.NEW_CATEGORY_CONTAINER);

            this.moveFormElementIntoTab(existingCategoryFormElement, existingCategoryTab)
            this.moveFormElementIntoTab(newCategoryFormElement, newCategoryTab)
            this.moveTabsIntoTabContent(form);
            this.moveCancelButtonToTabs(form);

            Templates.replaceNode(this.getBody().find(SELECTORS.TAB_CONTENT), form, js);
            return;
        }.bind(this))
        .then(function() {
            // Make sure the form change checker is disabled otherwise it'll
            // stop the user from navigating away from the page once the modal
            // is hidden.
            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.reset_form_dirty_state();
            });
        })
        .fail(Notification.exception);
    };

    ModalAddRandomQuestion.prototype.show = function() {
        Modal.prototype.show.call(this);

        if (!this.loadedForm) {
            this.loadForm();
            this.loadedForm = true;
        }
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
