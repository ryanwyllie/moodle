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

use Mustache_LambdaHelper;
use stdClass;
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

    public function __construct($id, $templatename, $context, moodle_page $page) {
        $this->id = $id;
        $this->templatename = $templatename;
        $this->context = $context;
        $this->page = $page;
    }

    /**
     *
     * @param string $text The text to parse for arguments.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function viewstore($text, Mustache_LambdaHelper $helper) {
        $templatename = $this->templatename;
        $id = "{$this->id}-{$templatename}";
        $jsoncontext = json_encode($this->context);

        $this->page->requires->js_amd_inline("
            require(['core/template_view_store'], function(ViewStore) {
                ViewStore.set(\"{$id}\", {$jsoncontext});
            });
        ");

        return "<div data-view-store-id=\"{$id}\" data-template-name=\"{$templatename}\">{$helper->render($text)}</div>";
    }
}

