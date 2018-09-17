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
 * Class for exporting a myoverview course
 *
 * @package    block_myoverview
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_myoverview\external;
defined('MOODLE_INTERNAL') || die();

use renderer_base;
use moodle_url;
use core_completion\progress;

/**
 * Exporter of a course overview course
 *
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class myoverview_course_exporter extends \core\external\exporter {

    protected static function define_related() {
        // We cache the context so it does not need to be retrieved from the course.
        return array('context' => '\\context');
    }

    /**
     * Get the additionval values to inject while exporting
     *
     * @param renderer_base $output
     * @return array Keys and the property names, values and their values.
     */
    protected function get_other_values(renderer_base $output) {
        $courseimage = self::get_course_image($this->data);
        if (!$courseimage) {
            $courseimage = self::get_course_pattern($this->data);
        }
        $progress = self::get_course_progress($this->data);
        $hasprogress = false;
        if ($progress === 0 || $progress > 0) {
            $hasprogress = true;
        }
        return array(
            'courseimage' => $courseimage,
            'progress' => $progress,
            'hasprogress' => $hasprogress
        );
    }

    /**
     * Returns a list of objects that are related
     *
     * @return array
     */
    public static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
            ),
            'fullname' => array(
                'type' => PARAM_TEXT,
            ),
            'shortname' => array(
                'type' => PARAM_TEXT,
            ),
            'idnumber' => array(
                'type' => PARAM_RAW,
            ),
            'summary' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED
            ),
            'summaryformat' => array(
                'type' => PARAM_INT,
            ),
            'startdate' => array(
                'type' => PARAM_INT,
            ),
            'enddate' => array(
                'type' => PARAM_INT,
            ),
            'fullnamedisplay' => array(
                'type' => PARAM_RAW,
            ),
            'viewurl' => array(
                'type' => PARAM_URL,
            ),
        );
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    public static function define_other_properties() {
        return array(
            'courseimage' => array(
                'type' => PARAM_RAW,
            ),
            'progress' => array(
                'type' => PARAM_INT,
                'optional' => true
            ),
            'hasprogress' => array(
                'type' => PARAM_BOOL
            )
        );
    }

    /**
     * Get the course image if added to course.
     *
     * @return string url of course image
     */
    public static function get_course_image($course) {
        global $CFG;
        $courseinlist = new \core_course_list_element($course);
        foreach ($courseinlist->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return \file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'.
                    $file->get_contextid().
                    '/'.
                    $file->get_component().
                    '/'.
                    $file->get_filearea().
                    $file->get_filepath().
                    $file->get_filename(),
                false);
            }
        }
        return false;
    }

    /**
     * Get the course pattern datauri.
     *
     * The datauri is an encoded svg that can be passed as a url.
     * @return string datauri
     */
    public static function get_course_pattern($course) {
        $color = self::coursecolor($course->id);
        $pattern = new \core_geopattern();
        $pattern->setColor($color);
        $pattern->patternbyid($course->id);
        return $pattern->datauri();
    }

    /** 
     * Get the course progress percentage.
     *
     * @return int progress
     */
    public static function get_course_progress($course) {
        // Add course completion info.
        $completion = new \completion_info($course);
        return floor(progress::get_course_progress_percentage($course));
    }

    /**
     * Get the course color.
     *
     * @return string hex color code.
     */
    public static function coursecolor($courseid) {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];

        $color = $basecolors[$courseid % 10];
        return $color;
    }
}
