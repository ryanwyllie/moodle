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
 * Potential user selector module.
 *
 * @module     tool_dataprivacy/expand_contract
 * @class      page-expand-contract
 * @package    tool_dataprivacy
 * @copyright  2018 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/url', 'core/str'], function($, url, str) {

    /** {object} filterlist An object that represents all plugin types and components in the system.  */
    var filterlist;

    /**
     * {object} currentlist An object that represents the filtered (via filters and search box) list
     * of plugin types and component.
     */
    var currentlist;

    /** {integer} searchlength The length of the string in the searchbox. */
    var searchlength = 0;

    /** {integer} filterlength The number of filters currently active. */
    var filterlength = 0;

    /** {object} expandedImage The image node (clean) for the expanded image. */
    var expandedImage = $('<img alt="" src="' + url.imageUrl('t/expanded') + '"/>');

    /** {object} collapsedImage The image node (clean) for the collapsed image. */
    var collapsedImage = $('<img alt="" src="' + url.imageUrl('t/collapsed') + '"/>');

    /**
     * Update the page to show or hide the relevant nodes.
     */
    var updatedisplay = function(filterlist, currentlist) {
        // Check each component and see if it is already hidden. Hide if necessary.
        var pluginstohide = $.extend(true, {}, filterlist);
        if (Object.keys(filterlist).length != Object.keys(currentlist).length || searchlength > 0) {
            for (var key in currentlist) {
                delete pluginstohide[key];
            }

            // Hide all plugins.
            for (var key in pluginstohide) {
                // Collapse previously expanded areas.
                var node = $('[data-plugintarget="' + key + '"]');
                node.addClass('hide');
                node.attr('aria-expanded', false);
                $('[data-plugin="' + key + '"]').addClass('hide');
            }

            // Expand and unhide the remaining plugins.
            for (var key in currentlist) {
                $('[data-plugin="' + key + '"]').removeClass('hide');
                var node = $('[data-plugintarget="' + key + '"]');
                node.attr('aria-expanded', true);
                if (currentlist[key].plugins.length == 0) {
                    node.attr('aria-expanded', false);
                    node.addClass('hide');
                    // Set children to unhidden.
                    node.children().each(function() {
                        $(this).removeClass('hide');
                    });
                } else {
                    node.removeClass('hide');
                    // Hide all components then just unhide from our list.
                    node.children().each(function() {
                        $(this).addClass('hide');
                    });
                }

                currentlist[key].plugins.forEach(function(plugin) {
                    $('[data-id="' + plugin.raw_component + '"]').removeClass('hide');
                });
            }
        } else {
            // Reset back to normal.
            resetNodes(filterlist);
        }
    };

    /**
     * Reset the all of the nodes back to the original state.
     */
    var resetNodes = function(filterlist) {
        for (var key in filterlist) {
            var node = $('[data-plugintarget="' + key + '"]');
            node.addClass('hide');
            node.attr('aria-expanded', false);
            $('[data-plugin="' + key + '"]').removeClass('hide');
        }
    };

    /**
     * Hides nodes and also changes the icons to collapsed.
     *
     * @param  {object} basenode The node to hide.
     * @param  {object} parentnode The parent node to use to find the appropriate icons to collapse.
     */
    var hidenode = function(basenode, parentnode) {
        basenode.addClass('hide');
        basenode.attr('aria-expanded', false);
        parentnode.find(':header i.fa').removeClass('fa-minus-square');
        parentnode.find(':header i.fa').addClass('fa-plus-square');
        parentnode.find(':header img.icon').attr('src', collapsedImage.attr('src'));
    };

    /**
     * Shows nodes and also changes the icons to expand.
     *
     * @param  {object} basenode The node to show.
     * @param  {object} parentnode The parent node to use to find the appropriate icons to expand.
     */
    var shownode = function(basenode, parentnode) {
        basenode.removeClass('hide');
        basenode.attr('aria-expanded', true);
        parentnode.find(':header i.fa').removeClass('fa-plus-square');
        parentnode.find(':header i.fa').addClass('fa-minus-square');
        parentnode.find(':header img.icon').attr('src', expandedImage.attr('src'));
    };

    var getApiIssueFilter = function() {
        return function(item) {
            return (item.hasOwnProperty('compliant') && item.compliant) ? false : true;
        }
    };

    var getAdditionalFilter = function() {
        return function(item) {
            return (item.hasOwnProperty('external')) ? true : false;
        }
    };

    var getTextFilter = function(text) {
        return function(item) {
            if (item.hasOwnProperty('plugin_type')) {
                if (item.plugin_type.toLowerCase().indexOf(text) >= 0) {
                    return true;
                }
            }

            if (item.hasOwnProperty('component')) {
                if (item.component.toLowerCase().indexOf(text) >= 0) {
                    return true;
                }
            }

            return false;
        }
    };

    return /** @alias module:tool_dataprivacy/expand-collapse */ {
        /**
         * Initialises The page to be ready for returning a filtered list.
         *
         * @param  {object} data All of the plugin and component information.
         */
        init: function(data) {
            filterlist = data;
            currentlist = $.extend(true, {}, filterlist);
        },

        /**
         * Expand or collapse a selected node.
         *
         * @param  {object} thisnode The node that was clicked.
         */
        expandCollapse: function(thisnode) {
            // Section -- Attempt to open section with filtering.
            var partiallyopen = false;
            var section;
            if (thisnode.attr('data-plugin')) {
                var tname = thisnode.attr('data-plugin');
                section = $('[data-plugintarget="' + tname + '"]');
                var pluginchildren = section.children();
                var childcount = pluginchildren.length;
                var hidecount = childcount;
                pluginchildren.each(function() {
                    hidecount = ($(this).hasClass('hide')) ? hidecount - 1 : hidecount;
                    $(this).removeClass('hide');
                });
                partiallyopen = (childcount !== hidecount) ? true : false;
            }

            if (typeof (section) !== 'undefined') {

                if (!partiallyopen && section.attr('aria-expanded') == 'true') {
                    hidenode(section, thisnode);
                } else {
                    shownode(section, thisnode);
                }
            }

            // Move onto child nodes.
            if (thisnode.attr('data-component')) {
                var componentname = thisnode.attr('data-component');
                var infosection = $('[data-section="' + componentname + '"]');
                if (infosection.attr('aria-expanded') == 'false') {
                    // Open up the section.
                    shownode(infosection, thisnode);
                } else {
                    // Hide the section.
                    hidenode(infosection, thisnode);
                }
            }
        },

        /**
         * Expand or collapse all nodes on this page.
         *
         * @param  {string} nextstate The next state to change to.
         */
        expandCollapseAll: function(nextstate) {

            var showall = (nextstate == 'visible') ? true : false;
            var state = (nextstate == 'visible') ? 'hide' : 'visible';

            $('.tool_dataprivacy-element').each(function() {
                $(this).addClass(nextstate);
                $(this).removeClass(state);
                $(this).attr('aria-expanded', showall);
            });

            $('.tool_dataprivacy-expand-all').attr('data-visibility-state', state);

            str.get_string(nextstate, 'tool_dataprivacy').then(function(langString) {
                $('.tool_dataprivacy-expand-all').html(langString);
                return;
            }).catch(Notification.exception);

            var iconclassnow = (nextstate == 'visible') ? 'fa-plus-square' : 'fa-minus-square';
            var iconclassnext = (nextstate == 'visible') ? 'fa-minus-square' : 'fa-plus-square';
            var imagenow = (nextstate == 'visible') ? expandedImage.attr('src') : collapsedImage.attr('src');

            $(':header i.fa').each(function() {
                $(this).removeClass(iconclassnow);
                $(this).addClass(iconclassnext);
            });
            $(':header img.icon').each(function() {
                $(this).attr('src', imagenow);
            });
        },

        buildFilterFunction: function(root) {
            var filters = [];

            var apiElement = root.find('[data-type="api-issue"]');
            var additionalElement = root.find('[data-type="additional"]');
            var searchElement = root.find('input[type="text"]');

            if (apiElement.prop('checked')) {
                filters.push(getApiIssueFilter());
            }

            if (additionalElement.prop('checked')) {
                filters.push(getAdditionalFilter());
            }

            var text = searchElement.val();
            if (text) {
                filters.push(getTextFilter(text));
            }

            if (filters.length) {
                return function(item) {
                    for (var i = 0; i < filters.length; i++) {
                        var filter = filters[0];
                        if (filter(item)) {
                            return true;
                        }
                    }

                    return false;
                }
            } else {
                return function() {
                    return true;
                }
            }
        },

        /**
         * Applies the selected filters to the plugin list.
         */
        filterList: function(filterFunction, list) {
            return list.reduce(function(carry, item) {
                var filteredPlugins = item.plugins.filter(filterFunction);

                if (filteredPlugins.length) {
                    var newItem = $.extend({}, item);
                    newItem.plugins = filteredPlugins;
                    return carry.concat([newItem]);
                }

                if (filterFunction(item)) {
                    var newItem = $.extend({}, item);
                    return carry.concat([newItem]);
                };

                return carry;
            }, []);
        },

        /**
         * Expands node when following a hyper link.
         *
         * @param  {string} link The hyperlink that we are following.
         */
        followLink: function(link) {
            var linkContainer = $('[data-id="' + link + '"]');
            linkContainer.removeClass('hide');
            var linkNode = $('[data-section="' + link + '"]');
            linkNode.removeClass('hide');
            linkNode.attr('aria-expanded', true);
            var parentNode = linkNode.parents('[data-plugintarget]');
            parentNode.removeClass('hide');
            parentNode.addClass('done');
            parentNode.attr('aria-expanded', true);
            parentNode.parent().removeClass('hide');
        },

        /**
         * Searches the plugin list for a string matching the search box. This is compared against the plugin type and
         * component name.
         */
        search: function() {
            var searchText = $('.tool_dataprivacy-search-box').val();

            /**
             * Returns an array of plugins matching the search criteria.
             * 
             * @param  {object} plugins The plugins to filter on
             * @return {array} An array of plugins after they have been filtered.
             */
            var getmatchingplugins = function(plugins) {
                var matchingplugins = [];
                plugins.forEach(function(plugin) {
                    var componentstring = plugin.component.toLowerCase();
                    if (componentstring.indexOf(searchText.toLowerCase()) !== -1) {
                        // Add to the matching list.
                        matchingplugins.push(plugin);
                    }
                });
                return matchingplugins;
            };

            if (searchlength > searchText.length) {
                currentlist = $.extend(true, {}, filterlist);
            }
             searchlength = searchText.length;

            for (var key in currentlist) {
                var matchingplugins = getmatchingplugins(currentlist[key].plugins);
                currentlist[key].plugins = matchingplugins;
                var pluginstring = currentlist[key].plugin_type.toLowerCase();
                if (pluginstring.indexOf(searchText.toLowerCase()) == -1 && matchingplugins.length == 0) {
                    delete currentlist[key];
                }
            }
            updatedisplay();
        },

        updatedisplay: updatedisplay
    };
});
