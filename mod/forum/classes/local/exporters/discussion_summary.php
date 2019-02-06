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

namespace mod_forum\local\exporters;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion_summary as discussion_summary_entity;
use core\external\exporter;
use renderer_base;

/**
 * Forum class.
 */
class discussion_summary extends exporter {
    /** @var discussion_summary_entity The discussion summary information */
    private $summary;

    /** @var stdClass[] The group information for each author */
    private $groupsbyid;

    /** @var stdClass[] The group information for each author */
    private $groupsbyauthorid;

    /** @var int The number of replies to the discussion */
    private $replycount;

    /** @var int number of unread posts if the user is tracking these */
    private $unreadcount;

    /** @var int The latest post id in the discussion */
    private $latestpostid;

    public function __construct(
        discussion_summary_entity $summary,
        array $groupsbyid,
        array $groupsbyauthorid,
        int $replycount,
        int $unreadcount,
        int $latestpostid,
        $related = []
    ) {
        $this->summary = $summary;
        $this->groupsbyid = $groupsbyid;
        $this->groupsbyauthorid = $groupsbyauthorid;
        $this->replycount = $replycount;
        $this->unreadcount = $unreadcount;
        $this->latestpostid = $latestpostid;
        return parent::__construct([], $related);
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => ['type' => PARAM_INT],
            'discussion' => [
                'type' => discussion::read_properties_definition(),
            ],
            'replies' => [
                'type' => ['type' => PARAM_INT],
            ],
            'unread' => [
                'type' => ['type' => PARAM_INT],
            ],
            'firstpostauthor' => [
                'type' => author::read_properties_definition(),
            ],
            'latestpostauthor' => [
                'type' => author::read_properties_definition(),
            ],
            'latestpostid' => ['type' => PARAM_INT],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $capabilitymanager = $this->related['capabilitymanager'];
        $forum = $this->related['forum'];
        $user = $this->related['user'];
        $discussion = $this->summary->get_discussion();

        $related = (array) (object) $this->related;
        $related['latestpostid'] = $this->latestpostid;
        $related['groupsbyid'] = $this->groupsbyid;
        $discussionexporter = new discussion($discussion, $related);

        $related = [
            'urlmanager' => $this->related['urlmanager'],
            'context' => $this->related['forum']->get_context(),
        ];

        $firstpostauthor = new author(
            $this->summary->get_first_post_author(),
            $this->groupsbyauthorid[$this->summary->get_first_post_author()->get_id()],
            $capabilitymanager->can_view_post(
                $user,
                $discussion,
                $this->summary->get_first_post()
            ),
            $related
        );

        $latestpostauthor = new author(
            $this->summary->get_latest_post_author(),
            [],
            $capabilitymanager->can_view_post(
                $user,
                $discussion,
                $this->summary->get_first_post()
            ),
            $related
        );

        return [
            'id' => $discussion->get_id(),
            'discussion' => $discussionexporter->export($output),
            'replies' => $this->replycount,
            'unread' => $this->unreadcount,
            'firstpostauthor' => $firstpostauthor->export($output),
            'latestpostauthor' => $latestpostauthor->export($output),
            'latestpostid' => $this->latestpostid,
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'legacydatamapperfactory' => 'mod_forum\local\factories\legacy_data_mapper',
            'context' => 'context',
            'forum' => 'mod_forum\local\entities\forum',
            'capabilitymanager' => 'mod_forum\local\managers\capability',
            'urlmanager' => 'mod_forum\local\managers\url',
            'user' => 'stdClass',
        ];
    }
}
