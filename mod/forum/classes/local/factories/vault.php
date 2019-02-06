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

use mod_forum\local\factories\entity as entity_factory;
use mod_forum\local\vaults\author as author_vault;
use mod_forum\local\vaults\discussion as discussion_vault;
use mod_forum\local\vaults\discussion_list as discussion_list_vault;
use mod_forum\local\vaults\forum as forum_vault;
use mod_forum\local\vaults\post as post_vault;
use mod_forum\local\vaults\post_read_receipt_collection as post_read_receipt_collection_vault;
use file_storage;
use moodle_database;

/**
 * Vault factory.
 */
class vault {
    private $entityfactory;
    private $db;
    private $filestorage;

    public function __construct(moodle_database $db, entity_factory $entityfactory, file_storage $filestorage) {
        $this->db = $db;
        $this->entityfactory = $entityfactory;
        $this->filestorage = $filestorage;
    }

    public function get_forum_vault() : forum_vault {
        return new forum_vault(
            $this->db,
            $this->entityfactory
        );
    }

    public function get_discussion_vault() : discussion_vault {
        return new discussion_vault(
            $this->db,
            $this->entityfactory
        );
    }

    public function get_discussions_in_forum_vault() : discussion_list_vault {
        return new discussion_list_vault(
            $this->db,
            $this->entityfactory
        );
    }

    public function get_post_vault() : post_vault {
        return new post_vault(
            $this->db,
            $this->entityfactory,
            $this->filestorage
        );
    }

    public function get_author_vault() : author_vault {
        return new author_vault(
            $this->db,
            $this->entityfactory
        );
    }

    public function get_post_read_receipt_collection_vault() : post_read_receipt_collection_vault {
        return new post_read_receipt_collection_vault(
            $this->db,
            $this->entityfactory
        );
    }
}
