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
 * Forum class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\serializers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;

/**
 * Forum class.
 */
class forum implements serializer_interface {

    public function from_db_records(array $records) : array {
        return array_map(function($record) {
            return new forum_entity(
                $record->id,
                $record->course,
                $record->type,
                $record->name,
                $record->intro,
                $record->introformat,
                $record->assessed,
                $record->assesstimestart,
                $record->assesstimefinish,
                $record->scale,
                $record->maxbytes,
                $record->maxattachments,
                $record->forcesubscribe,
                $record->trackingtype,
                $record->rsstype,
                $record->rssarticles,
                $record->timemodified,
                $record->warnafter,
                $record->blockafter,
                $record->blockperiod,
                $record->completiondiscussions,
                $record->completionreplies,
                $record->completionposts,
                $record->displaywordcount,
                $record->lockdiscussionafter
            );
        }, $records);
    }

    public function to_db_records(array $forums) : array {
        return array_map(function($forums) {
            return (object) [
                'id' => $forum->get_id(),
                'course' => $forum->get_course_id(),
                'type' => $forum->get_type(),
                'name' => $forum->get_name(),
                'intro' => $forum->get_intro(),
                'introformat' => $forum->get_intro_format(),
                'assessed' => $forum->is_assessed(),
                'assesstimestart' => $forum->get_assess_time_start(),
                'assesstimefinish' => $forum->get_assess_time_finish(),
                'scale' => $forum->get_scale(),
                'maxbytes' => $forum->get_max_bytes(),
                'maxattachments' => $forum->get_max_attachments(),
                'forcesubscribe' => $forum->is_subscription_forced(),
                'trackingtype' => $forum->get_tracking_type(),
                'rsstype' => $forum->get_rss_type(),
                'rssarticles' => $forum->get_rss_articles(),
                'timemodified' => $forum->get_time_modified(),
                'warnafter' => $forum->get_warn_after(),
                'blockafter' => $forum->get_block_after(),
                'blockperiod' => $forum->get_block_period(),
                'completiondiscussions' => $forum->get_completion_discussions(),
                'completionreplies' => $forum->get_completion_replies(),
                'completionposts' => $forum->get_completion_posts(),
                'displaywordcount' => $forum->should_display_word_count(),
                'lockdiscussionafter' => $forum->get_lock_discussion_after()
            ];
        }, $forums);
    }
}
