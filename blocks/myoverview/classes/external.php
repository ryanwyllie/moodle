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
require_once("$CFG->libdir/coursecatlib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/externallib.php');
require_once($CFG->libdir . '/completionlib.php');

use core_completion\progress;

class block_myoverview_external extends core_course_external {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.6
     */
    public static function mycourses_parameters() {
        return parent::get_enrolled_courses_by_timeline_classification_parameters();
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
    public static function mycourses(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null
    ) {
        global $PAGE, $CFG, $DB, $USER;
        
        $coursesbytimeline = parent::get_enrolled_courses_by_timeline_classification($classification,
            $limit, $offset, $sort);

        $hascourses = (count($coursesbytimeline['courses']) > 0 );

        $formattedcourses = array_map(function($course) use ($CFG) {

            // Find the course image if any.
            $courseinlist = new course_in_list($course);
            foreach ($courseinlist->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                if ($isimage) {
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    $course->courseimage = $url;
                    $course->classes = 'courseimage';
                    break;
                }
            }

            // Set a course pattern if no image found.
            $course->color = self::coursecolor($course->id);
            if (!isset($course->courseimage)) {
                $pattern = new \core_geopattern();
                $pattern->setColor($course->color);
                $pattern->patternbyid($course->id);
                $course->classes = 'coursepattern';
                $course->courseimage = $pattern->datauri();
            }

            // Add course completion info.
            $completion = new \completion_info($course);
            $course->progress = progress::get_course_progress_percentage($course);
            
            $course->hasprogress = false;
            if ($course->progress === 0 || $course->progress > 0) {
                $course->hasprogress = true;
            }

            if (!is_null($course->progress)) {
                $course->progress = floor($course->progress);
            }

            // Format the course summary text
            $course->summary = format_text($course->summary, $course->summaryformat);

            return $course;

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
    public static function mycourses_returns() {
        return new external_single_structure(
            array(
                'hascourses' => new external_value(PARAM_BOOL, 'has courses'),
                'courses' => new external_multiple_structure(self::get_myoverview_course_structure(), 'course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request')
            )
        );
    }

    /**
     * Returns a course structure definition
     *
     * @param  boolean $onlypublicdata set to true, to retrieve only fields viewable by anyone when the course is visible
     * @return array the course structure
     * @since  Moodle 3.6
     */
    protected static function get_myoverview_course_structure() {
        $coursestructure = array(
            'id' => new external_value(PARAM_INT, 'course id'),
            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
            'fullname' => new external_value(PARAM_TEXT, 'course full name'),
            'startdate' => new external_value(PARAM_INT, 'course start date', VALUE_OPTIONAL),
            'summary' => new external_value(PARAM_RAW, 'summary'),
            'courseimage' => new external_value(PARAM_RAW, 'course image'),
            'hasprogress' => new external_value(PARAM_BOOL, 'has progress'),
            'progress' => new external_value(PARAM_INT, 'course completion', VALUE_OPTIONAL),
            'favoritestate' => new external_value(PARAM_INT, 'is user favorite', VALUE_OPTIONAL),
            'viewurl' => new external_value(PARAM_URL, 'course url')

        );
        return new external_single_structure($coursestructure);
    }

    /**
     * These colors should be turned into configurables.
     */
    protected static function coursecolor($courseid) {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];

        $color = $basecolors[$courseid % 10];
        return $color;
    }
}
