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

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\vault;

/**
 * Vault class.
 */
class discussion extends vault {
    public function __construct(\moodle_database $db, string $table = 'forum_discussions', callable $transformtoentities = null) {
        if (is_null($transformtoentities)) {
            $transformtoentities = function(array $records) {
                return array_map(function($record) {
                    return new discussion_entity(
                        $record->id,
                        $record->course,
                        $record->forum,
                        $record->name,
                        $record->firstpost,
                        $record->userid,
                        $record->groupid,
                        $record->assessed,
                        $record->timemodified,
                        $record->usermodified,
                        $record->timestart,
                        $record->timeend,
                        $record->pinned
                    );
                }, $records);
            };
        }

        return parent::__construct($db, $table, $transformtoentities);
    }
}
