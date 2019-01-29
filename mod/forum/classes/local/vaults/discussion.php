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

namespace mod_forum\local\vaults;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\vault;

/**
 * Vault class.
 */
class discussion extends vault {
    public function get_from_forum_id_and_group(int $forumid, ?int $groupid, ?int $sortorder, int $pagesize, int $pageno) {
        $strategy = $this->get_sql_strategy();
        $alias = $strategy->get_table_alias();
        $wheresql = "{$alias}.forum = :forumid";
        $params = [
            'forumid' => $forumid,
        ];

        if (null !== $groupid) {
            $wheresql .= " AND {$alias}.groupid = :groupid";
            $params['groupid'] = $groupid;
        }

        $limitfrom = $pageno * $pagesize;
        $sql = $strategy->generate_get_records_sql($wheresql, $strategy->get_sort_order($sortorder));
        $records = $this->get_db()->get_records_sql($sql, $params, $limitfrom, $pagesize);

        return $this->transform_db_records_to_entities($records);
    }
}
