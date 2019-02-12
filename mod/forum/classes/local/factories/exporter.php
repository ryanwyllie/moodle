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
 * Forum Exporter factory.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\factories;

defined('MOODLE_INTERNAL') || die();

use core_rating\external\rating_exporter;
use core_rating\external\rating_settings_exporter;
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
use rating;
use stdClass;

/**
 * The exporter factory class used to fetch an instance of the different exporter types.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 */
class exporter {
    /** @var legacy_data_mapper_factory The factory to fetch a legacy data mapper */
    private $legacydatamapperfactory;

    /** @var manager_factory The factory to fetch a new manager */
    private $managerfactory;

    /**
     * Constructor for the expoter factory.
     *
     * @param   legacy_data_mapper_factory $legacydatamapperfactory The factory to fetch a legacy data mapper instance
     * @param   manager_factory $managerfactory The factory fo fetch a manager instance
     */
    public function __construct(
        legacy_data_mapper_factory $legacydatamapperfactory,
        manager_factory $managerfactory
    ) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->managerfactory = $managerfactory;
    }

    /**
     * Construct a new forum exporter for the specified user and forum.
     *
     * @param   stdClass        $user The user viewing the forum
     * @param   forum_entity    $forum The forum being viewed
     * @param   int             $groupid The group currently being viewed
     * @return  forum_exporter
     */
    public function get_forum_exporter(
        stdClass $user,
        forum_entity $forum,
        ?int $currentgroup
    ) : forum_exporter {
        return new forum_exporter($forum, [
            'legacydatamapperfactory' => $this->legacydatamapperfactory,
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'user' => $user,
            'currentgroup' => $currentgroup,
        ]);
    }

    /**
     * Fetch the structure of the forum exporter.
     *
     * @return  array
     */
    public static function get_forum_export_structure() : array {
        return forum_exporter::read_properties_definition();
    }

    /**
     * Construct a new discussion exporter for the specified user and forum discussion.
     *
     * @param   stdClass        $user The user viewing the forum
     * @param   forum_entity    $forum The forum being viewed
     * @param   discussion_entity $discussion The discussion being viewed
     * @param   stdClass[]      $groupsbyid The list of groups in the forum
     * @return  discussion_exporter
     */
    public function get_discussion_exporter(
        stdClass $user,
        forum_entity $forum,
        discussion_entity $discussion,
        array $groupsbyid = [],
        rating $rating = null
    ) : discussion_exporter {
        return new discussion_exporter($discussion, [
            'context' => $forum->get_context(),
            'forum' => $forum,
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'user' => $user,
            'legacydatamapperfactory' => $this->legacydatamapperfactory,
            'exporterfactory' => $this,
            'latestpostid' => null,
            'groupsbyid' => $groupsbyid,
            'rating' => $rating
        ]);
    }

    /**
     * Fetch the structure of the discussion exporter.
     *
     * @return  array
     */
    public static function get_discussion_export_structure() {
        return discussion_exporter::read_properties_definition();
    }

    /**
     * Construct a new discussion summaries exporter for the specified user and set of discussions.
     *
     * @param   stdClass        $user The user viewing the forum
     * @param   forum_entity    $forum The forum being viewed
     * @param   discussion_entity[] $discussions The set of discussions to be shown
     * @param   stdClass[]      $groupsbyauthorid The set of groups in an associative array for each author
     * @param   stdClass[]      $groupsbyid The set of groups in the forum in an associative array for each group
     * @param   int[]           $discussionreplycount The number of replies for each discussion
     * @param   int[]           $discussionunreadcount The number of unread posts for each discussion
     * @param   int[]           $latestpostids The latest post id for each discussion
     * @return  discussion_summaries_exporter
     */
    public function get_discussion_summaries_exporter(
        stdClass $user,
        forum_entity $forum,
        array $discussions,
        array $groupsbyid = [],
        array $groupsbyauthorid = [],
        array $discussionreplycount = [],
        array $discussionunreadcount = [],
        array $latestpostid = []
    ) : discussion_summaries_exporter {
        return new discussion_summaries_exporter(
            $discussions,
            $groupsbyid,
            $groupsbyauthorid,
            $discussionreplycount,
            $discussionunreadcount,
            $latestpostid,
            [
                'legacydatamapperfactory' => $this->legacydatamapperfactory,
                'context' => $forum->get_context(),
                'forum' => $forum,
                'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
                'urlmanager' => $this->managerfactory->get_url_manager($forum),
                'user' => $user,
            ]
        );
    }

    /**
     * Fetch the structure of the discussion summaries exporter.
     *
     * @return  array
     */
    public static function get_discussion_summaries_export_structure() {
        return discussion_summaries_exporter::read_properties_definition();
    }

    /**
     * Construct a new post exporter for the specified user and set of post.
     *
     * @param   stdClass        $user The user viewing the forum
     * @param   forum_entity    $forum The forum being viewed
     * @param   discussion_entity $discussion The discussion that the post is in
     * @param   post_entity[]   $posts The set of posts to be exported
     * @param   stdClass[]      $groupsbyauthorid The group information indexes by author
     * @param   post_read_receipt_collection_entity $readreceiptcollection Details of read receipts for each post
     * @return  post_exporter
     */
    public function get_posts_exporter(
        stdClass $user,
        forum_entity $forum,
        discussion_entity $discussion,
        array $posts,
        array $groupsbyauthorid = [],
        post_read_receipt_collection_entity $readreceiptcollection = null,
        array $tagsbypostid = [],
        array $ratingbypostid = []
    ) : posts_exporter {
        return new posts_exporter($posts, $groupsbyauthorid, $tagsbypostid, $ratingbypostid, [
            'legacydatamapperfactory' => $this->legacydatamapperfactory,
            'exporterfactory' => $this,
            'capabilitymanager' => $this->managerfactory->get_capability_manager($forum),
            'urlmanager' => $this->managerfactory->get_url_manager($forum),
            'forum' => $forum,
            'discussion' => $discussion,
            'user' => $user,
            'context' => $forum->get_context(),
            'readreceiptcollection' => $readreceiptcollection,
        ]);
    }

    /**
     * Fetch the structure of the posts exporter.
     *
     * @return  array
     */
    public static function get_posts_export_structure() {
        return posts_exporter::read_properties_definition();
    }

    /**
     * Construct a new rating exporter for the specified user and rating.
     *
     * @param   stdClass        $user The user viewing the forum
     * @param   rating    $rating The rating to export
     * @return  rating_exporter
     */
    public function get_rating_exporter(
        stdClass $user,
        rating $rating
    ) : rating_exporter {
        return new rating_exporter($rating, [
            'user' => $user,
            'ratingmanager' => $this->managerfactory->get_rating_manager()
        ]);
    }

    /**
     * Fetch the structure of the rating exporter.
     *
     * @return  array
     */
    public static function get_rating_export_structure() {
        return rating_exporter::read_properties_definition();
    }

    /**
     * Construct a new rating settings exporter for the given rating.
     *
     * @param   rating    $rating The rating to export
     * @return  rating_info_exporter
     */
    public function get_rating_settings_exporter_from_rating(rating $rating) : rating_settings_exporter {
        return new rating_settings_exporter($rating->settings, []);
    }

    /**
     * Fetch the structure of the rating settings exporter.
     *
     * @return  array
     */
    public static function get_rating_settings_export_structure() {
        return rating_settings_exporter::read_properties_definition();
    }
}
