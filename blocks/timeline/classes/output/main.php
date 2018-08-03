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
 * Class containing data for my overview block.
 *
 * @package    block_myoverview
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_timeline\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use core_course\external\course_summary_exporter;

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Class containing data for my overview block.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $courses = array_values(enrol_get_my_courses('*'));
        $nocoursesurl = $output->image_url('courses', 'block_myoverview')->out();
        $noeventsurl = $output->image_url('activities', 'block_myoverview')->out();

        $inprogresscourses = array_filter($courses, function($course) {
            return \course_classify_for_timeline($course) === COURSE_TIMELINE_INPROGRESS;
        });
        $formattedcourses = array_map(function($course) use ($output) {
            $context = \context_course::instance($course->id);
            $exporter = new course_summary_exporter($course, [
                'context' => $context
            ]);
            
            return $exporter->export($output);
        }, $inprogresscourses);
        $coursepages = array_chunk($formattedcourses, 6);

        return [
            'midnight' => usergetmidnight(time()),
            'coursepages' => $coursepages,
            'urls' => [
                'nocourses' => $nocoursesurl,
                'noevents' => $noeventsurl
            ]
        ];
    }
}
