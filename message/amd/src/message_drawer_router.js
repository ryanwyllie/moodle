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
 * @module     core_message/message_drawer
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/pubsub',
    'core_message/message_drawer_events'
],
function(
    $,
    PubSub,
    MessageDrawerEvents
) {

    /* @var {object} routes Message drawer route elements and callbacks. */
    var routes = {};

    /* @var {array} history Store for route objects history. */
    var history = [];

    /**
     * Add a route.
     *
     * @param {string} route Route config name.
     * @param {array} elements Route container objects.
     * @param {callback} onGo Route initialization function.
     */
    var add = function(route, elements, onGo) {
        routes[route] = {
            elements: elements,
            onGo: onGo
        };
    };

    /**
     * Go to a defined route and run the route callbacks.
     *
     * @param {string} newRoute Route config name.
     * @return {object} record Current route record with route config name and parameters.
     */
    var goInSecret = function(newRoute) {
        var newConfig;
        // Get the rest of the arguments, if any.
        var args = [].slice.call(arguments, 1);

        Object.keys(routes).forEach(function(route) {
            var config = routes[route];
            var isMatch = route === newRoute;

            if (isMatch) {
                newConfig = config;
            }

            config.elements.forEach(function(element) {
                element.removeClass('previous');

                if (isMatch) {
                    element.removeClass('hidden');
                } else {
                    element.addClass('hidden');
                }
            });
        });

        if (newConfig) {
            if (newConfig.onGo) {
                newConfig.onGo.apply(undefined, newConfig.elements.concat(args));
            }
        }

        var record = {
            route: newRoute,
            params: args
        };

        PubSub.publish(MessageDrawerEvents.ROUTE_CHANGED, record);

        return record;
    };

    /**
     * Go to a defined route and store the route history.
     *
     * @return {object} record Current route record with route config name and parameters.
     */
    var go = function() {
        var record = goInSecret.apply(null, arguments);
        var previousRecord = history.length ? history[history.length - 1] : null;

        if (previousRecord) {
            if (previousRecord.route === record.route) {
                if (previousRecord.params.length === record.params.length) {
                    var paramsMatch = previousRecord.params.every(function(param, index) {
                        return param === record.params[index];
                    });

                    if (paramsMatch) {
                        return record;
                    }
                }
            }

            routes[previousRecord.route].elements.forEach(function(element) {
                element.addClass('previous');
            });
        }

        history.push(record);
        console.log('HISTORY', history);
        return record;
    };

    /**
     * Go back to the previous route record stored in history.
     */
    var back = function() {
        if (history.length) {
            // Remove the current route.
            var current = history.pop();
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
