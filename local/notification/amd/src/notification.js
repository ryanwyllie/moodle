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
        this.elements.menuToggle = this.elements.root.find('[data-toggle="dropdown"]');
        this.limit = 10;
        this.offset = 0;
        this.notifications = [];
        this.isLoading = false;
        this.unseenCount = 0;

        this.addListeners();
    };

    NotificationController.prototype.getUnseenNotifications = function() {
        return this.notifications.filter(function(notification, index, array) {
            return notification.seen ? false : true;
        });
    };

    NotificationController.prototype.getNotificationById = function(id) {
        return this.notifications.filter(function(notification, index, array) {
            return notification.id == id;
        })[0];
    }

    NotificationController.prototype.loadNew = function() {
        var controller = this;

        controller.load({limit: controller.limit, offset: controller.notifications.length}).done(function(data) {
            var notificationData = data['notifications'];
            controller.notifications = notificationData.concat(controller.notifications);

            var promises = [];
            var renderedTemplates = [];

            $(notificationData.reverse()).each(function(index, data) {
                var templateData = {};
                // Need to make a copy because template renderer pollutes the object
                // with properties.
                $.extend(templateData, data);

                var renderer = templates.render('local_notification/item', templateData);
                renderer.done(function(html, js) {
                    renderedTemplates[index] = { html: html, js: js, id: templateData.id};
                }).fail(notification.exception);

                promises.push(renderer);
            });

            // Have to wait for all promises to resolve in order to guarantee
            // item ordering.
            $.when.apply($, promises).done(function() {
                $(renderedTemplates).each(function(index, data) {
                    var html = data.html;
                    var js = data.js;
                    var id = data.id;
                    var element = $(html);
                    controller.elements.list.prepend(element);
                    element.data('notification-id', id);
                    controller.addNotificationClickHandler(element);
                    // And execute any JS that was in the template.
                    templates.runTemplateJS(js);
                });
            });
        });
    }

    NotificationController.prototype.loadMore = function() {
        var controller = this;

        controller.load({limit: controller.limit, offset: controller.notifications.length}).done(function(data) {
            var notificationData = data['notifications'];
            controller.notifications = controller.notifications.concat(notificationData);
            // Any newly loaded ones with the menu open are considered "seen".
            controller.updateUnseen();

            var promises = [];
            var renderedTemplates = [];

            $(notificationData).each(function(index, data) {
                var templateData = {};
                // Need to make a copy because template renderer pollutes the object
                // with properties.
                $.extend(templateData, data);

                var renderer = templates.render('local_notification/item', templateData);
                renderer.done(function(html, js) {
                    renderedTemplates[index] = { html: html, js: js, id: templateData.id};
                }).fail(notification.exception);

                promises.push(renderer);
            });

            // Have to wait for all promises to resolve in order to guarantee
            // item ordering.
            $.when.apply($, promises).done(function() {
                $(renderedTemplates).each(function(index, data) {
                    var html = data.html;
                    var js = data.js;
                    var id = data.id;
                    var element = $(html);
                    var element = $(html);
                    element.insertBefore(controller.elements.loader);
                    element.data('notification-id', id);
                    controller.addNotificationClickHandler(element);
                    // And execute any JS that was in the template.
                    templates.runTemplateJS(js);
                });
            });
        });
    };

    NotificationController.prototype.load = function(args) {
        var controller = this;
        controller.loading();

        var promises = ajax.call([{
            methodname: 'local_notifications_query',
            args: args
        }]);

        promises[0].done(function(data) {
            controller.updateUnseenCount(data.total_unseen_count);
            controller.totalCount = data.total_count;
            controller.loaded();
        }).fail(notification.exception);

        return promises[0];
    };

    NotificationController.prototype.updateUnseenCount = function(count) {
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
    };

    NotificationController.prototype.updateUnseen = function() {
        var controller = this;
        var unseenNotifications = this.getUnseenNotifications();

        $(unseenNotifications).each(function(index, notification) {
            notification.seen = true;
        });

        var promises = ajax.call([{
            methodname: 'local_notifications_update',
            args: { notifications: unseenNotifications }
        }]);

        promises[0].done(function(data) {
            controller.updateUnseenCount(data.total_unseen_count);
            controller.totalCount = data.total_count;
        }).fail(notification.exception);
    };

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

    NotificationController.prototype.addNotificationClickHandler = function(element) {
        var controller = this;

        element.click(function(e) {
            var id = element.data('notification-id');

            if (id == null) {
                return;
            }

            var notification = controller.getNotificationById(id);
            notification.actioned = true;

            var promises = ajax.call([{
                methodname: 'local_notifications_update',
                args: { notifications: [notification] }
            }]);

            promises[0].done(function(data) {
                controller.updateUnseenCount(data.total_unseen_count);
                controller.totalCount = data.total_count;
                element.removeClass('highlight');
            }).fail(notification.exception);
        });
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

        this.elements.menuToggle.click(function(e) {
            controller.updateUnseen();
        });
    };

    return {
        init: function(rootElement) {
            var controller = new NotificationController(rootElement);
            controller.loadNew();
        },
    };
});
