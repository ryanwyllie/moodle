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
    'core/notification',
    'core/custom_interaction_events',
    'core/modal',
    'core/modal_events',
    'core/modal_registry',
    'core/yui',
    'core/fragment'
],
function(
    $,
    Notification,
    CustomEvents,
    Modal,
    ModalEvents,
    ModalRegistry,
    Y,
    Fragment
) {

    var registered = false;
    var CSS = {
        QBANKLOADING:       'div.questionbankloading',
        ADDQUESTIONLINKS:   '.menu [data-action="questionbank"]',
        ADDTOQUIZCONTAINER: 'td.addtoquizaction',
        PREVIEWCONTAINER:   'td.previewaction',
        SEARCHOPTIONS:      '#advancedsearch'
    };
    var SELECTORS = {
        TAG: '[data-tags]'
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalQuizQuestionBank = function(root) {
        Modal.call(this, root);
        this.initialLoad = false;
        this.tags = {};
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

    ModalQuizQuestionBank.prototype.show = function() {
        if (!this.initialLoad) {
            this.reloadBodyContent(window.location.search);
            this.initialLoad = true;
        }

        Modal.prototype.show.call(this);
    };

/*
    ModalQuizQuestionBank.prototype.indexTags = function() {
        this.tags = {};

        var body = this.getBody();
        var tagElements = body.find(SELECTORS.TAG);
        tagElements.each(function(index, element) {
            element = $(element);
            var tagString = element.attr('data-tags');
            tagString.split(',').forEach(function(tag) {
                if (this.tags.hasOwnProperty(tag)) {
                    this.tags[tag].push(element);
                } else {
                    this.tags[tag] = [element];
                }
            }.bind(this));
        }.bind(this));
    };
*/

    ModalQuizQuestionBank.prototype.reloadBodyContent = function(queryString) {
        var promise = Fragment.loadFragment(
            'mod_quiz',
            'quiz_question_bank',
            this.getContextId(),
            { querystring: queryString }
        );

        this.setBody(promise);
        /*
        promise.then(function() {
            M.question.qbankmanager.init();

            //this.initialiseSearchRegion();
            M.util.init_collapsible_region(Y, "advancedsearch", "question_bank_advanced_search",
                M.util.get_string('clicktohideshow', 'moodle'));
        });
        */
    };

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalQuizQuestionBank.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on('click', 'a[href]', function(e) {
            var target = $(e.currentTarget);

            // Add question to quiz. mofify the URL, then let it work as normal.
            if (e.currentTarget.ancestor(CSS.ADDTOQUIZCONTAINER)) {
                e.currentTarget.set('href', e.currentTarget.get('href') + '&addonpage=' + this.addonpage);
                return;
            }

            // Question preview. Needs to open in a pop-up.
            if (e.currentTarget.ancestor(CSS.PREVIEWCONTAINER)) {
                window.openpopup(e, {
                    url: e.currentTarget.get('href'),
                    name: 'questionpreview',
                    options: 'height=600,width=800,top=0,left=0,menubar=0,location=0,scrollbars,' +
                    'resizable,toolbar,status,directories=0,fullscreen=0,dependent'
                });
                return;
            }

            // Click on expand/collaspse search-options. Has its own handler.
            // We should not interfere.
            if (e.currentTarget.ancestor(CSS.SEARCHOPTIONS)) {
                return;
            }

            // Anything else means reload the pop-up contents.
            e.preventDefault();
            this.reloadBodyContent(e.currentTarget.get('search'));
        }.bind(this));

        this.getBody().on('change', '.form-autocomplete-selection input', function(e) {
            e.stopPropagation();
            debugger;
        });

        this.getModal().on('change', 'form', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var modifiedElement = $(e.target);
            if (modifiedElement.attr('aria-autocomplete')) {
                return;
            }

            var form = $(e.currentTarget);
            var queryString = '?' + form.serialize();
            this.reloadBodyContent(queryString);
        }.bind(this));

        //this.getRoot().on(ModalEvents.bodyRendered, this.indexTags.bind(this));
    };

    // Automatically register with the modal registry the first time this module is
    // imported so that you can create modals of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(
            ModalQuizQuestionBank.TYPE,
            ModalQuizQuestionBank,
            'mod_quiz/modal_quiz_question_bank'
        );

        registered = true;
    }

    return ModalQuizQuestionBank;
});
