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
 * Tabs controller for tabs template.
 *
 * @module     core/tabs
 * @package    core
 * @class      tabs
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
define(['jquery', 'core/str'], function($, str) {

    // Constants
    /** @var {string} default class to use for selected tabs. */
    var DEFAULT_SELECTED_CLASS = 'active';
    /** @var {int} - keycode for left arrow */
    var LEFT_ARROW_KEY = 37;
    /** @var {int} - keycode for right arrow */
    var RIGHT_ARROW_KEY = 39;
    /** @var {int} - keycode for up arrow */
    var UP_ARROW_KEY = 38;
    /** @var {int} - keycode for down arrow */
    var DOWN_ARROW_KEY = 40;
    /** @var {int} - keycode for page up */
    var PAGE_UP_KEY = 33;
    /** @var {int} - keycode for page down */
    var PAGE_DOWN_KEY = 34;
    /** @var {object} jquery selectors for this module */
    var SELECTORS = {
        TAB: '[role="tab"]',
        TAB_LIST: '[role="tablist"]'
    };

    /**
     * Tab class.
     *
     * This class provides encapsulation of a tab and it's components.
     * It will ensure aria attributes are set and provides a simple API
     * to manipulate a Tab without having to access the DOM directly.
     *
     * @var {dom element} The element representing a tab.
     */
    var Tab = function (element) {
        // Public accessible property for the target element.
        this.element = $(element);

        // Only the tab itself should be in the tab index. All
        // child elements should not be tabbable.
        this.element.find('*').each(function(index, child) {
            $(child).attr('tabindex', '-1');
        });

        // Determine the target panel for this tab.
        var panelId = this.element.attr('data-target');

        if (!panelId.match(/^#/)) {
            panelId = '#' + panelId;
        }

        // Keep a reference to this tab's panel.
        this.panel = $(panelId);

        // Look for the class we need to set on the tab when it's selected.
        var selectedClass = this.element.attr('data-selected-class');

        // Fall back to the default, if we can't find one.
        if (!selectedClass) {
            selectedClass = DEFAULT_SELECTED_CLASS;
        }

        // Keep a reference to the class.
        this.selectedClass = selectedClass;

        // Make sure correct aria attribtes are set.
        this.updateAriaAttributes();
    };

    /**
     * Sets the aria attributes to correct values.
     *
     * @public
     * @method updateAriaAttributes
     */
    Tab.prototype.updateAriaAttributes = function () {
        if (this.isSelected()) {
            this.element.attr('tabindex', '0');
        } else {
            this.element.attr('tabindex', '-1');
            this.element.attr('aria-selected', false);
        }

        if (!this.element.attr('aria-controls')) {
            var panelId = this.panel.attr('id');
            this.element.attr('aria-controls', panelId);
        }

        if (!this.panel.attr('role')) {
            this.panel.attr('role', 'tabpanel');
        }
    };

    /**
     * Check if this tab is selected.
     *
     * @public
     * @method isSelected
     * @return {bool}
     */
    Tab.prototype.isSelected = function() {
        return this.element.attr('aria-selected') == "true" ? true : false;
    };

    /**
     * Shows this tab.
     *
     * @public
     * @method show
     */
    Tab.prototype.show = function () {
        if (this.isSelected()) {
            return;
        }

        this.element.attr('aria-selected', true);
        this.element.addClass(this.selectedClass);
        this.panel.addClass(this.selectedClass);
        this.updateAriaAttributes();

        this.element.trigger({
            type: 'shown',
        });
    };

    /**
     * Shows this tab and focuses the element.
     *
     * @public
     * @method focus
     */
    Tab.prototype.focus = function () {
        this.show();
        this.element.focus();
    };

    /**
     * Hides this tab.
     *
     * @public
     * @method hide
     */
    Tab.prototype.hide = function () {
        if (!this.isSelected()) {
            return;
        }

        this.element.attr('aria-selected', false);
        this.element.removeClass(this.selectedClass);
        this.panel.removeClass(this.selectedClass);
        this.updateAriaAttributes();

        this.element.trigger({
            type: 'hidden',
        });
    }; // End Tab class.

    /**
     * TabGroup class.
     *
     * This class models the group/collection of tabs and provides an API
     * to interact with it. It abstracts away the need for other code to
     * understand the underlying DOM structure and provides common list style
     * methods as well as some defaults (such as automatically selecting the
     * first tab, if no other one is selected).
     *
     * This class can be used by other modules wishing to interact with a tab
     * group and is independent from the TabController.
     *
     * @var {dom element} The overall containing elements for the tabs and panels.
     */
    var TabGroup = function (element) {
        /** @var {string} - jquery selector to find tabs */

        /**
         * @var {jquery object} - Internal reference to containing element.
         *
         * All tab calculations are done relative to this element to allow
         * multiple instances of the tabs template and/or nested
         * tabs templates.
         */
        this.container = $(element);

        /**
         * @var {jquery object} - Internal reference to the group container.
         */
        this.listContainer = this.container.find(SELECTORS.TAB_LIST);

        // Show the first tab if there isn't already an active one.
        if (!this.hasSelectedTab()) {
            this.getFirstTab().show();
        }
    };

    /**
     * Finds the tab element within the active list element in the
     * tab list container.
     *
     * @public
     * @method getSelectedTab
     * @return {object} A Tab object or null.
     */
    TabGroup.prototype.getSelectedTab = function () {
        var selectedTab = null;
        this.getTabs().each(function(index, tab) {
            if (tab.isSelected()) {
                selectedTab = tab;
                return false;
            }
        });

        return selectedTab;
    };

    /**
     * Checks if there is currently an active tab within the container.
     *
     * @public
     * @method hasSelectedTab
     * @return {bool}
     */
    TabGroup.prototype.hasSelectedTab = function() {
        return this.getSelectedTab() ? true : false;
    };

    /**
     * Looks for all anchor tags (tabs) within the tab list.
     *
     * @public
     * @method getTabs
     * @return {object[]} A jquery iterable list of Tab objects.
     */
    TabGroup.prototype.getTabs = function () {
        var tabs = [];
        this.listContainer.find(SELECTORS.TAB).each(function(index, domNode) {
            var tab = new Tab(domNode);
            tabs.push(tab);
        });
        return $(tabs);
    };

    /**
     * Looks for all anchor tags (tabs) within the tab list, excluding
     * any selected tabs.
     *
     * @public
     * @method getUnselectedTabs
     * @return {object[]} A jquery iterable list of Tab objects.
     */
    TabGroup.prototype.getUnselectedTabs = function () {
        var results = [];

        this.getTabs().each(function(index, tab) {
            if (!tab.isSelected()) {
                results.push(tab);
            }
        });

        return $(results);
    };

    /**
     * Looks for the tab panel elements within the list of tabs.
     *
     * @public
     * @method getPanels
     * @return {object[]} A jquery iterable list of jquery objects.
     */
    TabGroup.prototype.getPanels = function() {
        var results = [];

        this.getTabs().each(function(index, tab) {
            results.push(tab.panel);
        });

        return $(results);
    };

    /**
     * Get the first tab in the list.
     *
     * @public
     * @method getFirstTab
     * @return {object} A tab instance.
     */
    TabGroup.prototype.getFirstTab = function () {
        return this.getTabs().first()[0];
    };

    /**
     * Get the last tab in the list.
     *
     * @public
     * @method getLastTab
     * @return {object} A Tab instance.
     */
    TabGroup.prototype.getLastTab = function () {
        return this.getTabs().last()[0];
    };

    /**
     * Gets the tab immediately to the left of
     * the current selected tab. If the selected tab is the left most
     * tab in the list then the right most tab is returned.
     *
     * @public
     * @method getPreviousTab
     * @return {object} A Tab instance.
     */
     TabGroup.prototype.getPreviousTab = function () {
        if (!this.hasSelectedTab()) {
            return null;
        }

        var tabs = this.getTabs();
        var prevIndex = null;

        tabs.each(function(index, tab) {
            if (tab.isSelected()) {
                prevIndex = index - 1;
                // Found it. Break early.
                return false;
            }
        });

        // Loop back around if we've hit the end.
        if (prevIndex < 0) {
            prevIndex = tabs.length - 1;
        }

        return tabs[prevIndex];
    };

    /**
     * Gets the tab immediately to the right of the current selected tab.
     * If the selected tab is the right most tab in the list then the
     * left most tab is returned.
     *
     * @public
     * @method getNextTab
     * @return {object} A Tab instance.
     */
    TabGroup.prototype.getNextTab = function () {
        if (!this.hasSelectedTab()) {
            return null;
        }

        var tabs = this.getTabs();
        var nextIndex = null;

        tabs.each(function(index, tab) {
            if (tab.isSelected()) {
                nextIndex = index + 1;
                // Found it. Break early.
                return false;
            }
        });

        // Loop back around if we've hit the end.
        if (nextIndex >= tabs.length) {
            nextIndex = 0;
        }

        return tabs[nextIndex];
    }; // End TabGroup class.

    /**
     * TabController class.
     *
     * This class is responsible for managing the user interaction with
     * the tabs. It will define events and how they are handled.
     *
     * This class may be overriden or replaced if someone wants to change
     * the way users interact with the tabs.
     *
     * @param {object} Root containing element for tabs.
     */
    var TabController = function (container) {
        this.tabGroup = new TabGroup(container);

        // Set up event listeners.
        this.addTabListeners();
        this.addTabPanelListeners();
    };

    /**
     * Checks if (and only if) the control key modifier is set on the
     * given event.
     *
     * @public
     * @method onlyCtrlModifier
     * @param {event} event The event that has occured.
     * @return {bool}
     */
    TabController.prototype.onlyCtrlModifier = function(event) {
        return event.ctrlKey && !event.shiftKey && !event.altKey && !event.metaKey;
    };

    /**
     * Checks if any key modifier has been set on the given event.
     *
     * @public
     * @method hasKeyModifier
     * @param {event} event The event that has occured.
     * @return {bool}
     */
    TabController.prototype.hasKeyModifier = function(event) {
        return event.ctrlKey || event.shiftKey || event.altKey || event.metaKey;
    };

    /**
     * Shows and shifts focus to the next tab.
     *
     * @public
     * @method focusNextTab
     */
    TabController.prototype.focusNextTab = function() {
        var nextTab = this.tabGroup.getNextTab();
        this.tabGroup.getSelectedTab().hide();
        nextTab.focus();
    };

    /**
     * Shows and shifts focus to the previous tab.
     *
     * @public
     * @method focusPreviousTab
     */
    TabController.prototype.focusPreviousTab = function() {
        var prevTab = this.tabGroup.getPreviousTab();
        this.tabGroup.getSelectedTab().hide();
        prevTab.focus();
    };

    /**
     * Create the tab event listeners. The events are handled according
     * to the aria guidelines for navigating tabbed web content.
     *
     * @public
     * @method addTabListeners
     */
    TabController.prototype.addTabListeners = function() {
        var tabs = this.tabGroup.getTabs();
        var controller = this;

        tabs.each(function(index, tab) {
            // Listen for keyboard input. Commands follow the aria
            // guidelines for tabs.
            tab.element.keydown(function(e) {
                if (!controller.hasKeyModifier(e)) {
                    switch(e.which) {
                        case LEFT_ARROW_KEY:
                            e.preventDefault();

                            // Invert controls for RTL languages.
                            if (str.is_rtl_enabled()) {
                                controller.focusNextTab();
                            } else {
                                controller.focusPreviousTab();
                            }

                            break;
                        case RIGHT_ARROW_KEY:
                            e.preventDefault();

                            // Invert controls for RTL languages.
                            if (str.is_rtl_enabled()) {
                                controller.focusPreviousTab();
                            } else {
                                controller.focusNextTab();
                            }

                            break;
                        case UP_ARROW_KEY:
                            e.preventDefault();
                            controller.focusPreviousTab();

                            break;
                        case DOWN_ARROW_KEY:
                            e.preventDefault();
                            controller.focusNextTab();

                            break;
                    }
                }
            });

            // Listen for clicks on the tabs.
            tab.element.click(this, function(e) {
                e.preventDefault();
                var clickedTab = e.data;

                controller.tabGroup.getSelectedTab().hide();
                clickedTab.show();
            });
        });
    };

    /**
     * Create the tab panel event listeners. The events are
     * handled according to the aria guidelines for navigating
     * tabbed web content.
     *
     * @public
     * @method addTabPanelListeners
     */
    TabController.prototype.addTabPanelListeners = function() {
        var panels = this.tabGroup.getPanels();
        var controller = this;

        panels.each(function(index, panel) {
            // Listen for keyboard input when focus is inside a tab panel.
            panel.keydown(function(e) {

                // Make sure control is the only modifier key pressed.
                if (controller.onlyCtrlModifier(e)) {

                    switch(e.which) {
                        // Ctrl + Up arrow.
                        case UP_ARROW_KEY:
                            e.preventDefault();
                            var tab = controller.tabGroup.getSelectedTab();
                            tab.focus();

                            break;
                        // Ctrl + Page Up.
                        case PAGE_UP_KEY:
                            e.preventDefault();
                            controller.focusPreviousTab();

                            break;
                        // Ctrl + Page Down.
                        case PAGE_DOWN_KEY:
                            e.preventDefault();
                            controller.focusNextTab();

                            break;
                    }
                }
            });
        });
    }; // End TabController class.

    return {
        // @alias module:core/templates

        // Public variables and functions.

        /**
         * Set up the controller for the given containing element. Creates
         * the relevent event listeners and actives a default tab, if
         * required.
         *
         * @method create
         * @public
         * @param {object} containing element for tabbed content.
         * @param {string} class to be set when tab is selected.
         */
        create: function(containerElement) {
            // All tab calculations are relative to the container element.
            new TabController(containerElement);
        }
    };
});
