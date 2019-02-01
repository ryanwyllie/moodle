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
 * Discussion list renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Nested discussion renderer class.
 */
class discussion_list {
    public const SORTORDER_NEWEST_FIRST = 1;
    public const SORTORDER_OLDEST_FIRST = 2;

    private $forum;
    private $forumrecord;
    private $renderer;
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $capabilitymanager;
    private $notifications;

    public function __construct(
        forum_entity $forum,
        renderer_base $renderer,
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        capability_manager $capabilitymanager,
        array $notifications = []
    ) {
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->capabilitymanager = $capabilitymanager;
        $this->notifications = $notifications;

        $forumdatamapper = $this->legacydatamapperfactory->get_forum_data_mapper();
        $this->forumrecord = $forumdatamapper->to_legacy_object($forum);
    }

    public function render(\stdClass $user, ?int $groupid, ?int $sortorder, ?int $pageno, ?int $pagesize) : string {
        $capabilitymanager = $this->capabilitymanager;
        $forum = $this->forum;

        // Make sure we can render.
        if (!$capabilitymanager->can_view_discussions($user)) {
            throw new moodle_exception('noviewdiscussionspermission', 'mod_forum');
        }

        $forumexporter = $this->exporterfactory->get_forum_exporter(
            $user,
            $this->forum,
            $groupid
        );

        $forumview = array_merge(
                (array) $forumexporter->export($this->renderer),
                (array) $this->get_exported_discussions($user, $groupid, $sortorder, $pageno, $pagesize)
            );

        print_object($forumview);

        return $this->renderer->render_from_template($this->get_template(), $forumview);
    }

    private function get_exported_discussions(\stdClass $user, ?int $groupid, ?int $sortorder, ?int $pageno, ?int $pagesize) {
        $forum = $this->forum;
        $discussionvault = $this->vaultfactory->get_discussions_in_forum_vault();
        if ($groupid === null) {
            $discussions = $discussionvault->get_from_forum_id(
                $forum->get_id(),
                $sortorder,
                $this->get_page_size($pagesize),
                $this->get_page_number($pageno));
        } else {
            $discussions = $discussionvault->get_from_forum_id_and_group_id(
                $forum->get_id(),
                $groupid,
                $sortorder,
                $this->get_page_size($pagesize),
                $this->get_page_number($pageno));
        }

        $postvault = $this->vaultfactory->get_post_vault();
        $posts = $postvault->get_from_discussion_ids(array_keys($discussions));

        $groupsbyauthorid = $this->get_author_groups_from_posts($posts);

        $summaryexporter = $this->exporterfactory->get_discussion_summaries_exporter(
            $user,
            $forum,
            $discussions,
            $groupsbyauthorid
        );

        return $summaryexporter->export($this->renderer);
    }

    private function get_page_size() : int {
        // TODO
        return 50;
    }

    private function get_page_number() : int {
        // TODO
        return 0;
    }

    private function get_template() : string {
        switch ($this->forum->get_type()) {
            case 'news':
                return 'mod_forum/news_discussion_list';
                break;
            case 'blog':
                return 'mod_forum/blog_discussion_list';
                break;
            case 'qanda':
                return 'mod_forum/qanda_discussion_list';
                break;
            default:
                return 'mod_forum/discussion_list';
        }
    }

    private function get_author_groups_from_posts(array $posts) : array {
        $course = $this->forum->get_course_record();
        $coursemodule = $this->forum->get_course_module_record();
        $authorids = array_reduce($posts, function($carry, $post) {
            $carry[$post->get_author()->get_id()] = true;
            return $carry;
        }, []);
        $authorids[3] = 1;
        $authorids[11] = 1;
        $authorgroups = groups_get_all_groups($course->id, array_keys($authorids), $coursemodule->groupingid, 'g.*, gm.id, gm.groupid, gm.userid');

        $authorgroups = array_reduce($authorgroups, function($carry, $group) {
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
        }, []);

        foreach (array_diff(array_keys($authorids), array_keys($authorgroups)) as $authorid) {
            $authorgroups[$authorid] = [];
        }

        return $authorgroups;
    }
}
