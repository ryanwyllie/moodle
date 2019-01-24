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

use mod_forum\local\data_mappers\database\db_data_mapper_interface;
use mod_forum\local\vaults\sql_strategies\sql_strategy_interface;
use context_helper;
use moodle_database;

/**
 * Vault class.
 */
class vault {
    private $sqlstrategy;
    private $db;
    private $datamapper;
    private $preprocessors;

    public function __construct(
        moodle_database $db,
        sql_strategy_interface $sqlstrategy,
        db_data_mapper_interface $datamapper,
        array $preprocessors = []
    ) {
        $this->db = $db;
        $this->sqlstrategy = $sqlstrategy;
        $this->datamapper = $datamapper;
        $this->preprocessors = $preprocessors;
    }

    protected function get_db() : moodle_database {
        return $this->db;
    }

    protected function get_sql_strategy() : sql_strategy_interface {
        return $this->sqlstrategy;
    }

    protected function get_data_mapper() : db_data_mapper_interface {
        return $this->datamapper;
    }

    protected function transform_db_records_to_entities(array $records) {
        $result = array_map(function($record) {
            return [$record];
        }, $records);

        $result = array_reduce($this->preprocessors, function($carry, $step) use ($records) {
            $dependencies = $step->execute($records);

            foreach ($dependencies as $index => $dependency) {
                // Add the new dependency to the list.
                $carry[$index] = array_merge($carry[$index], [$dependency]);
            }

            return $carry;
        }, $result);

        return $this->get_data_mapper()->from_db_records($result);
    }

    public function get_from_id(int $id) {
        $records = $this->get_from_ids([$id]);
        return count($records) ? array_shift($records) : null;
    }

    public function get_from_ids(array $ids) {
        $strategy = $this->get_sql_strategy();
        $alias = $strategy->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($ids);
        $wheresql = $alias . '.id ' . $insql;
        $sql = $strategy->generate_get_records_sql($wheresql);
        $records = $this->get_db()->get_records_sql($sql, $params);

        return $this->transform_db_records_to_entities($records);
    }
}
