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
define(['jquery', 'core/templates'], function($, Templates) {

    var SELECTORS = {
        EVENT_TYPE: '[name="eventtype"]',
        EVENT_COURSE_ID: '[name="courseid"]',
        EVENT_GROUP_COURSE_ID: '[name="groupcourseid"]',
        EVENT_GROUP_ID: '[name="groupid"]',
        FORM_GROUP: '.form-group',
        SELECT_OPTION: 'option',
        ADVANCED_ELEMENT: '.fitem.advanced',
        FIELDSET_ADVANCED_ELEMENTS: 'fieldset.containsadvancedelements',
        MORELESS_TOGGLE: '.moreless-actions'
    };

    var EVENT_TYPES = {
        USER: 'user',
        SITE: 'site',
        COURSE: 'course',
        GROUP: 'group'
    };

    var EVENTS = {
        SHOW_ADVANCED: 'event_form-show-advanced',
        HIDE_ADVANCED: 'event_form-hide-advanced',
        ADVANCED_SHOWN: 'event_form-advanced-shown',
        ADVANCED_HIDDEN: 'event_form-advanced-hidden',
    };

    var destroyOldMoreLessToggle = function(formElement) {
        formElement.find(SELECTORS.FIELDSET_ADVANCED_ELEMENTS).removeClass('containsadvancedelements');
        var element = formElement.find(SELECTORS.MORELESS_TOGGLE);
        Templates.replaceNode(element, '', '');
    };

    var showAdvancedElements = function(formElement) {
        formElement.find(SELECTORS.ADVANCED_ELEMENT).removeClass('hidden');
        formElement.trigger(EVENTS.ADVANCED_SHOWN);
    };

    var hideAdvancedElements = function(formElement) {
        formElement.find(SELECTORS.ADVANCED_ELEMENT).addClass('hidden');
        formElement.trigger(EVENTS.ADVANCED_HIDDEN);
    };

    var listenForShowHideEvents = function(formElement) {
        formElement.on(EVENTS.SHOW_ADVANCED, function() {
            showAdvancedElements(formElement);
        });

        formElement.on(EVENTS.HIDE_ADVANCED, function() {
            hideAdvancedElements(formElement);
        });
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

    var init = function(formId, hasError) {
        var formElement = $('#' + formId);

        listenForShowHideEvents(formElement);
        destroyOldMoreLessToggle(formElement);
        hideTypeSubSelects(formElement);
        parseGroupSelect(formElement);
        addTypeSelectListeners(formElement);
        addCourseGroupSelectListeners(formElement);

        if (hasError) {
            showAdvancedElements(formElement);
        } else {
            hideAdvancedElements(formElement);
        }
    };

    return {
        init: init,
        events: EVENTS,
    };
});
