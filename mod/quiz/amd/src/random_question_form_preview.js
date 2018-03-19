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
 * JavaScript for the random_question_form_preview of the
 * add_random_form class.
 *
 * @module    mod_quiz/random_question_form_preview
 * @package   mod_quiz
 * @copyright 2018 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'core/ajax',
        'core/notification',
        'core/templates',
        'core/paged_content_factory'
    ],
    function(
        $,
        Ajax,
        Notification,
        Templates,
        PagedContentFactory
    ) {

    var ITEMS_PER_PAGE = 1;
    var TEMPLATE_NAME = 'mod_quiz/random_question_form_preview_question_list';
    var SELECTORS = {
        LOADING_ICON_CONTAINER: '[data-region="overlay-icon-container"]',
        QUESTION_LIST_CONTAINER: '[data-region="question-list-container"]'
    };

    var showLoadingIcon = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var hideLoadingIcon = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var buildTemplateContext = function(questions) {
        return {
            hasquestions: questions.length > 0,
            questions: questions
        };
    };

    var reload = function(root, categoryId, includeSubcategories, tagIds, contextId) {
        var request = {
            methodname: 'core_question_get_random_questions',
            args: {
                categoryid: categoryId,
                includesubcategories: includeSubcategories,
                tagids: tagIds,
                contextid: contextId
            }
        };

        showLoadingIcon(root);
        return Ajax.call([request])[0]
            .then(function(questions) {
                return PagedContentFactory.createFromStaticList(questions, ITEMS_PER_PAGE, function(pageQuestions) {
                    return Templates.render(TEMPLATE_NAME, buildTemplateContext(pageQuestions));
                });
            })
            .then(function(html, js) {
                var container = root.find(SELECTORS.QUESTION_LIST_CONTAINER);
                Templates.replaceNodeContents(container, html, js);
                return;
            })
            .always(function() {
                hideLoadingIcon(root);
            })
            .fail(Notification.exception);
    };

    return {
        reload: reload,
        showLoadingIcon: showLoadingIcon,
        hideLoadingIcon: hideLoadingIcon
    };
});
