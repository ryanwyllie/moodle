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

namespace mod_forum\local\vaults\sql_strategies;

defined('MOODLE_INTERNAL') || die();

/**
 * Vault class.
 */
class single_table implements sql_strategy_interface {
    private $table;

    public function __construct(string $table) {
        $this->table = $table;
    }

    public function get_table() : string {
        return $this->table;
    }

    public function get_table_alias() : string {
        return 't';
    }

    public function generate_get_records_sql(string $wheresql = null, string $sortsql = null) : string {
        $selectsql = 'SELECT * FROM {' . $this->get_table() . '} ' . $this->get_table_alias();
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }
}
