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
 * Templatable for a forum discussion subscription toggle.
 *
 * @package    mod_forum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Templatable for a forum discussion subscription toggle.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion_subscription_toggle implements \renderable, \templatable {

    /**
     * @var \stdClass The forum whose subscription toggle is to be shown.
     */
    protected $forum;

    /**
     * @var \stdClass The discussion being toggled.
     */
    protected $discussion;

    /**
     * @var bool Whether to include the textual description or not.
     */
    protected $includetext = true;

    /**
     * Constructor.
     *
     * @param   \mod_forum\instance $forum
     * @param   \stdClass           $discussion
     */
    public function __construct(\stdClass $forum, \stdClass $discussion, $includetext = true) {
        $this->forum = $forum;
        $this->discussion = $discussion;
        $this->includetext = $includetext;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param   \mod_forum_renderer $renderer
     * @return  \stdClass            Data ready for use in a mustache template
     */
    public function export_for_template(\renderer_base $renderer) : \stdclass {
        return (object) [
            'can_subscribe' => $this->forum->can_subscribe(),
            'is_subscribed' => $this->forum->is_subscribed_to_discussion($this->discussion),
            'discussionid' => $this->discussion->id,
            'forumid' => $this->forum->get_forum_id(),
            'include_text' => $this->includetext,
        ];
    }
}
