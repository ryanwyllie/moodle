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
 * @module     message_popup/message_drawer
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/custom_interaction_events',
    'message_popup/message_drawer_view_contact',
    'message_popup/message_drawer_view_overview',
    'message_popup/message_drawer_view_conversation',
    'message_popup/message_drawer_view_search',
    'message_popup/message_drawer_view_settings',
    'message_popup/message_drawer_router',
    'message_popup/message_drawer_routes'
],
function(
    $,
    CustomEvents,
    ViewContact,
    ViewOverview,
    ViewConversation,
    ViewSearch,
    ViewSettings,
    Router,
    Routes
) {

    var SELECTORS = {
        VIEW_CONTACT: '[data-region="view-contact"]',
        VIEW_CONVERSATION: '[data-region="view-conversation"]',
        VIEW_GROUP_CONVERSATION: '[data-region="view-group-conversation"]',
        VIEW_GROUP_FAVOURITES: '[data-region="view-group-favourites"]',
        VIEW_GROUP_INFO: '[data-region="view-group-info"]',
        VIEW_OVERVIEW: '[data-region="view-overview"]',
        VIEW_REQUESTS: '[data-region="view-requests"]',
        VIEW_SEARCH: '[data-region="view-search"]',
        VIEW_SETTINGS: '[data-region="view-settings"]',
        ROUTES: '[data-route]',
        ROUTES_BACK: '[data-route-back]'
    };

    var createRoutes = function(root) {
        Router.add(Routes.VIEW_CONTACT, root.find(SELECTORS.VIEW_CONTACT), ViewContact.show);
        Router.add(Routes.VIEW_CONVERSATION, root.find(SELECTORS.VIEW_CONVERSATION), ViewConversation.show);
        Router.add(Routes.VIEW_GROUP_CONVERSATION, root.find(SELECTORS.VIEW_GROUP_CONVERSATION));
        Router.add(Routes.VIEW_GROUP_FAVOURITES, root.find(SELECTORS.VIEW_GROUP_FAVOURITES));
        Router.add(Routes.VIEW_GROUP_INFO, root.find(SELECTORS.VIEW_GROUP_INFO));
        Router.add(Routes.VIEW_OVERVIEW, root.find(SELECTORS.VIEW_OVERVIEW), ViewOverview.show);
        Router.add(Routes.VIEW_REQUESTS, root.find(SELECTORS.VIEW_REQUESTS));
        Router.add(Routes.VIEW_SEARCH, root.find(SELECTORS.VIEW_SEARCH), ViewSearch.show);
        Router.add(Routes.VIEW_SETTINGS, root.find(SELECTORS.VIEW_SETTINGS), ViewSettings.show);
    };

    var registerEventListeners = function(root) {
        CustomEvents.define(root, [CustomEvents.events.activate]);

        root.on(CustomEvents.events.activate, SELECTORS.ROUTES, function(e, data) {
            var element = $(e.target).closest(SELECTORS.ROUTES);
            var route = element.attr('data-route');
            var param = element.attr('data-route-param');
            Router.go(route, param);

            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ROUTES_BACK, function(e, data) {
            Router.back();

            data.originalEvent.preventDefault();
        });
    };

    var init = function(root) {
        root = $(root);
        createRoutes(root);
        registerEventListeners(root);
        Router.go(Routes.VIEW_OVERVIEW);
    };

    return {
        init: init,
    };
});
