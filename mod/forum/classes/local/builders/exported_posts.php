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
 * Post class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\builders;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\post_read_receipt_collection as read_receipt_collection_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use core\output\notification;
use context;
use core_tag_tag;
use moodle_exception;
use rating_manager;
use renderer_base;
use stdClass;

/**
 * Post class.
 */
class exported_posts {
    private $renderer;
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $ratingmanager;

    public function __construct(
        renderer_base $renderer,
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        rating_manager $ratingmanager
    ) {
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->ratingmanager = $ratingmanager;
    }

    public function build(
        stdClass $user,
        array $forums,
        array $discussions,
        array $posts,
        read_receipt_collection_entity $readreceiptcollection = null
    ) {
        $forums = array_reduce($forums, function($carry, $forum) {
            $carry[$forum->get_id()] = $forum;
            return $carry;
        }, []);
        $discussions = array_reduce($discussions, function($carry, $discussion) {
            $carry[$discussion->get_id()] = $discussion;
            return $carry;
        }, []);

        $groupedposts = $this->group_posts_by_discussion($forums, $discussions, $posts);
        $authorsbyid = $this->get_authors_for_posts($posts);
        $attachmentsbypostid = $this->get_attachments_for_posts($groupedposts);
        $groupsbyforumandauthorid = $this->get_author_groups_from_posts($groupedposts);
        $tagsbypostid = $this->get_tags_from_posts($posts);
        $ratingbypostid = $this->get_ratings_from_posts($user, $groupedposts);
        $exportedposts = [];

        foreach ($groupedposts as $grouping) {
            [
                'forum' => $forum,
                'discussion' => $discussion,
                'posts' => $groupedposts
            ] = $grouping;

            $postsexporter = $this->exporterfactory->get_posts_exporter(
                $user,
                $forum,
                $discussion,
                $groupedposts,
                $authorsbyid,
                $attachmentsbypostid,
                $groupsbyforumandauthorid[$forum->get_id()],
                $readreceiptcollection,
                $tagsbypostid,
                $ratingbypostid,
                true
            );
            ['posts' => $exportedgroupedposts] = (array) $postsexporter->export($this->renderer);
            $exportedposts = array_merge($exportedposts, $exportedgroupedposts);
        }

        if (count($forums) == 1 && count($discussions) == 1) {
            // All of the posts belong to a single discussion in a single forum so
            // the exported order will match the given $posts array.
            return $exportedposts;
        } else {
            // Since we grouped the posts by discussion and forum the ordering of the
            // exported posts may be different to the given $posts array so we should
            // sort it back into the correct order for the caller.
            return $this->sort_exported_posts($posts, $exportedposts);
        }
    }

    private function group_posts_by_discussion(array $forums, array $discussions, array $posts) : array {
        $grouping = array_reduce($posts, function($carry, $post) use ($discussions) {
            $discussionid = $post->get_discussion_id();
            if (!isset($discussions[$discussionid])) {
                throw new moodle_exception('Unable to find discussion with id ' . $discussionid);
            }

            if (isset($carry[$discussionid])) {
                $carry[$discussionid]['posts'][] = $post;
            } else {
                $carry[$discussionid] = [
                    'discussion' => $discussions[$discussionid],
                    'posts' => [$post]
                ];
            }

            return $carry;
        }, []);

        return array_map(function($group) use ($forums) {
            $discussion = $group['discussion'];
            $forumid = $discussion->get_forum_id();
            if (!isset($forums[$forumid])) {
                throw new moodle_exception('Unable to find forum with id ' . $forumid);
            }

            $group['forum'] = $forums[$forumid];
            return $group;
        }, $grouping);
    }

    private function get_authors_for_posts(array $posts) : array {
        $authorvault = $this->vaultfactory->get_author_vault();
        return $authorvault->get_authors_for_posts($posts);
    }

    private function get_attachments_for_posts(array $groupedposts) : array {
        $attachmentsbypostid = [];
        $postattachmentvault = $this->vaultfactory->get_post_attachment_vault();

        foreach ($groupedposts as $grouping) {
            ['forum' => $forum, 'posts' => $posts] = $grouping;
            $attachments = $postattachmentvault->get_attachments_for_posts($forum->get_context(), $posts);
            $attachmentsbypostid = array_merge($attachmentsbypostid, $attachments);
        }

        return $attachmentsbypostid;
    }

    private function get_author_groups_from_posts(array $groupedposts) : array {
        $groupsbyauthorid = [];
        $authoridsbyforumid = [];

        foreach ($groupedposts as $grouping) {
            ['forum' => $forum, 'posts' => $posts] = $grouping;
            $forumid = $forum->get_id();
            if (!isset($authoridsbyforumid[$forumid])) {
                $authoridsbyforumid[$forumid] = [
                    'forum' => $forum,
                    'authorids' => []
                ];
            }

            $authorids = array_map(function($post) {
                return $post->get_author_id();
            }, $posts);

            foreach ($authorids as $authorid) {
                $authoridsbyforumid[$forumid]['authorids'][$authorid] = $authorid;
            }
        }

        foreach ($authoridsbyforumid as $forumid => $values) {
            ['forum' => $forum, 'authorids' => $authorids] = $values;
            $course = $forum->get_course_record();
            $coursemodule = $forum->get_course_module_record();
            $authorgroups = groups_get_all_groups($course->id, array_keys($authorids), $coursemodule->groupingid, 'g.*, gm.id, gm.groupid, gm.userid');

            if (!isset($groupsbyauthorid[$forumid])) {
                $groupsbyauthorid[$forumid] = [];
            }

            foreach ($authorgroups as $group) {
                // Clean up data returned from groups_get_all_groups.
                $userid = $group->userid;
                $groupid = $group->groupid;

                unset($group->userid);
                unset($group->groupid);
                $group->id = $groupid;

                if (!isset($groupsbyauthorid[$forumid][$userid])) {
                    $groupsbyauthorid[$forumid][$userid] = [];
                }

                $groupsbyauthorid[$forumid][$userid][] = $group;
            }
        }

        return $groupsbyauthorid;
    }

    private function get_tags_from_posts(array $posts) : array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);
        return core_tag_tag::get_items_tags('mod_forum', 'forum_posts', $postids);
    }

    private function get_ratings_from_posts(stdClass $user, array $groupedposts) {
        $ratingsbypostid = [];
        $postsdatamapper = $this->legacydatamapperfactory->get_post_data_mapper();

        foreach ($groupedposts as $grouping) {
            ['forum' => $forum, 'posts' => $posts] = $grouping;

            if (!$forum->has_rating_aggregate()) {
                continue;
            }

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

            foreach ($items as $item) {
                $ratingsbypostid[$item->id] = empty($item->rating) ? null : $item->rating;
            }
        }

        return $ratingsbypostid;
    }

    private function sort_exported_posts(array $posts, array $exportedposts) {
        $postindexes = [];
        foreach (array_values($posts) as $index => $post) {
            $postindexes[$post->get_id()] = $index;
        }

        $sortedexportedposts = [];

        foreach ($exportedposts as $exportedpost) {
            $index = $postindexes[$exportedpost->get_id()];
            $sortedexportedposts[$index] = $exportedpost;
        }

        return $sortedexportedposts;
    }
}
