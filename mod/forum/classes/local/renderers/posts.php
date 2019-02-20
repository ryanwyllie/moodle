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
use mod_forum\local\entities\post_read_receipt_collection as read_receipt_collection_entity;
use mod_forum\local\entities\sorter as sorter_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use core\output\notification;
use context;
use context_module;
use core_tag_tag;
use html_writer;
use moodle_exception;
use moodle_page;
use moodle_url;
use rating_manager;
use renderer_base;
use single_button;
use single_select;
use stdClass;
use url_select;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Posts renderer class.
 */
class posts {
    private $discussion;
    private $forum;
    private $renderer;
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $ratingmanager;
    private $exportedpostsorter;

    public function __construct(
        discussion_entity $discussion,
        forum_entity $forum,
        renderer_base $renderer,
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        rating_manager $ratingmanager,
        sorter_entity $exportedpostsorter
    ) {
        $this->discussion = $discussion;
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->ratingmanager = $ratingmanager;
        $this->exportedpostsorter = $exportedpostsorter;
    }

    public function render(
        stdClass $user,
        int $displaymode,
        post_entity $firstpost,
        array $replies,
        ?read_receipt_collection_entity $readreceiptcollection
    ) : string {
        $posts = array_merge([$firstpost], array_values($replies));
        $forum = $this->forum;
        $discussion = $this->discussion;
        $authorsbyid = $this->get_authors_for_posts($posts);
        $attachmentsbypostid = $this->get_attachments_for_posts($posts);
        $groupsbyauthorid = $this->get_author_groups_from_posts($posts);
        $tagsbypostid = $this->get_tags_from_posts($posts);
        $ratingbypostid = $forum->has_rating_aggregate() ? $this->get_ratings_from_posts($user, $posts) : null;
        $postsexporter = $this->exporterfactory->get_posts_exporter(
            $user,
            $forum,
            $discussion,
            $posts,
            $authorsbyid,
            $attachmentsbypostid,
            $groupsbyauthorid,
            $readreceiptcollection,
            $tagsbypostid,
            $ratingbypostid,
            true
        );
        ['posts' => $exportedposts] = (array) $postsexporter->export($this->renderer);
        $seenfirstunread = false;
        $exportedposts = array_map(function($exportedpost) use ($forum, $seenfirstunread) {
            if ($forum->get_type() == 'single' && !$exportedpost->hasparent) {
                // Remove the author from any posts that don't have a parent.
                unset($exportedpost->author);
            }

            $exportedpost->replies = [];

            $exportedpost->isfirstunread = false;
            if (!$seenfirstunread && $exportedpost->unread) {
                $exportedpost->isfirstunread = true;
                $seenfirstunread = true;
            }

            return $exportedpost;
        }, $exportedposts);

        if ($displaymode === FORUM_MODE_NESTED || $displaymode === FORUM_MODE_THREADED) {
            $sortedposts = $this->exportedpostsorter->sort_into_children($exportedposts);
            $sortintoreplies = function($nestedposts) use (&$sortintoreplies) {
                return array_map(function($postdata) use (&$sortintoreplies) {
                    [$post, $replies] = $postdata;
                    $post->replies = $sortintoreplies($replies);
                    return $post;
                }, $nestedposts);
            };

            $exportedposts = $sortintoreplies($sortedposts);
        } else {
            $exportedfirstpost = array_shift($exportedposts);
            $exportedfirstpost->replies = $exportedposts;
            $exportedposts = [$exportedfirstpost];
        }

        return $this->renderer->render_from_template($this->get_template($displaymode), ['posts' => $exportedposts]);
    }

    private function get_template(int $displaymode) : string {
        switch ($displaymode) {
            case FORUM_MODE_THREADED:
                return 'mod_forum/forum_discussion_threaded_posts';
            case FORUM_MODE_NESTED:
                return 'mod_forum/forum_discussion_nested_posts';
            default;
                return 'mod_forum/forum_discussion_posts';
        }
    }

    private function get_authors_for_posts(array $posts) : array {
        $authorvault = $this->vaultfactory->get_author_vault();
        return $authorvault->get_authors_for_posts($posts);
    }

    private function get_attachments_for_posts(array $posts) : array {
        $forum = $this->forum;
        $postattachmentvault = $this->vaultfactory->get_post_attachment_vault();
        return $postattachmentvault->get_attachments_for_posts($forum->get_context(), $posts);
    }

    private function get_author_groups_from_posts(array $posts) : array {
        $course = $this->forum->get_course_record();
        $coursemodule = $this->forum->get_course_module_record();
        $authorids = array_reduce($posts, function($carry, $post) {
            $carry[$post->get_author_id()] = [];
            return $carry;
        }, []);
        $authorgroups = groups_get_all_groups($course->id, array_keys($authorids), $coursemodule->groupingid, 'g.*, gm.id, gm.groupid, gm.userid');

        return array_reduce($authorgroups, function($carry, $group) {
            // Clean up data returned from groups_get_all_groups.
            $userid = $group->userid;
            $groupid = $group->groupid;

            unset($group->userid);
            unset($group->groupid);
            $group->id = $groupid;

            if (!isset($carry[$userid])) {
                $carry[$userid] = [$group];
            } else {
                $carry[$userid][] = $group;
            }

            return $carry;
        }, $authorids);
    }

    private function get_tags_from_posts(array $posts) : array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);
        return core_tag_tag::get_items_tags('mod_forum', 'forum_posts', $postids);
    }

    private function get_ratings_from_posts(stdClass $user, array $posts) {
        $forum = $this->forum;
        $postsdatamapper = $this->legacydatamapperfactory->get_post_data_mapper();

        $items = $postsdatamapper->to_legacy_objects($posts);
        $ratingoptions = (object) [
            'context' => $forum->get_context(),
            'component' => 'mod_forum',
            'ratingarea' => 'post',
            'items' => $items,
            'aggregate' => $forum->get_rating_aggregate(),
            'scaleid' => $forum->get_scale(),
            'userid' => $user->id,
            'assesstimestart' => $forum->get_assess_time_start(),
            'assesstimefinish' => $forum->get_assess_time_finish()
        ];

        $rm = $this->ratingmanager;
        $items = $rm->get_ratings($ratingoptions);

        return array_reduce($items, function($carry, $item) {
            $carry[$item->id] = empty($item->rating) ? null : $item->rating;
            return $carry;
        }, []);
    }
}
