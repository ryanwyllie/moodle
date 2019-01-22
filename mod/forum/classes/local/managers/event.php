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
 * A manager to help raise events in the forum.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\managers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\discussion as discussion_entity;

/**
 * A manager to help raise events in the forum.
 *
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event {
    public function mark_forum_as_viewed(forum_entity $forumentity): void {
        $mapperfactory = \mod_forum\local\container::get_database_data_mapper_factory();
        $forummapper = $mapperfactory->get_forum_data_mapper();

        $course = $forumentity->get_course_record();
        $coursemodule = $forumentity->get_course_module_record();
        $forum = $forummapper->to_db_records([$forumentity])[0];

        // Completion.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($coursemodule);

        // Trigger course_module_viewed event.
        $event = \mod_forum\event\course_module_viewed::create([
                'context' => $forumentity->get_context(),
                'objectid' => $forumentity->get_id(),
            ]);
        $event->add_record_snapshot('course_modules', $coursemodule);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('forum', $forum);
        $event->trigger();
    }

    public function mark_discussion_as_viewed(forum_entity $forumentity, discussion_entity $discussionentity): void {
        $mapperfactory = \mod_forum\local\container::get_database_data_mapper_factory();
        $forummapper = $mapperfactory->get_forum_data_mapper();
        $discussionmapper = $mapperfactory->get_discussion_data_mapper();

        $course = $forumentity->get_course_record();
        $coursemodule = $forumentity->get_course_module_record();
        $forum = $forummapper->to_db_records([$forumentity])[0];
        $discussion = $discussionmapper->to_db_records([$discussionentity])[0];

        $event = \mod_forum\event\discussion_viewed::create([
                'context' => $forumentity->get_context(),
                'objectid' => $discussionentity->get_id(),
            ]);
        $event->add_record_snapshot('forum_discussions', $discussion);
        $event->add_record_snapshot('forum', $forum);
        $event->trigger();
    }
}
