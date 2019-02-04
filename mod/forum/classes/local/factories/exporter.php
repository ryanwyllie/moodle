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

use mod_forum\local\entities\author as author_entity;
use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\post_read_receipt_collection as post_read_receipt_collection_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\manager as manager_factory;
use mod_forum\local\exporters\author as author_exporter;
use mod_forum\local\exporters\forum as forum_exporter;
use mod_forum\local\exporters\discussion as discussion_exporter;
use mod_forum\local\exporters\discussion_summaries as discussion_summaries_exporter;
use mod_forum\local\exporters\post as post_exporter;
use mod_forum\local\exporters\posts as posts_exporter;
use context;
use stdClass;

/**
 * data_mapper factory.
 */
class exporter {
    private $legacydatamapperfactory;
    private $managerfactory;

    public function __construct(
        legacy_data_mapper_factory $legacydatamapperfactory,
        manager_factory $managerfactory
    ) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->managerfactory = $managerfactory;
    }

    public function get_forum_exporter(
        stdClass $user,
        forum_entity $forum,
        ?int $currentgroup
    ) : forum_exporter {
        return new forum_exporter($forum, [
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'user' => $user,
            'currentgroup' => $currentgroup,
        ]);
    }

    public function get_forum_export_structure() {
        return forum_exporter::get_read_structure();
    }

    public function get_discussion_exporter(
        stdClass $user,
        forum_entity $forum,
        discussion_entity $discussion
    ) : discussion_exporter {
        return new discussion_exporter($discussion, [
            'context' => $forum->get_context(),
            'forum' => $forum,
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'user' => $user,
            'legacydatamapperfactory' => $this->legacydatamapperfactory,
        ]);
    }

    public function get_discussion_export_structure() {
        return discussion_exporter::get_read_structure();
    }

    public function get_discussion_summaries_exporter(
        stdClass $user,
        forum_entity $forum,
        array $discussions,
        array $groupsbyauthorid = [],
        array $discussionreplycount = []
    ) : discussion_summaries_exporter {
        return new discussion_summaries_exporter($discussions, $groupsbyauthorid, $discussionreplycount, [
            'legacydatamapperfactory' => $this->legacydatamapperfactory,
            'context' => $forum->get_context(),
            'forum' => $forum,
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'user' => $user,
        ]);
    }

    public function get_discussion_summaries_export_structure() {
        return discussion_summaries_exporter::get_read_structure();
    }

    public function get_posts_exporter(
        stdClass $user,
        forum_entity $forum,
        discussion_entity $discussion,
        array $posts,
        array $groupsbyauthorid = [],
        post_read_receipt_collection_entity $readreceiptcollection = null
    ) : posts_exporter {
        return new posts_exporter($posts, $groupsbyauthorid, [
            'legacydatamapperfactory' => $this->legacydatamapperfactory,
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'forum' => $forum,
            'discussion' => $discussion,
            'user' => $user,
            'context' => $forum->get_context(),
            'readreceiptcollection' => $readreceiptcollection
        ]);
    }

    public function get_posts_export_structure() {
        return posts_exporter::get_read_structure();
    }
}
