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
    'jquery'
],
function(
    $
) {

    var SELECTORS = {
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
        EMPTY_MESSAGE: '[data-region="empty-message-container"]',
        COLLAPSE_REGION: '[data-region="collapse-region"]',
        PLACEHOLDER: '[data-region="placeholder-container"]'
    };

    var startLoading = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopLoading = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var getUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var isVisible = function(root) {
        return root.find(SELECTORS.COLLAPSE_REGION).hasClass('show');
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

    var hideContent = function(root) {
        getContentContainer(root).addClass('hidden');
    };

    var loadAndRender = function(root, loadCallback, renderCallback) {
        startLoading(root);

        return loadCallback(root, getUserId(root))
            .then(function(items) {
                if (items.length > 0) {
                    var contentContainer = getContentContainer(root);
                    return renderCallback(contentContainer, items);
                } else {
                    return showEmptyMessage(root);
                }
            })
            .then(function() {
                stopLoading(root);
                root.attr('data-seen', true);
                return;
            })
            .catch(function() {
                stopLoading(root);
                root.attr('data-seen', true);
                return;
            });
    };

    var initialLoadAndRender = function(root, loadCallback, renderCallback) {
        getContentContainer(root).empty();
        showPlaceholder(root);
        hideContent(root);
        loadAndRender(root, loadCallback, renderCallback)
            .then(function() {
                hidePlaceholder(root);
                showContent(root);
                return;
            })
            .catch(function() {
                hidePlaceholder(root);
                showContent(root);
                return;
            });
    };

    var registerEventListeners = function(root, loadCallback, renderCallback) {
        root.on('show.bs.collapse', function() {
            if (!root.attr('data-seen')) {
                initialLoadAndRender(root, loadCallback, renderCallback);
            }
        });
    };

    var show = function(root, loadCallback, renderCallback) {
        root = $(root);
        root.removeAttr('data-seen');

        if (!root.attr('data-init')) {
            registerEventListeners(root, loadCallback, renderCallback);
            root.attr('data-init', true);
        }

        if (isVisible(root)) {
            initialLoadAndRender(root, loadCallback, renderCallback);
        }
    };

    return {
        show: show
    };
});
