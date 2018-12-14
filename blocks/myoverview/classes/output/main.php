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
namespace block_myoverview\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

require_once($CFG->dirroot . '/blocks/myoverview/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Class containing data for my overview block.
 *
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /**
     * Store the grouping preference
     *
     * @var string String matching the grouping constants defined in myoverview/lib.php
     */
    private $grouping;

    /**
     * Store the sort preference
     *
     * @var string String matching the sort constants defined in myoverview/lib.php
     */
    private $sort;

    /**
     * Store the view preference
     *
     * @var string String matching the view/display constants defined in myoverview/lib.php
     */
    private $view;

    /**
     * Store the paging preference
     *
     * @var string String matching the paging constants defined in myoverview/lib.php
     */
    private $paging;

    /**
     * main constructor.
     * Initialize the user preferences
     *
     * @param string $grouping Grouping user preference
     * @param string $sort Sort user preference
     * @param string $view Display user preference
     */
    public function __construct($grouping, $sort, $view, $paging) {
        $this->grouping = $grouping ? $grouping : BLOCK_MYOVERVIEW_GROUPING_ALL;
        $this->sort = $sort ? $sort : BLOCK_MYOVERVIEW_SORTING_TITLE;
        $this->view = $view ? $view : BLOCK_MYOVERVIEW_VIEW_CARD;
        $this->paging = $paging ? $paging : BLOCK_MYOVERVIEW_PAGING_12;
    }

    /**
     * Get the user preferences as an array to figure out what has been selected
     *
     * @return array $preferences Array with the pref as key and value set to true
     */
    public function get_preferences_as_booleans() {
        $preferences = [];
        $preferences[$this->view] = true;
        $preferences[$this->sort] = true;
        $preferences[$this->grouping] = true;

        return $preferences;
    }

    /*
    public function get_courses(
        renderer_base $renderer,
        string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null
    ) {
        global $USER;

        $requiredproperties = course_summary_exporter::define_properties();
        $fields = join(',', array_keys($requiredproperties));
        $hiddencourses = get_hidden_courses_on_timeline();
        $courses = [];

        // If the timeline requires the hidden courses then restrict the result to only $hiddencourses else exclude.
        if ($classification == COURSE_TIMELINE_HIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, $hiddencourses);
        } else {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
        }

        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

        if ($favourites) {
            $favouritecourseids = array_map(
                function($favourite) {
                    return $favourite->itemid;
                }, $favourites);
        }

        if ($classification == COURSE_FAVOURITES) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_favourites(
                $courses,
                $favouritecourseids,
                $limit
            );
        } else {
            list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
                $courses,
                $classification,
                $limit
            );
        }

        $formattedcourses = array_map(function($course) use ($renderer, $favouritecourseids) {
            \context_helper::preload_from_record($course);
            $context = \context_course::instance($course->id);
            $isfavourite = false;
            if (in_array($course->id, $favouritecourseids)) {
                $isfavourite = true;
            }
            $exporter = new course_summary_exporter($course, ['context' => $context, 'isfavourite' => $isfavourite]);
            return $exporter->export($renderer);
        }, $filteredcourses);

        return [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount
        ];
    }
    */

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        $limit = $this->paging;
        $sort = $this->sort == BLOCK_MYOVERVIEW_SORTING_TITLE ? 'fullname' : 'ul.timeaccess desc';
        $nocoursesurl = $output->image_url('courses', 'block_myoverview')->out();
        $result = course_get_enrolled_courses_by_timeline_classification(
            $output,
            $this->grouping,
            $limit + 1,
            0,
            $sort
        );

        $hasmorepages = count($result['courses']) > $limit;
        $courses = array_slice($result['courses'], 0, $limit);
        $hascourses = !empty($courses);
        $itemsperpage = [
            [
                'value' => BLOCK_MYOVERVIEW_PAGING_12,
                'active' => $limit == BLOCK_MYOVERVIEW_PAGING_12,
            ],
            [
                'value' => BLOCK_MYOVERVIEW_PAGING_24,
                'active' => $limit == BLOCK_MYOVERVIEW_PAGING_24,
            ],
            [
                'value' => BLOCK_MYOVERVIEW_PAGING_48,
                'active' => $limit == BLOCK_MYOVERVIEW_PAGING_48,
            ]
        ];
        $offset = $hasmorepages ? $result['nextoffset'] - 1 : $result['nextoffset'];

        if ($hascourses) {
            switch ($this->view) {
                case BLOCK_MYOVERVIEW_VIEW_CARD:
                    $template = 'block_myoverview/view-cards';
                    break;
                case BLOCK_MYOVERVIEW_VIEW_LIST:
                    $template = 'block_myoverview/view-list';
                    break;
                default:
                    $template = 'block_myoverview/view-summary';
                    break;
            }

            $content = $output->render_from_template($template, ['courses' => $courses]);
        } else {
            $content = $output->render_from_template('block_myoverview/no-courses', ['nocoursesimg' => $nocoursesurl]);
        }

        $templatecontext = [
            'nocoursesimg' => $nocoursesurl,
            'grouping' => $this->grouping,
            'sort' => $sort,
            'view' => $this->view,
            'paging' => $limit,
            'loadedcourses' => json_encode($courses),
            'initialoffset' => $offset,
            'pagedcontent' => [
                'ignorecontrolwhileloading' => true,
                'controlplacementbottom' => true,
                'skipjs' => true,
                'pagingbar' => [
                    'showitemsperpageselector' => true,
                    'itemsperpage' => $itemsperpage,
                    'skippages' => true,
                    'next' => $hascourses ? ['disabled' => !$hasmorepages] : false,
                    'previous' => $hascourses ? ['disabled' => true] : false
                ],
                'pages' => [
                    [
                        'page' => 1,
                        'active' => true,
                        'content' => $content
                    ]
                ]
            ]
        ];

        $preferences = $this->get_preferences_as_booleans();
        return array_merge($templatecontext, $preferences);
    }
}