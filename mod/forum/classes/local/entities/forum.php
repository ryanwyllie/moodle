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

namespace mod_forum\local\entities;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use context;
use cm_info;
use stdClass;

/**
 * Forum class.
 */
class forum {
    private $context;
    private $coursemodule;
    private $course;
    private $effectivegroupmode;
    private $id;
    private $courseid;
    private $type;
    private $name;
    private $intro;
    private $introformat;
    private $assessed;
    private $assesstimestart;
    private $assesstimefinish;
    private $scale;
    private $maxbytes;
    private $maxattachments;
    private $forcesubscription;
    private $trackingtype;
    private $rsstype;
    private $rssarticles;
    private $timemodified;
    private $warnafter;
    private $blockafter;
    private $blockperiod;
    private $completiondiscussions;
    private $completionreplies;
    private $completionposts;
    private $displaywordcounts;
    private $lockdiscussionafter;

    public function __construct(
        context $context,
        stdClass $coursemodule,
        stdClass $course,
        int $effectivegroupmode,
        int $id,
        int $courseid,
        string $type,
        string $name,
        string $intro,
        int $introformat,
        int $assessed,
        int $assesstimestart,
        int $assesstimefinish,
        int $scale,
        int $maxbytes,
        int $maxattachments,
        bool $forcesubscribe,
        int $trackingtype,
        int $rsstype,
        int $rssarticles,
        int $timemodified,
        int $warnafter,
        int $blockafter,
        int $blockperiod,
        int $completiondiscussions,
        int $completionreplies,
        int $completionposts,
        bool $displaywordcount,
        int $lockdiscussionafter
    ) {
        $this->context = $context;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
        $this->effectivegroupmode = $effectivegroupmode;
        $this->id = $id;
        $this->courseid = $courseid;
        $this->type = $type;
        $this->name = $name;
        $this->intro = $intro;
        $this->introformat = $introformat;
        $this->assessed = $assessed;
        $this->assesstimestart = $assesstimestart;
        $this->assesstimefinish = $assesstimefinish;
        $this->scale = $scale;
        $this->maxbytes = $maxbytes;
        $this->maxattachments = $maxattachments;
        $this->forcesubscribe = $forcesubscribe;
        $this->trackingtype = $trackingtype;
        $this->rsstype = $rsstype;
        $this->rssarticles = $rssarticles;
        $this->timemodified = $timemodified;
        $this->warnafter = $warnafter;
        $this->blockafter = $blockafter;
        $this->blockperiod = $blockperiod;
        $this->completiondiscussions = $completiondiscussions;
        $this->completionreplies = $completionreplies;
        $this->completionposts = $completionposts;
        $this->displaywordcount = $displaywordcount;
        $this->lockdiscussionafter = $lockdiscussionafter;
    }

    public function get_context() : context {
        return $this->context;
    }

    public function get_course_module_record() : stdClass {
        return $this->coursemodule;
    }

    public function get_effective_group_mode() : int {
        return $this->effectivegroupmode;
    }

    public function get_course_record() : stdClass {
        return $this->course;
    }

    public function get_id() : int {
        return $this->id;
    }

    public function get_course_id() : int {
        return $this->courseid;
    }

    public function get_type() : string {
        return $this->type;
    }

    public function get_name() : string {
        return $this->name;
    }

    public function get_intro() : string {
        return $this->intro;
    }

    public function get_intro_format() : int {
        return $this->introformat;
    }

    public function get_rating_aggregate() : int {
        return $this->assessed;
    }

    public function has_rating_aggregate() : bool {
        return $this->get_rating_aggregate() != RATING_AGGREGATE_NONE;
    }

    public function get_assess_time_start() : int {
        return $this->assesstimestart;
    }

    public function get_assess_time_finish() : int {
        return $this->assesstimefinish;
    }

    public function get_scale() : int {
        return $this->scale;
    }

    public function get_max_bytes() : int {
        return $this->maxbytes;
    }

    public function get_max_attachments() : int {
        return $this->maxattachments;
    }

    public function is_subscription_forced() : bool {
        return $this->forcesubscribe;
    }

    public function get_tracking_type() : int {
        return $this->trackingtype;
    }

    public function get_rss_type() : int {
        return $this->rsstype;
    }

    public function get_rss_articles() : int {
        return $this->rssarticles;
    }

    public function get_time_modified() : int {
        return $this->timemodified;
    }

    public function get_warn_after() : int {
        return $this->warnafter;
    }

    public function get_block_after() : int {
        return $this->blockafter;
    }

    public function get_block_period() : int {
        return $this->blockperiod;
    }

    public function has_blocking_enabled() : bool {
        return !empty($this->get_block_after()) && !empty($this->get_block_period());
    }

    // What is this?
    public function get_completion_discussions() : int {
        return $this->completiondiscussions;
    }

    // What is this?
    public function get_completion_replies() : int {
        return $this->completionreplies;
    }

    // What is this?
    public function get_completion_posts() : int {
        return $this->completionposts;
    }

    public function should_display_word_count() : bool {
        return $this->displaywordcount;
    }

    public function get_lock_discussions_after() : int {
        return $this->lockdiscussionafter;
    }

    public function has_lock_discussions_after() : bool {
        return !empty($this->get_lock_discussions_after());
    }

    public function is_discussion_locked(discussion_entity $discussion) : bool {
        if (!$this->has_lock_discussions_after()) {
            return false;
        }

        if ($this->get_type() === 'single') {
            // It does not make sense to lock a single discussion forum.
            return false;
        }

        return (($discussion->get_time_modified() + $this->get_lock_discussions_after()) < time());
    }
}
