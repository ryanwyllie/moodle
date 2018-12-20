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
 * Nested discussion renderer.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\serializers\serializer_interface;
use mod_forum\local\vaults\post as post_vault;
use context;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Nested discussion renderer class.
 */
class discussion {
    private $renderer;
    private $displaymode;
    private $template;
    private $orderby;
    private $canrendercallback;
    private $serializer;
    private $postvault;

    public function __construct(
        renderer_base $renderer,
        serializer_interface $serializer,
        // TODO: change this to an interface dependency.
        post_vault $postvault,
        int $displaymode,
        string $template,
        string $orderby = 'created ASC',
        callable $validaterendercallback = null
    ) {
        $this->renderer = $renderer;
        $this->displaymode = $displaymode;
        $this->template = $template;
        $this->orderby = $orderby;
        $this->serializer = $serializer;
        $this->postvault = $postvault;

        if (is_null($validaterendercallback)) {
            $validaterendercallback = function($context, $discussion) {
                // No errors.
                return;
            };
        }

        $this->validaterendercallback = $validaterendercallback;
    }

    public function render(context $context, forum_entity $forum, discussion_entity $discussion) : string {
        global $USER;

        // Make sure we can render.
        $this->validate_render($context, $forum, $discussion);

        $posts = $this->postvault->get_from_discussion_id($discussion->get_id(), $this->orderby);
        $serializeddiscussion = $this->serializer->for_display($USER, $context, $forum, $discussion, $posts);
        $serializeddiscussion = array_merge($serializeddiscussion, [
            'modeselectorform' => forum_print_mode_form($discussion->get_id(), $this->displaymode)
        ]);

        return $this->renderer->render_from_template($this->template, $serializeddiscussion);
    }

    private function validate_render(context $context, forum_entity $forum, discussion_entity $discussion) {
        ($this->validaterendercallback)($context, $forum, $discussion);
    }
}
