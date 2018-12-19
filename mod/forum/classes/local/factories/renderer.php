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
 * Vault factory.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\factories;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion;
use mod_forum\local\renderers\discussion as discussion_renderer;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Vault factory.
 */
class renderer {
    public static function get_discussion_renderer(
        discussion $discussion,
        int $displaymode,
        \renderer_base $renderer
    ) {
        switch ($displaymode) {
            case FORUM_MODE_FLATOLDEST:
                return new discussion_renderer(
                    $renderer,
                    'mod_forum/forum_discussion_flat_posts',
                    'created DESC'
                );
            case FORUM_MODE_FLATNEWEST:
                return new discussion_renderer(
                    $renderer,
                    'mod_forum/forum_discussion_flat_posts',
                    'created DESC'
                );
            case FORUM_MODE_THREADED:
                return new discussion_renderer(
                    $renderer,
                    'mod_forum/forum_discussion_threaded_posts',
                    'created ASC'
                );
            case FORUM_MODE_NESTED:
                return new discussion_renderer(
                    $renderer,
                    'mod_forum/forum_discussion_nested_posts',
                    'created ASC'
                );
            default;
                return new discussion_renderer(
                    $renderer,
                    'mod_forum/forum_discussion_nested_posts',
                    'created ASC'
                );
        }
    }
}
