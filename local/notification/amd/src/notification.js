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
    var NotificationController = function(rootElement) {
        this.elements = {
            root: $(rootElement)
        };
        this.elements.list = this.elements.root.find('[role="menu-items"]');
        this.elements.iconContainer = this.elements.root.find('.menu-icon-container');
        this.elements.loader = this.elements.list.find('[role="menu-loading-item"]');
        this.elements.count = this.elements.iconContainer.find('[role="menu-item-count"]');
        this.limit = 10;
        this.offset = 0;
        this.notifications = [];
        this.isLoading = false;
        this.unseenCount = 0;

        this.addListeners();
    };

    NotificationController.prototype.loadNew = function() {
        var controller = this;

        controller.load({limit: controller.limit, offset: controller.notifications.length}).done(function(data) {
            var notificationData = data['notifications'];
            controller.notifications = notificationData.concat(controller.notifications);

            $(notificationData.reverse()).each(function(index, data) {
                templates.render('local_notification/item', data).done(function(html, js) {
                    controller.elements.list.prepend(html);
                    // And execute any JS that was in the template.
                    templates.runTemplateJS(js);
                }).fail(notification.exception);
            });
        });
    }

    NotificationController.prototype.loadMore = function() {
        var controller = this;

        controller.load({limit: controller.limit, offset: controller.notifications.length}).done(function(data) {
            var notificationData = data['notifications'];
            controller.notifications = controller.notifications.concat(notificationData);

            $(notificationData).each(function(index, data) {
                templates.render('local_notification/item', data).done(function(html, js) {
                    $(html).insertBefore(controller.elements.loader);
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
            methodname: 'local_notification_query',
            args: args
        }]);

        promises[0].done(function(data) {
            controller.updateUnseen(data.total_unseen_count);
            controller.totalCount = data.total_count;
            controller.loaded();
        }).fail(notification.exception);

        return promises[0];
    };

    NotificationController.prototype.updateUnseen = function(count) {
        if (this.unseenCount == count) {
            return;
        }

        this.unseenCount = count;
        var element = this.elements.count;

        if (count <= 0) {
            element.removeClass('visible');
        } else if (count > 99) {
            element.html('99+');
            element.addClass('visible');
        } else {
            element.html(count);
            element.addClass('visible');
        }
    }

    NotificationController.prototype.loading = function() {
        this.isLoading = true;
        this.elements.root.addClass('loading');
    };

    NotificationController.prototype.loaded = function() {
        this.isLoading = false;
        this.elements.root.removeClass('loading');
    };

    NotificationController.prototype.hasLoadedAllNotifications = function() {
        return this.notifications.length >= this.totalCount;
    };

    NotificationController.prototype.addListeners = function() {
        var controller = this;

        // Disable browser scrolling
        $('.scrollable').on('DOMMouseScroll mousewheel', function(ev) {
            var $this = $(this),
                scrollTop = this.scrollTop,
                scrollHeight = this.scrollHeight,
                height = $this.height(),
                delta = (ev.type == 'DOMMouseScroll' ?
                    ev.originalEvent.detail * -40 :
                    ev.originalEvent.wheelDelta),
                up = delta > 0;

            var prevent = function() {
                ev.stopPropagation();
                ev.preventDefault();
                ev.returnValue = false;
                return false;
            }

            if (!up && -delta > scrollHeight - height - scrollTop) {
                // Scrolling down, but this will take us past the bottom.
                $this.scrollTop(scrollHeight);

                return prevent();
            } else if (up && delta > scrollTop) {
                // Scrolling up, but this will take us past the top.
                $this.scrollTop(0);
                return prevent();
            }
        });

        this.elements.list.scroll(function(e) {
            if (!controller.isLoading && !controller.hasLoadedAllNotifications()) {
                if($(this).scrollTop() + $(this).innerHeight() >= this.scrollHeight) {
                    e.preventDefault();
                    controller.loadMore();
                }
            }
        });
    };

    return {
        init: function(rootElement) {
            var controller = new NotificationController(rootElement);
            controller.loadNew();
        },
    };
});
