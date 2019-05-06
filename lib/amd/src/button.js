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
define(['jquery', 'core/templates'], function($, Templates) {
    var LOADING_ICON_TEMPLATE = 'core/overlay_loading';
    var SELECTORS = {
        OVERLAY_ICON_CONTAINER: '[data-region="overlay-icon-container"]',
        ICON: '.icon'
    };

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
        var hasAddedIcon = false;
        var shouldAddIcon = true;
        button = $(button);
        button.addClass('disabled');
        button.prop('disabled', true);
        button.attr('aria-disabled', true);

        Templates.render(LOADING_ICON_TEMPLATE, {visible: true}).then(function(html) {
            if (shouldAddIcon) {
                // Only add the icon if the done callback hasn't been called yet.
                var textColour = button.css('color');
                html = $(html);
                html.css('background-color', 'transparent');
                var icon = html.find(SELECTORS.ICON);
                icon.css('height', 'auto');
                icon.css('width', 'auto');
                icon.css('font-size', 'inherit');
                icon.css('color', textColour);

                button.css('position', 'relative');
                button.css('color', 'transparent');
                button.append(html);
                hasAddedIcon = true;
            }

            return html;
        });

        var existingAriaLabel = button.attr('aria-label');
        var hasExistingAriaLabel = existingAriaLabel !== undefined;
        var ariaLabelLoading = button.attr('data-aria-label-loading');
        var hasAriaLabelLoading = ariaLabelLoading !== undefined;

        if (hasAriaLabelLoading) {
            button.attr('aria-label', ariaLabelLoading);
        }

        // Return a function that reverts these changes to be called when
        // the async action has completed.
        return function() {
            if (!hasAddedIcon) {
                // If the done function has executed before the loading icon
                // has been added to the DOM then skip adding it.
                shouldAddIcon = false;
            }

            if (hasAddedIcon) {
                // If we've added the loading icon to the DOM then remove the
                // custom styling we added and remove the icon.
                button.css('position', '');
                button.css('color', '');
                button.find(SELECTORS.OVERLAY_ICON_CONTAINER).remove();
            }

            button.removeClass('disabled');
            button.prop('disabled', false);
            button.removeAttr('aria-disabled');

            if (hasExistingAriaLabel) {
                button.attr('aria-label', existingAriaLabel);
            } else {
                button.removeAttr('aria-label');
            }
        };
    };

    return {
        async: async
    };
});
