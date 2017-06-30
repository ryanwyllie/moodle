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
 * Sets up HTML5 form validation for a given form.
 *
 * @module     core/html5_form_validator
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    var SELECTORS = {
        DEFAULT_ELEMENT: '[data-has-validation]'
    };

    var EVENTS = {
        VALID: 'html5-valid',
        INVALID: 'html5-invalid'
    };

    var isValid = function(formElement, elementSelector) {
        formElement = $(formElement);

        if (typeof elementSelector == 'undefined') {
            elementSelector = SELECTORS.DEFAULT_ELEMENT;
        }

        var isValid = true;

        formElement.find(elementSelector).each(function(index, element) {
            element = $(element);

            if (!element[0].checkValidity()) {
                isValid = false;
                // Trigger a custom event because the invalid event fired
                // by checkValidity doesn't bubble.
                element.trigger(EVENTS.INVALID);
            } else {
                element.trigger(EVENTS.VALID);
            }
        });

        return isValid;
    };

    var validateOnSubmit = function(formElement, elementSelector) {
        formElement = $(formElement);

        if (typeof elementSelector == 'undefined') {
            elementSelector = SELECTORS.DEFAULT_ELEMENT;
        }

        formElement.submit(function(e) {
            var isValid = isValid(formElement, elementSelector);

            if (!isValid) {
                e.preventDefault();
            }
        });
    };

    var validateOnBlur = function(formElement, elementSelector) {
        formElement = $(formElement);

        if (typeof elementSelector == 'undefined') {
            elementSelector = SELECTORS.DEFAULT_ELEMENT;
        }

        formElement.on('blur', elementSelector, function(e) {
            var element = $(e.target).closest(elementSelector);

            if (element[0].checkValidity()) {
                element.trigger(EVENTS.VALID);
            } else {
                // Trigger a custom event because the invalid event fired
                // by checkValidity doesn't bubble.
                element.trigger(EVENTS.INVALID);
            }
        });
    };

    return /** @module core/html5_form_validator */ {
        isValid: isValid,
        validateOnSubmit: validateOnSubmit,
        validateOnBlur: validateOnBlur,
        events: EVENTS
    };
});
