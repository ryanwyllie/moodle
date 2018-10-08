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
    'core/templates'
],
function(
    $,
    Templates
) {

    var SELECTORS = {
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        CONTENT_CONTAINER: '[data-region="contacts-content-container"]',
        EMPTY_MESSAGE: '[data-region="empty-message-container"]',
        PLACEHOLDER: '[data-region="placeholder-container"]'
    };


    var TEMPLATES = {
        CONTACTS_LIST: 'message_popup/message_drawer_contacts_list'
    };

    var startLoading = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopLoading = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var showEmptyMessage = function(root) {
        getContentContainer(root).addClass('hidden');
        root.find(SELECTORS.EMPTY_MESSAGE).removeClass('hidden');
    };

    var showPlaceholder = function(root) {
        root.find(SELECTORS.PLACEHOLDER).removeClass('hidden');
    };

    var hidePlaceholder = function(root) {
        root.find(SELECTORS.PLACEHOLDER).addClass('hidden');
    };

    var showContent = function(root) {
        getContentContainer(root).removeClass('hidden');
    };

    var render = function(root, viewState) {
        hidePlaceholder(root);
        var contentContainer = getContentContainer(root);
        if (viewState.contacts.length > 0) {
            return Templates.render(TEMPLATES.CONTACTS_LIST, {contacts: viewState.contacts})
                .then(function(html) {
                    contentContainer.append(html);
                    showContent(root);
                    stopLoading(root);
                })
                .catch(Notification.exception);
        } else {
            stopLoading(root);
            return showEmptyMessage(root);
        }
    };

    return {
        render: render,
        showPlaceholder: showPlaceholder
    };
});
