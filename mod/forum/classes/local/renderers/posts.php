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
use mod_forum\local\entities\sorter as sorter_entity;
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
    private $exportedpostsorter;
    private $gettemplate;

    public function __construct(
        discussion_entity $discussion,
        forum_entity $forum,
        renderer_base $renderer,
        exported_posts_builder $exportedpostsbuilder,
        sorter_entity $exportedpostsorter,
        callable $gettemplate
    ) {
        $this->discussion = $discussion;
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->exportedpostsbuilder = $exportedpostsbuilder;
        $this->exportedpostsorter = $exportedpostsorter;
        $this->gettemplate = $gettemplate;
    }

    public function render(
        stdClass $user,
        array $posts,
        read_receipt_collection_entity $readreceiptcollection = null,
        int $displaymode = null
    ) : string {
        $exportedposts = $this->exportedpostsbuilder->build(
            $user,
            [$this->forum],
            [$this->discussion],
            $posts,
            $readreceiptcollection
        );
        $exportedposts = $this->post_process_for_template($exportedposts);

        if ($displaymode === FORUM_MODE_NESTED || $displaymode === FORUM_MODE_THREADED) {
            $exportedposts = $this->sort_posts_into_replies($exportedposts);
        } else if ($displaymode === FORUM_MODE_FLATNEWEST || $displaymode === FORUM_MODE_FLATOLDEST) {
            $exportedfirstpost = array_shift($exportedposts);
            $exportedfirstpost->replies = $exportedposts;
            $exportedfirstpost->hasreplies = true;
            $exportedposts = [$exportedfirstpost];
        }

        return $this->renderer->render_from_template(
            ($this->gettemplate)($displaymode),
            ['posts' => $exportedposts]
        );
    }

    private function post_process_for_template(array $exportedposts) {
        $forum = $this->forum;
        $seenfirstunread = false;
        return array_map(
            function($exportedpost) use ($forum, $seenfirstunread) {
                if ($forum->get_type() == 'single' && !$exportedpost->hasparent) {
                    // Remove the author from any posts that don't have a parent.
                    unset($exportedpost->author);
                }

                $exportedpost->hasreplies = false;
                $exportedpost->replies = [];

                $exportedpost->isfirstunread = false;
                if (!$seenfirstunread && $exportedpost->unread) {
                    $exportedpost->isfirstunread = true;
                    $seenfirstunread = true;
                }

                return $exportedpost;
            },
            $exportedposts
        );
    }

    private function sort_posts_into_replies(array $exportedposts) {
        $sortedposts = $this->exportedpostsorter->sort_into_children($exportedposts);
        $sortintoreplies = function($nestedposts) use (&$sortintoreplies) {
            return array_map(function($postdata) use (&$sortintoreplies) {
                [$post, $replies] = $postdata;
                $post->replies = $sortintoreplies($replies);
                $post->hasreplies = !empty($post->replies);
                return $post;
            }, $nestedposts);
        };

        return $sortintoreplies($sortedposts);
    }
}
