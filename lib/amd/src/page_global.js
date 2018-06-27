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
 * Global JavaScript that should be run on all pages in Moodle.
 *
 * @module     core/page_global
 * @package    core
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/custom_interaction_events',
],
function(
    $,
    CustomEvents
) {

    var initDropdownHandler = function() {
        var body = $('body');
        
        CustomEvents.define(body, [CustomEvents.events.activate]);
        body.on(CustomEvents.events.activate, '[data-show-active-item]', function(e) {
            // The dropdown item that the user clicked on.
            var option = $(e.target);
            // The dropdown menu element.
            var menuContainer = option.closest('[data-show-active-item]');

            if (!option.hasClass('dropdown-item')) {
                // Ignore non Bootstrap dropdowns.
                return;
            }

            if (option.hasClass('active')) {
                // If it's already active then we don't need to do anything.
                return;
            }

            // Clear the active class from all other options.
            menuContainer.find('.dropdown-item').removeClass('active');
            
            if (!menuContainer.attr('data-skip-active-class')) {
                // Make this option active unless configured to ignore it.
                // Some code, for example the Bootstrap tabs, may want to handle
                // adding the active class itself.
                option.addClass('active');
            }
            
            var activeOptionText = option.text();
            var dropdownToggle = menuContainer.parent().find('[data-toggle="dropdown"]');
            var dropdownToggleText = dropdownToggle.find('[data-active-item-text]');

            if (dropdownToggleText.length) {
                // We have a specific placeholder for the active item text so
                // use that.
                dropdownToggleText.html(activeOptionText);
            } else {
                // Otherwise just replace all of the toggle text with the active item.
                dropdownToggle.html(activeOptionText);
            }
        });
    };

    var init = function() {
        initDropdownHandler();
    };
    
    return {
        init: init
    }
});
