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

use mod_forum\local\vaults\preprocessors\extract_user;
use user_picture;

/**
 * Vault class.
 */
class single_table_with_user implements sql_strategy_interface {
    private $table;
    private const USER_ID_ALIAS = 'userpictureid';
    private const USER_ALIAS = 'userrecord';

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
        $alias = $this->get_table_alias();
        $fields = $alias . '.*, ' . user_picture::fields('u', null, self::USER_ID_ALIAS, self::USER_ALIAS);
        $tables = '{' . $this->get_table() . '} ' . $alias;
        $tables .= ' JOIN {user} u ON u.id = ' . $alias . '.userid';

        $selectsql = 'SELECT ' . $fields . ' FROM ' . $tables;
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    public function get_preprocessors() : array {
        return [new extract_user(self::USER_ID_ALIAS, self::USER_ALIAS)];
    }
}
