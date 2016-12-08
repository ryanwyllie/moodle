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
 * Todo.
 *
 * @package    core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\todo;
defined('MOODLE_INTERNAL') || die();

use core\todo as todo;

class repository {

    public function create(todo $todo) {
        global $DB;

        $id = $DB->insert_record('todo', $todo->to_array());

        return $this->copy($todo, ['id' => $id]);
    }

    public function retrieve($uniqueid) {
        global $DB;

        $record = $DB->get_record('todo', ['uniqueid' => $uniqueid]);

        if ($record) {
            return $this->create_from_db($record);
        } else {
            return null;
        }
    }

    public function update(todo $todo) {
        global $DB;

        $DB->update_record('todo', $todo->to_array());

        return $todo;
    }

    public function delete(todo $todo) {
        global $DB;

        $DB->delete_records('todo', ['uniqueid' => $uniqueid]);

        return $todo;
    }

    public function query($limit, $offset) {
        global $DB;

        $records = $DB->get_records('todo', null, '', '*', $offset, $limit);

        return array_map([$this, 'create_from_db'], array_values($records));
    }

    private function create_from_db($data) {
        return new todo(
            $data->id,
            $data->uniqueid,
            $data->contextname,
            $data->contexturl,
            $data->courseid,
            $data->iconurl,
            $data->startdate,
            $data->enddate,
            $data->itemcount,
            $data->actionname,
            $data->actionurl,
            $data->actionstartdate
        );
    }

    private function copy(todo $todo, $properties) {
        return new todo(
            isset($properties['id']) ? $properties['id'] : $todo->get_id(),
            isset($properties['uniqueid']) ? $properties['uniqueid'] : $todo->get_unique_id(),
            isset($properties['contextname']) ? $properties['contextname'] : $todo->get_context_name(),
            isset($properties['contexturl']) ? $properties['contexturl'] : $todo->get_context_url(),
            isset($properties['courseid']) ? $properties['courseid'] : $todo->get_course_id(),
            isset($properties['iconurl']) ? $properties['iconurl'] : $todo->get_icon_url(),
            isset($properties['startdate']) ? $properties['startdate'] : $todo->get_start_date(),
            isset($properties['enddate']) ? $properties['enddate'] : $todo->get_end_date(),
            isset($properties['itemcount']) ? $properties['itemcount'] : $todo->get_item_count(),
            isset($properties['actionname']) ? $properties['actionname'] : $todo->get_action_name(),
            isset($properties['actionurl']) ? $properties['actionurl'] : $todo->get_action_url(),
            isset($properties['actionstartdate']) ? $properties['actionstartdate'] : $todo->get_action_start_date()
        );
    }
}
