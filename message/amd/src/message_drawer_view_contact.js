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
 * @module     core_message/message_drawer_view_overview
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
    Repository
) {

    var SELECTORS = {
        CONTENT_CONTAINER: '[data-region="content-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        LOADING_PLACEHOLDER_CONTAINER: '[data-region="loading-placeholder-container"]',
        TEXT_CONTAINER: '[data-region="button-text"]'
    };

    var TEMPLATES = {
        CONTENT: 'core_message/message_drawer_view_contact_body_content'
    };

    /**
     * Get the current logged in userid.
     *
     * @param {Object} root Contact container element.
     * @return {Number} The logged in userid.
     */
    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    /**
     * Get the content container of the contact view container.
     *
     * @param {Object} root Contact container element.
     */
    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    /**
     * Loading actions.
     *
     * @param {Object} root Contact container element.
     */
    var showLoadingIcon = function(root) {
        root.prop('disabled', true);
        root.find(SELECTORS.TEXT_CONTAINER).addClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    /**
     * Stop loading actions.
     *
     * @param {Object} root Contact container element.
     */
    var hideLoadingIcon = function(root) {
        root.prop('disabled', false);
        root.find(SELECTORS.TEXT_CONTAINER).removeClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    /**
     * Show loading placeholder.
     *
     * @param {Object} root Contact container element.
     */
    var showLoadingPlaceholder = function(root) {
        root.find(SELECTORS.LOADING_PLACEHOLDER_CONTAINER).removeClass('hidden');
    };

    /**
     * Hide loading placeholder.
     *
     * @param {Object} root Contact container element.
     */
    var hideLoadingPlaceholder = function(root) {
        root.find(SELECTORS.LOADING_PLACEHOLDER_CONTAINER).addClass('hidden');
    };

    /**
     * Start loading the contact page.
     *
     * @param {Object} root Contact container element.
     */
    var startFullLoading = function(root) {
        showLoadingIcon(root);
        showLoadingPlaceholder(root);
    };

    /**
     * Stop loading the contact page.
     *
     * @param {Object} root Contact container element.
     */
    var stopFullLoading = function(root) {
        hideLoadingIcon(root);
        hideLoadingPlaceholder(root);
    };

    /**
     * Load the contact info.
     *
     * @param {Object} root Contact container element.
     * @param {Number} contactUserId Contact user id.
     */
    var load = function(root, contactUserId) {
        var loggedInUserId =  getLoggedInUserId(root);
        return Repository.getProfile(loggedInUserId, contactUserId);
    };

    /**
     * Render the contact profile in the content container.
     *
     * @param {Object} root Contact container element.
     * @param {Object} profile Contact profile details.
     */
    var render = function(root, profile) {
        return Templates.render(TEMPLATES.CONTENT, profile)
            .then(function(html) {
                getContentContainer(root).append(html);
            });
    };

    /**
     * Setup the contact page.
     *
     * @param {Object} root Contact container element.
     * @param {Number} contactUserId The contact userid.
     */
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
