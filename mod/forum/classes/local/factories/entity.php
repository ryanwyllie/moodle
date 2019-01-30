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
        author_entity $author,
        array $attachments = [],
        array $useridreadlist = []
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
            $record->deleted,
            $attachments,
            $useridreadlist
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
}
