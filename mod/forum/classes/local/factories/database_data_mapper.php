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
use mod_forum\local\factories\entity as entity_factory;
use mod_forum\local\data_mappers\database\author as author_data_mapper;
use mod_forum\local\data_mappers\database\discussion as discussion_data_mapper;
use mod_forum\local\data_mappers\database\forum as forum_data_mapper;
use mod_forum\local\data_mappers\database\post as post_data_mapper;
use moodle_database;

/**
 * Exporter data_mapper factory.
 */
class database_data_mapper {
    private $db;
    private $entityfactory;

    public function __construct(moodle_database $db, entity_factory $entityfactory) {
        $this->db = $db;
        $this->entityfactory = $entityfactory;
    }

    public function get_forum_data_mapper() : forum_data_mapper {
        return new forum_data_mapper($this->db, $this->entityfactory);
    }

    public function get_discussion_data_mapper() : discussion_data_mapper {
        return new discussion_data_mapper($this->entityfactory);
    }

    public function get_post_data_mapper() : post_data_mapper {
        return new post_data_mapper($this->entityfactory);
    }

    public function get_author_data_mapper() : author_data_mapper {
        return new author_data_mapper($this->entityfactory);
    }
}
