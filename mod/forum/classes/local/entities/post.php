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

namespace mod_forum\local\entities;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Post class.
 */
class post {
    private $id;
    private $discussionid;
    private $parentid;
    private $author;
    private $timecreated;
    private $timemodified;
    private $mailed;
    private $subject;
    private $message;
    private $messageformat;
    private $messagetrust;
    private $hasattachments;
    private $totalscore;
    private $mailnow;
    private $deleted;

    public function __construct(
        int $id,
        int $discussionid,
        int $parentid,
        author $author,
        int $timecreated,
        int $timemodified,
        bool $mailed,
        string $subject,
        string $message,
        int $messageformat,
        bool $messagetrust,
        bool $hasattachments,
        int $totalscore,
        bool $mailnow,
        bool $deleted
    ) {
        $this->id = $id;
        $this->discussionid = $discussionid;
        $this->parentid = $parentid;
        $this->author = $author;
        $this->timecreated = $timecreated;
        $this->timemodified = $timemodified;
        $this->mailed = $mailed;
        $this->subject = $subject;
        $this->message = $message;
        $this->messageformat = $messageformat;
        $this->messagetrust = $messagetrust;
        $this->hasattachments = $hasattachments;
        $this->totalscore = $totalscore;
        $this->mailnow = $mailnow;
        $this->deleted = $deleted;
    }

    public function get_id() : int {
        return $this->id;
    }

    public function get_discussion_id() : int {
        return $this->discussionid;
    }

    public function get_parent_id() : int {
        return $this->parentid;
    }

    public function has_parent() : bool {
        return $this->get_parent_id() > 0;
    }

    public function get_author() : author {
        return $this->author;
    }

    public function get_time_created() : int {
        return $this->timecreated;
    }

    public function get_time_modified() : int {
        return $this->timemodified;
    }

    public function has_been_mailed() : bool {
        return $this->mailed;
    }

    public function get_subject() : string {
        return $this->subject;
    }

    public function get_message() : string {
        return $this->message;
    }

    public function get_message_format() : int {
        return $this->messageformat;
    }

    // What is this?
    public function is_message_trusted() : bool {
        return $this->messagetrust;
    }

    public function has_attachments() : string {
        return $this->hasattachments;
    }

    public function get_total_score() : int {
        return $this->totalscore;
    }

    public function should_mail_now() : bool {
        return $this->mailnow;
    }

    public function is_deleted() : bool {
        return $this->deleted;
    }

    public function get_age() : int {
        return time() - $this->get_time_created();
    }

    public function is_owned_by_user(stdClass $user) : bool {
        return $this->get_author()->get_id() == $user->id;
    }
}
