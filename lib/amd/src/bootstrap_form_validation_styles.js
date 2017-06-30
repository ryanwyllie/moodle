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
 * Apply the appropriate Bootstrap styles for form validation.
 *
 * @module     core/bootstrap_form_validation_styles
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    var DEFAULT_INDICATE_VALID = false;

    var init = function(formElement, invalidEventName, validEventName, indicateValid) {
        if (typeof indicateValid == 'undefined') {
            indicateValid = DEFAULT_INDICATE_VALID;
        }

        if (typeof invalidEventName != 'undefined') {
            formElement.on(invalidEventName, function(e) {
                var invalidElement = $(e.target);
                invalidElement.parent()
                    .removeClass('has-success')
                    .addClass('has-danger');

                invalidElement.removeClass('form-control-success');

                if (invalidElement.hasClass('form-control')) {
                    invalidElement.addClass('form-control-danger');
                }
            });
        }

        if (typeof validEventName != 'undefined') {
            formElement.on(validEventName, function(e) {
                var validElement = $(e.target);
                var parent = validElement.parent();
                parent.removeClass('has-danger');

                validElement.removeClass('form-control-success');

                if (indicateValid) {
                    parent.addClass('has-success');

                    if (validElement.hasClass('form-control')) {
                        validElement.addClass('form-control-success');
                    }
                }
            });
        }
    };

    return /** @module core/bootstrap_form_validation_styles */ {
        init: init,
    };
});
