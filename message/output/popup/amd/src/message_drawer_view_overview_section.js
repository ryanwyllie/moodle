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

    var showEmptyMessage = function(root) {
        getContentContainer(root).addClass('hidden');
        root.find(SELECTORS.EMPTY_MESSAGE).removeClass('hidden');
    };

    var registerEventListeners = function(root, loadCallback, renderCallback) {
        root.on('show.bs.collapse', function() {
            if (!root.attr('data-seen')) {
                startLoading(root);

                loadCallback(root, getUserId(root))
                    .then(function(items) {
                        if (items.length > 0) {
                            return renderCallback(root, items);
                        } else {
                            return showEmptyMessage(root);
                        }
                    })
                    .then(function() {
                        stopLoading(root);
                        return;
                    })
                    .catch(function() {
                        stopLoading(root);
                        return;
                    });

                root.attr('data-seen', true);
            }
        });
    };

    var show = function(root, loadCallback, renderCallback) {
        root = $(root);
        if (!root.attr('data-init')) {
            registerEventListeners(root, loadCallback, renderCallback);
            root.attr('data-init', true);
        }
    };

    return {
        show: show
    };
});
