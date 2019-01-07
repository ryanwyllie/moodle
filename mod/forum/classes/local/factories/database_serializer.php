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

use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\serializers\database\author as author_serializer;
use mod_forum\local\serializers\database\discussion as discussion_serializer;
use mod_forum\local\serializers\database\forum as forum_serializer;
use mod_forum\local\serializers\database\post as post_serializer;

/**
 * Exporter serializer factory.
 */
class database_serializer {
    public function get_forum_serializer() : forum_serializer {
        return new forum_serializer();
    }

    public function get_discussion_serializer() : discussion_serializer {
        return new discussion_serializer();
    }

    public function get_post_serializer() : post_serializer {
        return new post_serializer(
            (new vault_factory($this))->get_author_vault(),
            $this->get_discussion_serializer(),
            $this->get_forum_serializer()
        );
    }

    public function get_author_serializer() : author_serializer {
        return new author_serializer();
    }
}
