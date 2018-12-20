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
use mod_forum\local\factories\vault as vault_factory;

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

    public function __construct(
        \renderer_base $renderer,
        int $displaymode,
        string $template,
        string $orderby = 'created ASC',
        callable $validaterendercallback = null
    ) {
        $this->renderer = $renderer;
        $this->displaymode = $displaymode;
        $this->template = $template;
        $this->orderby = $orderby;

        if (is_null($validaterendercallback)) {
            $validaterendercallback = function($context, $discussion) {
                // No errors.
                return;
            };
        }

        $this->validaterendercallback = $validaterendercallback;
    }

    public function render(\context $context, forum_entity $forum, discussion_entity $discussion) : string {
        // Make sure we can render.
        $this->validate_render($context, $forum, $discussion);

        $postvault = vault_factory::get_post_vault();
        $posts = $postvault->get_from_discussion_id($discussion->get_id(), $this->orderby);
        $sortedposts = $this->sort_posts_into_replies($posts);
        $formattedposts = array_map(function($sortedpost) {
            list($post, $replies) = $sortedpost;
            return $this->transform_posts_to_template_object($post, $replies);
        }, $sortedposts);

        return $this->renderer->render_from_template($this->template, [
            'modeselectorform' => forum_print_mode_form($discussion->get_id(), $this->displaymode),
            'posts' => $formattedposts
        ]);
    }

    private function validate_render(\context $context, forum_entity $forum, discussion_entity $discussion) {
        ($this->validaterendercallback)($context, $forum, $discussion);
    }

    private function sort_posts_into_replies($posts) : array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);

        list($parents, $replies) = array_reduce($posts, function($carry, $post) use ($postids) {
            $parentid = $post->get_parent_id();

            if (in_array($parentid, $postids)) {
                // This post is a reply to another post in the list so add it to the replies list.
                $carry[1][] = $post;
            } else {
                // This post isn't replying to anything in our list so it's a parent.
                $carry[0][] = $post;
            }

            return $carry;
        }, [[], []]);

        if (empty($replies)) {
            return array_map(function($parent) {
                return [$parent, []];
            }, $parents);
        }

        $sortedreplies = $this->sort_posts_into_replies($replies);

        return array_map(function($parent) use ($sortedreplies) {
            return [
                $parent,
                array_filter($sortedreplies, function($replydata) use ($parent) {
                    return $replydata[0]->get_parent_id() == $parent->get_id();
                })
            ];
        }, $parents);
    }

    private function transform_posts_to_template_object(post_entity $post, array $replies) : array {
        $author = $post->get_author();

        return [
            'id' => $post->get_id(),
            'subject' => $post->get_subject(),
            'message' => format_text($post->get_message(), $post->get_message_format()),
            'author' => [
                'id' => $author->get_id(),
                'fullname' => $author->get_full_name(),
                'profileurl' => $author->get_profile_url()->out(false),
                'profileimageurl' => $author->get_profile_image_url()->out(false),
            ],
            'timecreated' => $post->get_time_created(),
            'replies' => array_map(function($replydata) {
                list($reply, $replyreplies) = $replydata;
                return $this->transform_posts_to_template_object($reply, $replyreplies);
            }, $replies)
        ];
    }
}
