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
    'core/notification',
    'core/templates',
    'core_message/message_repository',
],
function(
    $,
    Notification,
    Templates,
    Repository,
) {

    var SELECTORS = {
        CONTENT_CONTAINER: '[data-region="content-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        LOADING_PLACEHOLDER_CONTAINER: '[data-region="loading-placeholder-container"]',
        TEXT_CONTAINER: '[data-region="button-text"]'
    };

    var TEMPLATES = {
        CONTENT: 'message_popup/message_drawer_view_contact_body_content'
    };

    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var showLoadingIcon = function(root) {
        root.prop('disabled', true);
        root.find(SELECTORS.TEXT_CONTAINER).addClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var hideLoadingIcon = function(root) {
        root.prop('disabled', false);
        root.find(SELECTORS.TEXT_CONTAINER).removeClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var showLoadingPlaceholder = function(root) {
        root.find(SELECTORS.LOADING_PLACEHOLDER_CONTAINER).removeClass('hidden');
    };

    var hideLoadingPlaceholder = function(root) {
        root.find(SELECTORS.LOADING_PLACEHOLDER_CONTAINER).addClass('hidden');
    };

    var startFullLoading = function(root) {
        showLoadingIcon(root);
        showLoadingPlaceholder(root);
    };

    var stopFullLoading = function(root) {
        hideLoadingIcon(root);
        hideLoadingPlaceholder(root);
    };

    var load = function(root, contactUserId) {
        var loggedInUserId =  getLoggedInUserId(root);
        return Repository.getProfile(loggedInUserId, contactUserId);
    };

    var render = function(root, profile) {
        return Templates.render(TEMPLATES.CONTENT, profile)
            .then(function(html) {
                getContentContainer(root).append(html);
            });
    };

    var show = function(root, contactUserId) {
        root = $(root);

        getContentContainer(root).empty();
        startFullLoading(root);
        load(root, contactUserId)
            .then(function(profile) {
                stopFullLoading(root);
                return render(root, profile);
            })
            .catch(function(error) {
                Notification.exception(error);
                stopFullLoading(root);
            });
    };

    return {
        show: show,
    };
});
