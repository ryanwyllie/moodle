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
 * Forum class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\data_mappers\database;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\factories\entity as entity_factory;

/**
 * Forum class.
 */
class forum implements db_data_mapper_interface {
    private $entityfactory;

    public function __construct(entity_factory $entityfactory) {
        $this->entityfactory = $entityfactory;
    }

    public function from_db_records(array $results) : array {
        $entityfactory = $this->entityfactory;

        return array_map(function(array $result) use ($entityfactory) {
            [
                'forum' => $forumrecord,
                'course_module' => $coursemodule,
                'course' => $course,
                'context' => $context,
            ] = $result;
            return $entityfactory->get_forum_from_stdClass($forumrecord, $context, $coursemodule, $course);
        }, $results);
    }
}
