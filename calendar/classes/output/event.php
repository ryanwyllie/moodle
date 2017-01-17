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
 * Contains event class for displaying a calendar event.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use \core_calendar\local\event\entities\event_interface;
use \core_calendar\local\event\entities\action_event_interface;
use \core_calendar\local\event\proxies\proxy_interface;
use \core_calendar\local\event\value_objects\action_interface;

/**
 * Class for displaying a calendar event.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event implements templatable, renderable {

    /**
     * @var event_interface The calendar event.
     */
    protected $event;

    /**
     * Constructor.
     *
     * @param event_interface $event
     */
    public function __construct(event_interface $event) {
        $this->event = $event;
    }

    /**
     * Get the output for a template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $event = $this->event;
        $starttimestamp = $event->get_times()->get_starttime()->getTimestamp();
        $endtimestamp = $event->get_times()->get_endtime()->getTimestamp();
        $icon = icon_factory::create($event)->export_for_template($output);

        $serialisedevent = [
            'id' => $event->get_id(),
            'name' => $event->get_name(),
            'description' => $event->get_description()->get_value(),
            'format' => $event->get_description()->get_format(),
            'groupid' => $event->get_group()->id,
            'userid' => $event->get_user()->id,
            'repeatid' => $event->get_repeats()->get_id(),
            'eventtype' => $event->get_type(),
            'timestart' => $starttimestamp,
            'timeduration' => $endtimestamp - $starttimestamp,
            'timesort' => $event->get_times()->get_sorttime()->getTimestamp(),
            'visible' => $event->is_visible(),
            'timemodified' => $event->get_times()->get_modifiedtime()->getTimestamp(),
            'enddate' => $this->get_formatted_timesort($event),
            'icon' => $icon,
            'course' => $this->serialise_course($event->get_course()),
        ];

        if ($coursemodule = $event->get_course_module()) {
            $serialisedevent['url'] = $this->get_url($event)->out();
            $serialisedevent['modulename'] = $coursemodule->get_name();
            $serialisedevent['instance'] = $coursemodule->get_instance_id();
        }

        if ($event instanceof action_event_interface) {
            $serialisedevent['action'] = $this->serialise_action($event->get_action());
        }

        return $serialisedevent;
    }

    /**
     * Construct the URL for this event based on the course module.
     *
     * @param event_interface $event
     * @return \moodle_url
     */
    protected function get_url(event_interface $event) {
        $modulename = $event->get_course_module()->get_name();
        $moduleid = $event->get_course_module()->get_instance()->id;
        $url = new \moodle_url(sprintf('/mod/%s/view.php', $modulename), ['id' => $moduleid]);

        return $url;
    }

    /**
     * Format the time sort timestamp for the current event into a
     * human readable string using the user's calendar time representation.
     *
     * @param event_interface $event
     * @return string
     */
    protected function get_formatted_timesort(event_interface $event) {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $timesort = $event->get_times()->get_sorttime()->getTimestamp();

        return userdate($timesort, get_string('strftimerecent'));
    }

    /**
     * Get a formatted version of the course name for the current event.
     *
     * @param proxy_interface $course
     * @return string
     */
    protected function get_formatted_course_name($course) {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        return course_format_name($course);
    }

    /**
     * Return a serialised representation of the course for the
     * current event.
     *
     * @param proxy_interface $course
     * @return array
     */
    protected function serialise_course($course) {
        return [
            'id' => $course->id,
            'name' => $this->get_formatted_course_name($course),
        ];
    }

    /**
     * Return a serialised representation of the course for the
     * current event.
     *
     * @param action_interface $action
     * @return array
     */
    protected function serialise_action(action_interface $action) {
        return [
            'name' => $action->get_name(),
            'url' => $action->get_url()->out(),
            'itemcount' => $action->get_item_count()
        ];
    }
}
