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
 * This is an empty module, that is required before all other modules.
 * Because every module is returned from a request for any other module, this
 * forces the loading of all modules with a single request.
 *
 * @module     local_notification/refresh
 * @package    local_notification
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
    function loadNotifications(limit, offset, notifications) {
        var promises = ajax.call([{
            methodname: 'local_notification_retrieve',
            args:{limit: limit, offset: offset}
        }]);

        promises[0].done(function(data) {
            notifications = data['notifications'] = notifications.concat(data['notifications']);
            offset += limit;

            // We have the data - lets re-render the template with it.
            templates.render('local_notification/notifications', data).done(function(html, js) {
                $('#notification-list').replaceWith(html);
                // And execute any JS that was in the template.
                templates.runTemplateJS(js);
            }).fail(notification.exception);
        }).fail(notification.exception);

        return promises[0];
    }

    return /** @alias module:local_notification/notification */ {
        /**
         * Refresh the middle of the page!
         *
         * @method refresh
         */
        init: function(limit, offset, notifications) {
            // Disable more button while  we do first load
            $('#more').prop('disabled', true);

            // load first set of notifications
            loadNotifications(limit, offset, notifications).done(function() {
                $('#more').prop('disabled', false);
            });

            $('.notification').on('click', function() {
                $( this ).removeClass('unseen');
            });

            $('#more').on('click', function() {
                $('#more').prop('disabled', true);
                loadNotifications(limit, offset, notifications).done(function() {
                    $('#more').prop('disabled', false);
                });
            });
        },
    };
});
