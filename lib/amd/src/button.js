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
 * Utility functions for buttons.
 *
 * @module     core/button
 * @package    core
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    /**
     * Change the visuals and properties of a button to indicate that an
     * asynchronous action is occurring.
     *
     * Returns a function to be executed when the asynchronous action has
     * completed. The returned function will revert the visual changes made
     * to the button.
     *
     * @param {Object} button The button that triggered the async action
     * @return {Function}
     */
    var async = function(button) {
        button = $(button);
        button.addClass('loading disabled');
        button.prop('disabled', true);
        button.attr('aria-disabled', true);
        var existingAriaLabel = button.attr('aria-label');
        var hasExistingAriaLabel = existingAriaLabel !== undefined;
        var ariaLabelLoading = button.attr('data-aria-label-loading');
        var hasAriaLabelLoading = ariaLabelLoading !== undefined;
        var hasBtnAsyncClass = button.hasClass('btn-async');

        if (hasAriaLabelLoading) {
            button.attr('aria-label', ariaLabelLoading);
        }

        if (!hasBtnAsyncClass) {
            button.addClass('btn-async');
        }

        // Return a function that reverts these changes to be called when
        // the async action has completed.
        return function() {
            button.removeClass('loading disabled');
            button.prop('disabled', false);
            button.removeAttr('aria-disabled');

            if (hasExistingAriaLabel) {
                button.attr('aria-label', existingAriaLabel);
            } else {
                button.removeAttr('aria-label');
            }

            if (!hasBtnAsyncClass) {
                button.removeClass('btn-async');
            }
        };
    };

    return {
        async: async
    };
});
