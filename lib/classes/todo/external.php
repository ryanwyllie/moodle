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
 * Core grades external functions
 *
 * @package    core_grades
 * @copyright  2012 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */

namespace core\todo;
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * core grades functions
 */
class external extends \external_api {
    public static function query_todos_parameters() {
        return new \external_function_parameters(
            array(
                'limit' => new \external_value(PARAM_INT, 'Context ID', VALUE_DEFAULT, 0),
                'offset' => new \external_value(PARAM_INT, 'Context ID', VALUE_DEFAULT, 0),
            ));
    }

    public static function query_todos_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'id' => new \external_value(PARAM_INT, 'id'),
                    'uniqueid' => new \external_value(PARAM_TEXT, 'unique id'),
                    'contextname' => new \external_value(PARAM_TEXT, 'context name'),
                    'contexturl' => new \external_value(PARAM_URL, 'content url'),
                    'courseid' => new \external_value(PARAM_INT, 'course id'),
                    'iconurl' => new \external_value(PARAM_URL, 'icon url'),
                    'startdate' => new \external_value(PARAM_INT, 'start date'),
                    'enddate' => new \external_value(PARAM_INT, 'end date'),
                    'itemcount' => new \external_value(PARAM_INT, 'item count'),
                    'actionname' => new \external_value(PARAM_TEXT, 'action name'),
                    'actionurl' => new \external_value(PARAM_URL, 'action url'),
                    'actionstartdate' => new \external_value(PARAM_INT, 'action start date'),
                )
            )
        );
    }

    public static function query_todos($limit, $offset) {
        global $USER;

        self::validate_parameters(self::query_todos_parameters(), [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        self::validate_context(\context_system::instance());

        return array_map(function($todo) {
           return $todo->to_array();
        }, api::get_for_user($USER));
    }
}
