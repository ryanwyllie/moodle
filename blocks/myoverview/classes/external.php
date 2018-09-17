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
 * Page external API
 *
 * @package    block_myoverview
 * @category   external
 * @copyright  2018 Bas brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.6
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/externallib.php');
require_once($CFG->libdir . '/completionlib.php');

use core_completion\progress;

use block_myoverview\external\myoverview_course_exporter;

class block_myoverview_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.6
     */
    public static function get_enrolled_courses_by_timeline_classification_parameters() {
        return new external_function_parameters(
            array(
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null)
            )
        );
    }

    /**
     * Get courses matching the given timeline classification based on the extended webservice,
     * add additional course information like images and progress.
     *
     * @param  string $classification past, inprogress, or future
     * @param  int $limit Result set limit
     * @param  int $offset Result set offset
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     */
    public static function get_enrolled_courses_by_timeline_classification(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null
    ) {
        global $PAGE, $CFG, $DB, $USER, $OUTPUT;
        
        $coursesbytimeline = \core_course_external::get_enrolled_courses_by_timeline_classification($classification,
            $limit, $offset, $sort);

        $hascourses = (count($coursesbytimeline['courses']) > 0 );

        $renderer = $PAGE->get_renderer('core');

        $formattedcourses = array_map(function($course) use ($CFG, $renderer) {
            $context = context_course::instance($course->id);
            $exporter = new myoverview_course_exporter($course, ['context' => $context]);            
            return $exporter->export($renderer);
        }, $coursesbytimeline['courses']);

        return [
            'hascourses' => $hascourses,
            'courses' => $formattedcourses,
            'nextoffset' => $coursesbytimeline['nextoffset']
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.6
     */
    public static function get_enrolled_courses_by_timeline_classification_returns() {
        return new external_single_structure(
            array(
                'hascourses' => new external_value(PARAM_BOOL, 'has courses'),
                'courses' => new external_multiple_structure(myoverview_course_exporter::get_read_structure(), 'Course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request')
            )
        );
    }
}
