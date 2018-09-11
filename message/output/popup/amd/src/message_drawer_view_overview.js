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
 * @module     message_popup/message_drawer_view_overview
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/custom_interaction_events',
    'message_popup/message_drawer_view_overview_contacts',
    'message_popup/message_router',
    'message_popup/message_routes'
],
function(
    $,
    CustomEvents,
    Contacts,
    Router,
    Routes
) {

    var SELECTORS = {
        CONTACTS: '[data-region="view-overview-contacts"]'
    };

    var registerEventListeners = function(root) {
        CustomEvents.define(root, [CustomEvents.events.activate]);

        root.on(CustomEvents.events.activate, '[data-action="requests"]', function(e, data) {
            Router.go(Routes.VIEW_REQUESTS);
            data.originalEvent.preventDefault();
        });
    };

    var show = function(root) {
        if (!root.attr('data-seen')) {
            registerEventListeners(root);
            root.attr('data-seen', true);
        }

        Contacts.show(root.find(SELECTORS.CONTACTS));
    };

    return {
        show: show,
    };
});
