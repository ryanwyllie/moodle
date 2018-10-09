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
 * @module     message_popup/message_drawer
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/pubsub',
    'message_popup/message_drawer_events'
],
function(
    $,
    PubSub,
    MessageDrawerEvents
) {

    var routes = {};
    var history = [];

    var add = function(route, elements, onGo) {
        routes[route] = {
            elements: elements,
            onGo: onGo
        };
    };

    var goInSecret = function(newRoute) {
        var newConfig;
        // Get the rest of the arguments, if any.
        var args = [].slice.call(arguments, 1);

        Object.keys(routes).forEach(function(route) {
            var config = routes[route];

            if (route === newRoute) {
                newConfig = config;
            }

            config.elements.forEach(function(element) {
                $(element).addClass('hidden');
            });
        });

        if (newConfig) {
            var elements = newConfig.elements.map(function(element) {
                return $(element);
            });
            elements.forEach(function(element) {
                element.removeClass('hidden');
            });

            if (newConfig.onGo) {
                newConfig.onGo.apply(undefined, elements.concat(args));
            }
        }

        var record = {
            route: newRoute,
            params: args
        };

        PubSub.publish(MessageDrawerEvents.ROUTE_CHANGED, record);

        return record;
    }

    var go = function() {
        var record = goInSecret.apply(null, arguments);
        var previousRecord = history.length ? history[history.length - 1] : null;

        if (previousRecord) {
            if (previousRecord.route === record.route) {
                if (previousRecord.params.length === record.params.length) {
                    paramsMatch = previousRecord.params.every(function(param, index) {
                        return param === record.params[index];
                    });

                    if (paramsMatch) {
                        return record;
                    }
                }
            }
        }

        history.push(record);
        return record;
    };


    var back = function() {
        if (history.length) {
            // Remove the current route.
            history.pop();
            var previous = history.pop();

            if (previous) {
                // If we have a previous route then show it.
                go.apply(undefined, [previous.route].concat(previous.params));
            }
        }
    };

    return {
        add: add,
        go: go,
        goInSecret: goInSecret,
        back: back
    };
});
