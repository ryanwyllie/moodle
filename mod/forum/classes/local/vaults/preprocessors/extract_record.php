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
 * Build step.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\vaults\preprocessors;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Build step.
 */
class extract_record {
    private $db;
    private $table;
    private $alias;

    public function __construct(moodle_database $db, string $table, string $alias) {
        $this->db = $db;
        $this->table = $table;
        $this->alias = $alias;
    }

    public function execute(array $records) : array {
        $db = $this->db;
        $fields = $this->db->get_preload_columns($this->table, $this->alias);

        return array_map(function($record) use ($db, $fields) {
            return $db->extract_fields_from_object($fields, $record);
        }, $records);
    }
}
