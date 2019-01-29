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
 * Discussion class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\entities;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\author as author_entity;

/**
 * Discussion class.
 */
class discussion_summary {
    private $discussion;
    private $firstpostauthor;
    private $firstpost;
    private $latestpostauthor;

    public function __construct(
        discussion_entity $discussion,
        post_entity $firstpost,
        author_entity $firstpostauthor,
        author_entity $latestpostauthor
    ) {
        $this->discussion = $discussion;
        $this->firstpostauthor = $firstpostauthor;
        $this->firstpost = $firstpost;
        $this->latestpostauthor = $latestpostauthor;
    }

    public function get_discussion() : discussion_entity {
        return $this->discussion;
    }

    public function get_first_post_author() : author_entity {
        return $this->firstpostauthor;
    }

    public function get_latest_post_author() : author_entity {
        return $this->latestpostauthor;
    }

    public function get_first_post() : post_entity {
        return $this->firstpost;
    }
}
