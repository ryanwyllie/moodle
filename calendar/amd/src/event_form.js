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
 * A javascript module to enhance the event form.
 *
 * @module     core_calendar/event_form
 * @package    core_calendar
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    var SELECTORS = {
        EVENT_TYPE: '[name="eventtype"]',
        EVENT_COURSE_ID: '[name="courseid"]',
        EVENT_GROUP_COURSE_ID: '[name="groupcourseid"]',
        EVENT_GROUP_ID: '[name="groupid"]',
        FORM_GROUP: '.form-group',
        SELECT_OPTION: 'option'
    };

    var EVENT_TYPES = {
        USER: 'user',
        SITE: 'site',
        COURSE: 'course',
        GROUP: 'group'
    };

    var parseGroupSelect = function(formElement) {
        formElement.find(SELECTORS.EVENT_GROUP_ID)
            .find(SELECTORS.SELECT_OPTION)
            .each(function(index, element) {
                var element = $(element);
                var value = element.attr('value');
                var splits = value.split('-');
                var courseId = splits[0];
                var groupId = splits[1];

                element.attr('value', groupId);
                element.attr('data-course-id', courseId);
            });
    };

    var hideTypeSubSelects = function(formElement) {
        formElement.find(SELECTORS.EVENT_COURSE_ID).closest(SELECTORS.FORM_GROUP).addClass('hidden');
        formElement.find(SELECTORS.EVENT_GROUP_COURSE_ID).closest(SELECTORS.FORM_GROUP).addClass('hidden');
        formElement.find(SELECTORS.EVENT_GROUP_ID).closest(SELECTORS.FORM_GROUP).addClass('hidden');
    };

    var addTypeSelectListeners = function(formElement) {
        var typeSelect = formElement.find(SELECTORS.EVENT_TYPE);

        typeSelect.on('change', function(e) {
            hideTypeSubSelects(formElement);
            var type = typeSelect.val();

            if (type == EVENT_TYPES.COURSE) {
                formElement.find(SELECTORS.EVENT_COURSE_ID).closest(SELECTORS.FORM_GROUP).removeClass('hidden');
            } else if (type == EVENT_TYPES.GROUP) {
                formElement.find(SELECTORS.EVENT_GROUP_COURSE_ID).closest(SELECTORS.FORM_GROUP).removeClass('hidden');
                formElement.find(SELECTORS.EVENT_GROUP_ID).closest(SELECTORS.FORM_GROUP).removeClass('hidden');
            }
        });
    };

    var addCourseGroupSelectListeners = function(formElement) {
        var courseGroupSelect = formElement.find(SELECTORS.EVENT_GROUP_COURSE_ID);
        var groupSelect = formElement.find(SELECTORS.EVENT_GROUP_ID);
        var groupSelectOptions = groupSelect.find(SELECTORS.SELECT_OPTION);
        var filterGroupSelectOptions = function() {
            var selectedCourseId = courseGroupSelect.val();
            var selectedIndex = null;

            groupSelectOptions.each(function(index, element) {
                element = $(element);

                if (element.attr('data-course-id') == selectedCourseId) {
                    element.removeClass('hidden');
                    element.prop('disabled', false);

                    if (selectedIndex == null) {
                        selectedIndex = index;
                    }
                } else {
                    element.addClass('hidden');
                    element.prop('disabled', true);
                }
            });

            groupSelect.prop('selectedIndex', selectedIndex);
        };

        courseGroupSelect.on('change', filterGroupSelectOptions);
        filterGroupSelectOptions();
    };

    var init = function(formId) {
        var formElement = $('#' + formId);

        hideTypeSubSelects(formElement);
        parseGroupSelect(formElement);
        addTypeSelectListeners(formElement);
        addCourseGroupSelectListeners(formElement);
    };

    return {
        init: init
    };
});
