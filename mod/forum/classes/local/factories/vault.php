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

use mod_forum\local\factories\database_serializer as serializer_factory;
use mod_forum\local\vaults\author as author_vault;
use mod_forum\local\vaults\discussion as discussion_vault;
use mod_forum\local\vaults\forum as forum_vault;
use mod_forum\local\vaults\post as post_vault;

/**
 * Vault factory.
 */
class vault {
    private $serializerfactory;

    public function __construct(serializer_factory $serializerfactory) {
        $this->serializerfactory = $serializerfactory;
    }

    public function get_forum_vault() : forum_vault {
        global $DB;
        return new forum_vault($DB, 'forum', $this->serializerfactory->get_forum_serializer());
    }

    public function get_discussion_vault() : discussion_vault {
        global $DB;
        return new discussion_vault($DB, 'forum_discussions', $this->serializerfactory->get_discussion_serializer());
    }

    public function get_post_vault() : post_vault {
        global $DB;
        return new post_vault($DB, 'forum_posts', $this->serializerfactory->get_post_serializer());
    }

    public function get_author_vault() : author_vault {
        global $DB;
        return new author_vault($DB, 'user', $this->serializerfactory->get_author_serializer());
    }
}
