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
 * Vault class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\serializers\database\db_serializer_interface;
use moodle_database;

/**
 * Vault class.
 */
class vault {
    private $table;
    private $db;
    private $serializer;

    public function __construct(moodle_database $db, string $table, db_serializer_interface $serializer) {
        $this->db = $db;
        $this->table = $table;
        $this->serializer = $serializer;
    }

    public function get_db() : moodle_database {
        return $this->db;
    }

    public function get_table() : string {
        return $this->table;
    }

    public function get_serializer() : db_serializer_interface {
        return $this->serializer;
    }

    public function get_from_id(int $id) {
        $record = $this->get_db()->get_record($this->get_table(), ['id' => $id]);
        return $record ? $this->transform_db_records_to_entities([$record])[0] : null;
    }

    public function get_from_ids(array $ids) {
        $records = $this->get_db()->get_records_list($this->get_table(), 'id', $ids);
        return $this->transform_db_records_to_entities($records);
    }

    protected function transform_db_records_to_entities(array $records) {
        return $this->get_serializer()->from_db_records($records);
    }
}
