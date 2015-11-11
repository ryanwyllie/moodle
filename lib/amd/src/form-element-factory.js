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
 * Standard Ajax wrapper for Moodle. It calls the central Ajax script,
 * which can call any existing webservice using the current session.
 * In addition, it can batch multiple requests and return multiple responses.
 *
 * @module     core/form-element-factory
 * @class      ajax
 * @package    core
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
define(['jquery', 'core/templates', 'core/ajax'], function($, templates, ajax) {
    var COMPONENT = 'core';
    var TEMPLATE_PREFIX = 'form_element_';

    var getTemplateName = function(element) {
        var elementType = element.attr('data-mdl-is');

        if (!elementType) {
            var result = /^mdl-(.+)$/i.exec(element[0].nodeName);
            elementType = result[1].toLowerCase().replace('-', '_');
        }

        if (!elementType) {
            return null;
        } else {
            return COMPONENT + '/' + TEMPLATE_PREFIX + elementType;
        }
    };

    var getContext = function(element) {
        var context = {};

        $.each(element[0].attributes, function(index, attribute) {
            context[attribute.name] = attribute.value;
        });

        return context;
    };

    return {
        create: function(elementName) {

        },

        createAndReplaceFromElement: function(element) {
            element = $(element);
            var templateName = getTemplateName(element);

            if (!templateName) {
                return null;
            }

            var context = getContext(element);

            templates.render(templateName, context).done(function(html, js) {
                templates.replaceNode(element, html, js);
            });
        }
    };
});
