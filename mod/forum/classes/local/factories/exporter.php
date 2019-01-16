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
use mod_forum\local\factories\database_data_mapper as database_data_mapper_factory;
use mod_forum\local\exporters\author as author_exporter;
use mod_forum\local\exporters\discussion as discussion_exporter;
use mod_forum\local\exporters\post as post_exporter;
use mod_forum\local\exporters\posts as posts_exporter;
use context;
use stdClass;

/**
 * data_mapper factory.
 */
class exporter {
    private $dbdatamapperfactory;

    public function __construct(database_data_mapper_factory $dbdatamapperfactory) {
        $this->dbdatamapperfactory = $dbdatamapperfactory;
    }

    public function get_discussion_exporter(discussion_entity $discussion, array $exportedposts = []) : discussion_exporter {
        return new discussion_exporter($discussion, ['exportedposts' => $exportedposts]);
    }

    public function get_discussion_export_structure() {
        return discussion_exporter::get_read_structure();
    }

    public function get_posts_exporter(
        stdClass $user,
        context $context,
        forum_entity $forum,
        discussion_entity $discussion,
        array $posts,
        stdClass $coursemodule = null
    ) : posts_exporter {
        if (is_null($coursemodule)) {
            $coursemodule = get_coursemodule_from_instance('forum', $forum->get_id(), $forum->get_course_id());
        }

        return new posts_exporter($posts, [
            'databasedatamapperfactory' => $this->dbdatamapperfactory,
            'forum' => $forum,
            'discussion' => $discussion,
            'coursemodule' => $coursemodule,
            'user' => $user,
            'context' => $context
        ]);
    }

    public function get_posts_export_structure() {
        return posts_exporter::get_read_structure();
    }
}
