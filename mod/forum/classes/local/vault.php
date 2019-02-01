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

use mod_forum\local\factories\entity as entity_factory;
use moodle_database;

/**
 * Vault class.
 */
abstract class vault {
    private $db;
    private $entityfactory;

    public function __construct(
        moodle_database $db,
        entity_factory $entityfactory
    ) {
        $this->db = $db;
        $this->entityfactory = $entityfactory;
    }

    abstract protected function get_table_alias() : string;
    abstract protected function generate_get_records_sql(string $wheresql = null, string $sortsql = null) : string;
    abstract protected function from_db_records(array $results);

    protected function get_preprocessors() : array {
        return [];
    }

    protected function get_db() : moodle_database {
        return $this->db;
    }

    protected function get_entity_factory() : entity_factory {
        return $this->entityfactory;
    }

    protected function transform_db_records_to_entities(array $records) {
        $preprocessors = $this->get_preprocessors();
        $result = array_map(function($record) {
            return ['record' => $record];
        }, $records);

        $result = array_reduce(array_keys($preprocessors), function($carry, $preprocessor) use ($records, $preprocessors) {
            $step = $preprocessors[$preprocessor];
            $dependencies = $step->execute($records);

            foreach ($dependencies as $index => $dependency) {
                // Add the new dependency to the list.
                $carry[$index] = array_merge($carry[$index], [$preprocessor => $dependency]);
            }

            return $carry;
        }, $result);

        return $this->from_db_records($result);
    }

    public function get_from_id(int $id) {
        $records = $this->get_from_ids([$id]);
        return count($records) ? array_shift($records) : null;
    }

    public function get_from_ids(array $ids) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($ids);
        $wheresql = $alias . '.id ' . $insql;
        $sql = $this->generate_get_records_sql($wheresql);
        $records = $this->get_db()->get_records_sql($sql, $params);

        return $this->transform_db_records_to_entities($records);
    }
}
