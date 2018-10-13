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
    'core_message/message_drawer_view_contact',
    'core_message/message_drawer_view_contacts',
    'core_message/message_drawer_view_overview',
    'core_message/message_drawer_view_conversation',
    'core_message/message_drawer_view_search',
    'core_message/message_drawer_view_settings',
    'core_message/message_drawer_router',
    'core_message/message_drawer_routes',
    'core_message/message_drawer_events'
],
function(
    $,
    CustomEvents,
    PubSub,
    ViewContact,
    ViewContacts,
    ViewOverview,
    ViewConversation,
    ViewSearch,
    ViewSettings,
    Router,
    Routes,
    Events
) {

    var SELECTORS = {
        VIEW_CONTACT: '[data-region="view-contact"]',
        VIEW_CONTACTS: '[data-region="view-contacts"]',
        VIEW_CONVERSATION: '[data-region="view-conversation"]',
        VIEW_OVERVIEW: '[data-region="view-overview"]',
        VIEW_SEARCH: '[data-region="view-search"]',
        VIEW_SETTINGS: '[data-region="view-settings"]',
        ROUTES: '[data-route]',
        ROUTES_BACK: '[data-route-back]',
        HEADER_CONTAINER: '[data-region="header-container"]',
        BODY_CONTAINER: '[data-region="body-container"]',
        FOOTER_CONTAINER: '[data-region="footer-container"]',
    };

    /**
     * Get elements for route.
     *
     * @param {Object} root The message drawer container.
     * @param {string} selector The route container.
     *
     * @return {array} elements Found route container objects.
    */
    var getElementsForRoute = function(root, selector) {
        var candidates = root.children();
        var header = candidates.filter(SELECTORS.HEADER_CONTAINER).find(selector);
        var body = candidates.filter(SELECTORS.BODY_CONTAINER).find(selector);
        var footer = candidates.filter(SELECTORS.FOOTER_CONTAINER).find(selector);
        var elements = [header, body, footer].filter(function(element) {
            return element.length;
        });

        return elements;
    };

    /**
     * Create routes.
     *
     * @param {Object} root The message drawer container.
     */
    var createRoutes = function(root) {
        Router.add(Routes.VIEW_CONTACT, getElementsForRoute(root, SELECTORS.VIEW_CONTACT), ViewContact.show);
        Router.add(Routes.VIEW_CONTACTS, getElementsForRoute(root, SELECTORS.VIEW_CONTACTS), ViewContacts.show);
        Router.add(Routes.VIEW_CONVERSATION, getElementsForRoute(root, SELECTORS.VIEW_CONVERSATION), ViewConversation.show);
        Router.add(Routes.VIEW_OVERVIEW, getElementsForRoute(root, SELECTORS.VIEW_OVERVIEW), ViewOverview.show);
        Router.add(Routes.VIEW_SEARCH, getElementsForRoute(root, SELECTORS.VIEW_SEARCH), ViewSearch.show);
        Router.add(Routes.VIEW_SETTINGS, getElementsForRoute(root, SELECTORS.VIEW_SETTINGS), ViewSettings.show);
    };

    /**
     * Show the message drawer.
     *
     * @param {Object} root The message drawer container.
     */
    var show = function(root) {
        if (!root.attr('data-shown')) {
            Router.go(Routes.VIEW_OVERVIEW);
            root.attr('data-shown', true);
        }

        root.removeClass('hidden');
        root.attr('aria-expanded', true);
        root.attr('aria-hidden', false);
    };

    /**
     * Hide the message drawer.
     *
     * @param {Object} root The message drawer container.
     */
    var hide = function(root) {
        root.addClass('hidden');
        root.attr('aria-expanded', false);
        root.attr('aria-hidden', true);
    };

    /**
     * Listen to and handle events for routing, showing and hiding the message drawer.
     *
     * @param {Object} root The message drawer container.
     */
    var registerEventListeners = function(root) {
        CustomEvents.define(root, [CustomEvents.events.activate]);
        var paramRegex = /^data-route-param-?(\d*)$/;

        root.on(CustomEvents.events.activate, SELECTORS.ROUTES, function(e, data) {
            var element = $(e.target).closest(SELECTORS.ROUTES);
            var route = element.attr('data-route');
            var skipHistory = element.attr('data-route-skip-history') !== undefined;
            var attributes = [];

            for (var i = 0; i < element[0].attributes.length; i++) {
                attributes.push(element[0].attributes[i]);
            }

            var paramAttributes = attributes.filter(function(attribute) {
                var name = attribute.nodeName;
                var match = paramRegex.test(name);
                return match;
            });
            paramAttributes.sort(function(a, b) {
                var aParts = paramRegex.exec(a.nodeName);
                var bParts = paramRegex.exec(b.nodeName);
                var aIndex = aParts.length > 1 ? aParts[1] : 0;
                var bIndex = bParts.length > 1 ? bParts[1] : 0;

                if (aIndex < bIndex) {
                    return -1;
                } else if (bIndex < aIndex) {
                    return 1;
                } else {
                    return 0;
                }
            });

            var params = paramAttributes.map(function(attribute) {
                return attribute.nodeValue;
            });
            var routeParams = [route].concat(params);

            if (skipHistory) {
                Router.goInSecret.apply(null, routeParams);
            } else {
                Router.go.apply(null, routeParams);
            }

            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ROUTES_BACK, function(e, data) {
            Router.back();

            data.originalEvent.preventDefault();
        });

        PubSub.subscribe(Events.SHOW, function() {
            show(root);
        });

        PubSub.subscribe(Events.HIDE, function() {
            hide(root);
        });
    };

    /**
     * Initialise the message drawer.
     *
     * @param {Object} root The message drawer container.
     */
    var init = function(root) {
        root = $(root);
        createRoutes(root);
        registerEventListeners(root);
    };

    return {
        init: init,
    };
});
