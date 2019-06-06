<?php
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
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

use Mustache_Engine;
use Mustache_LambdaHelper;
use moodle_page;

/**
 *
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mustache_view_store_helper {

    private $id = null;
    private $templatename = null;
    private $context = null;
    private $page = null;
    private $mustache = null;

    public function __construct($id, $templatename, $context, moodle_page $page, Mustache_Engine $mustache) {
        $this->id = $id;
        $this->templatename = $templatename;
        $this->context = $context;
        $this->page = $page;
        $this->mustache = $mustache;
    }

    /**
     *
     * @param string $text The text to parse for arguments.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function viewstore($text, Mustache_LambdaHelper $helper) {
        $mustache = $this->mustache;
        $templatename = $this->templatename;
        $id = "{$this->id}-{$templatename}";
        $jsoncontext = json_encode($this->context);
        // We're going to save all of the child javascript from templates so that we can
        // wrap it. This means we need to override the original JS helper so let's save a copy
        // of it to restore after we're finished with our custom JS helper.
        $originaljshelper = $mustache->getHelper('uniqid');
        $childjavascript = '';

        // Add a custom JS helper which just saves all of the javascript into an array for us
        // so that we can wrap it for the store.
        $mustache->addHelper('js', function($javascript, $lambdahelper) use (&$childjavascript) {
            $childjavascript .= $lambdahelper->render($javascript);
        });

        $html = "<div data-component-id=\"{$id}\" data-template-name=\"{$templatename}\">{$helper->render($text)}</div>";

        // Restore the original JS helper so that any other template rendering occurs as normal.
        $mustache->addHelper('js', $originaljshelper);

        $this->page->requires->js_amd_inline("
            require(
            [
                'core/mobx',
                'core/react',
                'core/react-dom',
                'core/mustache_component',
                'core/templates'
            ],
            function(
                MobX,
                React,
                ReactDOM,
                MustacheComponent,
                Templates
            ) {
                var viewState = MobX.observable({$jsoncontext});
                var element = document.querySelector('[data-component-id=\"{$id}\"]');
                var templateName = element.getAttribute('data-template-name');
                viewState = Templates.addHelpers(viewState);

                Templates.getTemplateSource(templateName).then(function(templateSource) {
                    ReactDOM.render(
                        React.createElement(MustacheComponent, {template: templateSource, context: viewState}, null),
                        element
                    );
                });

                (function() {
                    function getViewState() {
                        return viewState;
                    };

                    function getRootElement() {
                        return element;
                    };

                    {$childjavascript}
                })();
            });
        ");

        return $html;
    }
}

