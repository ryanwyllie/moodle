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
 * @module     local_notification/notification
 * @package    local_notification
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
    var NotificationController = function(listElement, countElement) {
        this.listElement = $(listElement);
        this.countElement = $(countElement);
        this.limit = 10;
        this.offset = 0;
        this.notifications = [];
        this.isLoading = false;
        this.loaderElement = this.listElement.find('#notification-loader');
        this.unseenCount = 0;

        this.addListeners();
    };

    NotificationController.prototype.loadNew = function() {
        var controller = this;

        controller.load({limit: controller.limit, offset: controller.offset}).done(function(data) {
            var notificationData = data['notifications'];
            controller.notifications = notificationData.concat(controller.notifications);

            $(notificationData.reverse()).each(function(index, data) {
                templates.render('local_notification/item', data).done(function(html, js) {
                    controller.listElement.prepend(html);
                    // And execute any JS that was in the template.
                    templates.runTemplateJS(js);
                }).fail(notification.exception);
            });
        });
    }

    NotificationController.prototype.loadMore = function() {
        var controller = this;

        controller.load({limit: controller.limit, offset: controller.offset}).done(function(data) {
            var notificationData = data['notifications'];
            controller.notifications = controller.notifications.concat(notificationData);

            $(notificationData).each(function(index, data) {
                templates.render('local_notification/item', data).done(function(html, js) {
                    $(html).insertBefore(controller.loaderElement);
                    // And execute any JS that was in the template.
                    templates.runTemplateJS(js);
                }).fail(notification.exception);
            });
        });
    };

    NotificationController.prototype.load = function(args) {
        var controller = this;
        controller.loading();

        var promises = ajax.call([{
            methodname: 'local_notification_retrieve',
            args: args
        }]);

        promises[0].done(function(data) {
            controller.updateUnseen(data.total_unseen_count);

            controller.notifications = controller.notifications.concat(data['notifications']);

            /**
            // We have the data - lets re-render the template with it.
            templates.render('local_notification/notifications', data).done(function(html, js) {
                $('#notification-list').replaceWith(html);
                // And execute any JS that was in the template.
                templates.runTemplateJS(js);
            }).fail(notification.exception);
            */
            controller.loaded();
        }).fail(notification.exception);

        return promises[0];
    };

    NotificationController.prototype.updateUnseen = function(count) {
        if (this.unseenCount == count) {
            return;
        }

        this.unseenCount = count;

        /**
        if (count > 99) {
            this.countElement.html('99+');
        } else {
            this.countElement.html(count);
        }
        */
    }

    NotificationController.prototype.loading = function() {
        this.isLoading = true;
        this.loaderElement.addClass('loading');
        this.countElement.addClass('loading');
    };

    NotificationController.prototype.loaded = function() {
        this.isLoading = false;
        this.loaderElement.removeClass('loading');
        this.countElement.removeClass('loading');
    };

    NotificationController.prototype.hasLoadedAllNotifications = function() {
        return this.totalCount == this.notifications.length;
    };

    NotificationController.prototype.addListeners = function() {
        var controller = this;

        this.listElement.scroll(function(e) {
            if (!controller.isLoading && !controller.hasLoadedAllNotifications()) {
                if($(this).scrollTop() + $(this).innerHeight() >= this.scrollHeight) {
                    e.preventDefault();
                    controller.loadMore();
                }
            }
        });
    };

    return {
        init: function(listElement, countElement) {
            var controller = new NotificationController(listElement, countElement);
            controller.loadNew();
        },
    };
});
