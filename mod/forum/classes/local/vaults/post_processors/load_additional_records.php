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

namespace mod_forum\local\vaults\build_steps;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\vaults\sql_strategies\sql_strategy_interface;
use moodle_database;

/**
 * Build step.
 */
class load_additional_records {
    private $db;
    private $strategy;
    private $getwhereandorderbysqlcallback;

    public function __construct(moodle_database $db, sql_strategy_interface $strategy, callable $getwhereandorderbysqlcallback) {
        $this->db = $db;
        $this->strategy = $strategy;
        $this->getwhereandorderbysqlcallback = $getwhereandorderbysqlcallback;
    }

    public function execute(array $records) : array {
        [$wheresql, $params, $orderbysql] = $this->getwhereandorderbysqlcallback($this->strategy, $records);

        $sql = $strategy->generate_get_records_sql($wheresql, $orderbysql);
    }
}
