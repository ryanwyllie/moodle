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
 *
 * @module    mod_quiz/quizquestionbank
 * @package   mod_quiz
 * @copyright 2018 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'core/custom_interaction_events',
        'core/modal_factory',
        'mod_quiz/modal_quiz_question_bank'
    ],
    function(
        $,
        CustomEvents,
        ModalFactory,
        ModalQuizQuestionBank
    ) {

    var SELECTORS = {
        ADDQUESTIONLINKS:   '.menu [data-action="questionbank"]',
    };

    return {
        init: function(contextId, courseModuleId) {
            var body = $('body');

            ModalFactory.create(
                {
                    type: ModalQuizQuestionBank.TYPE,
                    large: true
                },
                [body, SELECTORS.ADDQUESTIONLINKS]
            ).then(function(modal) {
                modal.setContextId(contextId);

                body.on(CustomEvents.events.activate, SELECTORS.ADDQUESTIONLINKS, function(e) {
                    // We need to listen for activations on the trigger elements because there are
                    // several on the page and we need to know which one was activated in order to
                    // set some relevant data on the modal.
                    var triggerElement = $(e.target).closest(SELECTORS.ADDQUESTIONLINKS);
                    modal.setAddOnPageId(triggerElement.attr('data-addonpage'));
                    modal.setTitle(triggerElement.attr('data-header'));
                });
            });
        }
    };
});
