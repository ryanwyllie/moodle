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
        'core/modal_factory',
        'mod_quiz/modal_quiz_question_bank'
    ],
    function(
        $,
        ModalFactory,
        ModalQuizQuestionBank
    ) {

    var SELECTORS = {
        ADDQUESTIONLINKS:   '.menu [data-action="questionbank"]',
    };

    return {
        init: function(contextId, courseModuleId) {
            ModalFactory.create(
                {
                    type: ModalQuizQuestionBank.TYPE,
                    large: true
                },
                $(SELECTORS.ADDQUESTIONLINKS)
            ).then(function(modal) {
                modal.setContextId(contextId);
            });
        }
    };
});
