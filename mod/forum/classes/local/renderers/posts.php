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
 * Posts renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\builders\exported_posts as exported_posts_builder;
use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\sorter as sorter_entity;
use renderer_base;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Posts renderer class.
 */
class posts {
    /** @var discussion_entity $discussion The discussion that the posts belong to */
    private $discussion;
    /** @var forum_entity $forum The forum that the posts belong to */
    private $forum;
    /** @var renderer_base $renderer Renderer base */
    private $renderer;
    /** @var exported_posts_builder $exportedpostsbuilder Builder for building exported posts */
    private $exportedpostsbuilder;
    /** @var sorter_entity $exportedpostsorter Sorter to sort the exported posts */
    private $exportedpostsorter;
    /** @var callable $gettemplate Function to get the template to use for a display mode */
    private $gettemplate;

    /**
     * Constructor.
     *
     * @param discussion_entity $discussion The discussion that the posts belong to
     * @param forum_entity $forum The forum that the posts belong to
     * @param renderer_base $renderer Renderer base
     * @param exported_posts_builder $exportedpostsbuilder Builder for building exported posts
     * @param sorter_entity $exportedpostsorter Sorter to sort the exported posts
     * @param callable $gettemplate Function to get the template to use for a display mode
     */
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

    /**
     * Render the given posts for the forum.
     *
     * @param stdClass $user The user viewing the posts
     * @param post_entity[] $posts The posts to render
     * @param int $displaymode How should the posts be formatted?
     * @return string
     */
    public function render(
        stdClass $user,
        array $posts,
        int $displaymode = null
    ) : string {
        $exportedposts = $this->exportedpostsbuilder->build(
            $user,
            [$this->forum],
            [$this->discussion],
            $posts
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

    /**
     * Add additional values to the exported posts that are required in order
     * to render the posts templates.
     *
     * @param stdClass[] $exportedposts List of posts to process
     * @return stdClass[]
     */
    private function post_process_for_template(array $exportedposts) : array {
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

    /**
     * Sort the list of posts into parents and replies. Each post is given
     * the "replies" property which contains an array of replies to that post.
     * It will also receive a "hasreplies" property which gets set to true if
     * the post has replies, false otherwise.
     *
     * @param stdClass[] $exportedposts List of posts to process
     * @return array
     */
    private function sort_posts_into_replies(array $exportedposts) : array {
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
