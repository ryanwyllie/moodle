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
 * This file contains main class for the course format Fancy
 *
 * @copyright 2019 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/course/format/lib.php");

use format_fancy\local\renderers\nav_bar as nav_bar_renderer;
use renderer_base;

/**
 * Main class for the Fance course format
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_fancy extends format_base {
    public function extend_course_navigation($navigation, navigation_node $node) {
        return;
    }

    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => []
        ];
    }
}

/**
 * Renders the popup.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function format_fancy_render_navbar_output(renderer_base $renderer) {
    global $PAGE, $USER;

    // Have to use the global $PAGE because the page object in the renderer
    // is protected. Sigh...
    $courseformat = course_get_format($PAGE->course);

    if (!($courseformat instanceof format_fancy)) {
        // Not viewing a format fancy course so do nothing.
        return '';
    }

    $navbarrenderer = new nav_bar_renderer($renderer);
    return $navbarrenderer->render($PAGE, $USER);
}
