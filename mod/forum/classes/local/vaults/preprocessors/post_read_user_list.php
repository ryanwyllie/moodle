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
class post_read_user_list {
    private const TABLE = 'forum_read';
    private $db;

    public function __construct(moodle_database $db) {
        $this->db = $db;
    }

    public function execute(array $records) : array {
        $userids = array_reduce($records, function($carry, $record) {
            $carry[$record->userid] = true;
            return $carry;
        }, []);
        $userids = array_keys($userids);
        $readrecords = $this->db->get_records_list(self::TABLE, 'userid', $userids);
        $useridsbypostid = array_reduce($readrecords, function($carry, $readrecord) {
            $postid = $readrecord->postid;
            $userid = $readrecord->userid;

            if (isset($readrecord[$postid])) {
                $carry[$postid][] = $userid;
            } else {
                $carry[$postid] = [$userid];
            }
        }, []);

        return array_map(function($record) use ($useridsbypostid) {
            $postid = $record->id;
            return isset($useridsbypostid[$postid]) ? $useridsbypostid[$postid] : [];
        }, $records);
    }
}
