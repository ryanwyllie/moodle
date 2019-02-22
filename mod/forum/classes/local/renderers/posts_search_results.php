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

use mod_forum\local\builders\exported_posts as exported_posts_builder;
use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\post_read_receipt_collection as read_receipt_collection_entity;
use renderer_base;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Posts renderer class.
 */
class posts {
    private $discussion;
    private $forum;
    private $renderer;
    private $exportedpostsbuilder;

    public function __construct(
        discussion_entity $discussion,
        forum_entity $forum,
        renderer_base $renderer,
        exported_posts_builder $exportedpostsbuilder
    ) {
        $this->discussion = $discussion;
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->exportedpostsbuilder = $exportedpostsbuilder;
    }

    public function render(
        stdClass $user,
        array $posts,
        read_receipt_collection_entity $readreceiptcollection = null
    ) : string {
        $exportedposts = $this->exportedpostsbuilder->build($user, $posts, $readreceiptcollection);

        return $this->renderer->render_from_template(
            ($this->gettemplate)($displaymode),
            ['posts' => $exportedposts]
        );
    }
}
