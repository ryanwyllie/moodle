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
 * Controls the message drawer.
 *
 * @module     core_message/message_drawer
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/custom_interaction_events',
    'core/pubsub',
    'core_message/message_drawer_events'
],
function(
    $,
    CustomEvents,
    PubSub,
    MessageDrawerEvents
) {
    var showMessageDrawer = function() {
        PubSub.publish(MessageDrawerEvents.SHOW);
    };

    var hideMessageDrawer = function() {
        PubSub.publish(MessageDrawerEvents.HIDE);
    };

    var registerEventListeners = function(root, isShown) {
        CustomEvents.define(root, [CustomEvents.events.activate]);

        root.on(CustomEvents.events.activate, function(e, data) {

            if (isShown) {
                hideMessageDrawer();
            } else {
                showMessageDrawer();
            }

            isShown = !isShown;
            data.originalEvent.preventDefault();
        });
    };

    var init = function(root, isShown) {
        root = $(root);
        registerEventListeners(root, isShown);

        if (isShown) {
            showMessageDrawer();
        }
    };

    return {
        init: init,
    };
});
