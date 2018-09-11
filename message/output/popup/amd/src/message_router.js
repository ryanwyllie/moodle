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
    'jquery'
],
function(
    $
) {

    var routes = {};
    var history = [];

    var add = function(route, element, onGo) {
        routes[route] = {
            element: element,
            onGo: onGo
        };
    };

    var go = function(newRoute) {
        var newConfig;
        var args = [].slice.call(arguments);

        Object.keys(routes).forEach(function(route) {
            var config = routes[route];

            if (route === newRoute) {
                newConfig = config;
            }

            $(config.element).addClass('hidden');
        });

        if (newConfig) {
            var element = $(newConfig.element);
            element.removeClass('hidden');

            if (newConfig.onGo) {
                args.unshift(element);
                newConfig.onGo.apply(undefined, args);
            }
        }

        history.push(newRoute);
    };

    var back = function() {
        if (history) {
            // Remove the current route.
            history.pop();
            var previousRoute = history.pop();

            if (previousRoute) {
                // If we have a previous route then show it.
                go(previousRoute);
            }
        }
    };

    return {
        add: add,
        go: go,
        back: back
    };
});
