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
use mod_forum\local\entities\discussion_summary as discussion_summary_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\post_read_receipt_collection as post_read_receipt_collection_entity;
use mod_forum\local\entities\sorter as sorter_entity;
use stdClass;
use context;
use cm_info;
use user_picture;
use moodle_url;

/**
 * Vault factory.
 */
class entity {
    public function get_forum_from_stdClass(stdClass $record, context $context, \stdClass $coursemodule, \stdClass $course) : forum_entity {
        // Note: cm_info::create loads a cm_info in the context of the current user.
        // Only use properties which relate to all users rather than a specific user.
        $cm = \cm_info::create($coursemodule);

        return new forum_entity(
            $context,
            $coursemodule,
            $course,
            $cm->effectivegroupmode,
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
    }

    public function get_discussion_from_stdClass(stdClass $record) : discussion_entity {
        return new discussion_entity(
            $record->id,
            $record->course,
            $record->forum,
            $record->name,
            $record->firstpost,
            $record->userid,
            $record->groupid,
            $record->assessed,
            $record->timemodified,
            $record->usermodified,
            $record->timestart,
            $record->timeend,
            $record->pinned
        );
    }

    public function get_post_from_stdClass(
        stdClass $record,
        author_entity $author
    ) : post_entity {
        return new post_entity(
            $record->id,
            $record->discussion,
            $record->parent,
            $author,
            $record->created,
            $record->modified,
            $record->mailed,
            $record->subject,
            $record->message,
            $record->messageformat,
            $record->messagetrust,
            $record->attachment,
            $record->totalscore,
            $record->mailnow,
            $record->deleted
        );
    }

    public function get_author_from_stdClass(stdClass $record) : author_entity {
        return new author_entity(
            $record->id,
            $record->picture,
            $record->firstname,
            $record->lastname,
            fullname($record),
            $record->email,
            $record->middlename,
            $record->firstnamephonetic,
            $record->lastnamephonetic,
            $record->alternatename,
            $record->imagealt
        );
    }

    public function get_discussion_summary_from_stdClass(
        stdClass $discussion,
        stdClass $firstpost,
        stdClass $firstpostauthor,
        stdClass $latestpostauthor
    ) : discussion_summary_entity {

        $firstpostauthorentity = $this->get_author_from_stdClass($firstpostauthor);
        return new discussion_summary_entity(
            $this->get_discussion_from_stdClass($discussion),
            $this->get_post_from_stdClass($firstpost, $firstpostauthorentity),
            $firstpostauthorentity,
            $this->get_author_from_stdClass($latestpostauthor)
        );
    }

    public function get_post_read_receipt_collection_from_stdClasses(array $records) : post_read_receipt_collection_entity {
        return new post_read_receipt_collection_entity($records);
    }

    public function get_posts_sorter() : sorter_entity {
        return new sorter_entity(
            function(post_entity $post) {
                return $post->get_id();
            },
            function(post_entity $post) {
                return $post->get_parent_id();
            }
        );
    }

    public function get_exported_posts_sorter() : sorter_entity {
        return new sorter_entity(
            function(stdClass $post) {
                return $post->id;
            },
            function(stdClass $post) {
                return $post->parentid;
            }
        );
    }
}
