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
 * JavaScript for the add_random_form class.
 *
 * @module    mod_quiz/add_random_form
 * @package   mod_quiz
 * @copyright 2018 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'mod_quiz/random_question_form_preview'
    ],
    function(
        $,
        RandomQuestionFormPreview
    ) {

    // Wait 2 seconds before reloading the question set just in case
    // the user is still changing the criteria.
    var RELOAD_DELAY = 2000;
    var SELECTORS = {
        PREVIEW_CONTAINER: '[data-region="random-question-preview-container"]',
        CATEGORY_FORM_ELEMENT: '[name="category"]',
        SUBCATEGORY_FORM_ELEMENT: '[name="includesubcategories"]',
        TAG_IDS_FORM_ELEMENT: '[name="fromtags[]"]'
    };

    var getCategoryId = function(form) {
        // The value string is the category id and category context id joined
        // by a comma.
        var valueString = form.find(SELECTORS.CATEGORY_FORM_ELEMENT).val();
        // Split the two ids.
        var values = valueString.split(',');
        // Return just the category id.
        return values[0];
    };

    var shouldIncludeSubcategories = function(form) {
        return form.find(SELECTORS.SUBCATEGORY_FORM_ELEMENT).is(':checked');
    };

    var getTagIds = function(form) {
        var values = form.find(SELECTORS.TAG_IDS_FORM_ELEMENT).val();
        return values.map(function(value) {
            // The tag element value is the tag id and tag name joined
            // by a comma. So we need to split them to get the tag id.
            var parts = value.split(',');
            return parts[0];
        });
    };

    var reloadQuestionPreview = function(form, contextId) {
        var previewContainer = form.find(SELECTORS.PREVIEW_CONTAINER);
        RandomQuestionFormPreview.reload(
            previewContainer,
            getCategoryId(form),
            shouldIncludeSubcategories(form),
            getTagIds(form),
            contextId
        );
    };

    var isInterestingElement = function(element) {
        if (element.closest(SELECTORS.CATEGORY_FORM_ELEMENT).length > 0) {
            return true;
        }

        if (element.closest(SELECTORS.SUBCATEGORY_FORM_ELEMENT).length > 0) {
            return true;
        }

        if (element.closest(SELECTORS.TAG_IDS_FORM_ELEMENT).length > 0) {
            return true;
        }

        return false;
    };

    var addEventListeners = function(form, contextId) {
        var reloadTimerId = null;

        form.on('change', function(e) {
            // Only reload the preview when elements that will change the result
            // are modified.
            if (!isInterestingElement($(e.target))) {
                return;
            }

            // Show the loading icon to let the user know that the preview
            // will be updated after their actions.
            RandomQuestionFormPreview.showLoadingIcon(form);

            if (reloadTimerId) {
                // Reset the timer each time the form is modified.
                clearTimeout(reloadTimerId);
            }

            // Don't immediately reload the question preview section just
            // in case the user is still modifying the form. We don't want to
            // spam reload requests.
            reloadTimerId = setTimeout(function() {
                reloadQuestionPreview(form, contextId);
            }, RELOAD_DELAY);
        });
    };

    var init = function(formId, contextId) {
        var form = $('#' + formId);

        reloadQuestionPreview(form, contextId);
        addEventListeners(form, contextId);
    };

    return {
        init: init
    };
});
