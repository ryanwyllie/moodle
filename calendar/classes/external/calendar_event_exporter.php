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
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\external;

defined('MOODLE_INTERNAL') || die();

use \core_calendar\local\event\container;
use \core_course\external\course_summary_exporter;
use \renderer_base;

/**
 * Class for displaying a calendar event.
 *
 * @package   core_calendar
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_event_exporter extends event_exporter_base {

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {

        $values = parent::define_other_properties();
        $values['url'] = ['type' => PARAM_URL];
        $values['mindaytimestamp'] = [
            'type' => PARAM_INT,
            'optional' => true
        ];
        $values['mindayerror'] = [
            'type' => PARAM_TEXT,
            'optional' => true
        ];
        $values['maxdaytimestamp'] = [
            'type' => PARAM_INT,
            'optional' => true
        ];
        $values['maxdayerror'] = [
            'type' => PARAM_TEXT,
            'optional' => true
        ];

        return $values;
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $values = parent::get_other_values($output);

        $event = $this->event;
        $eventid = $this->event->get_id();

        $url = new \moodle_url($this->related['daylink'], [], "event_{$eventid}");
        $values['url'] = $url->out(false);

        if ($event->get_course_module()) {
            $mapper = container::get_event_mapper();
            $starttime = $event->get_times()->get_start_time();

            list($min, $max) = component_callback(
                'mod_' . $event->get_course_module()->get('modname'),
                'core_calendar_get_valid_event_timestart_range',
                [$mapper->from_event_to_legacy_event($event)],
                [null, null]
            );

            if ($min) {
                // We need to check that the minimum valid time is earlier in the
                // day than the current event time so that if the user drags and drops
                // the event to this day (which changes the date but not the time) it
                // will result in a valid time start for the event.
                //
                // For example:
                // An event that starts on 2017-01-10 08:00 with a minimum cutoff
                // of 2017-01-05 09:00 means that 2017-01-05 is not a valid start day
                // for the drag and drop because it would result in the event start time
                // being set to 2017-01-05 08:00, which is invalid. Instead the minimum
                // valid start day would be 2017-01-06.
                $timestamp = $min[0];
                $errorstring = $min[1];
                $mindate = (new \DateTimeImmutable())->setTimestamp($timestamp);
                $minstart = $mindate->setTime(
                    $starttime->format('H'),
                    $starttime->format('i'),
                    $starttime->format('s')
                );

                if ($mindate < $minstart) {
                    $values['mindaytimestamp'] = usergetmidnight($timestamp);
                } else {
                    $values['mindaytimestamp'] = usergetmidnight($timestamp + DAYSECS);
                }

                // Get the human readable error message to display if the min day
                // timestamp is violated.
                $values['mindayerror'] = $errorstring;
            }

            if ($max) {
                // We're doing a similar calculation here as we are for the minimum
                // day timestamp. See the explanation above.
                $timestamp = $max[0];
                $errorstring = $max[1];
                $maxdate = (new \DateTimeImmutable())->setTimestamp($timestamp);
                $maxstart = $maxdate->setTime(
                    $starttime->format('H'),
                    $starttime->format('i'),
                    $starttime->format('s')
                );

                if ($maxdate > $maxstart) {
                    $values['maxdaytimestamp'] = usergetmidnight($timestamp);
                } else {
                    $values['maxdaytimestamp'] = usergetmidnight($timestamp - DAYSECS);
                }

                // Get the human readable error message to display if the max day
                // timestamp is violated.
                $values['maxdayerror'] = $errorstring;
            }
        }

        return $values;
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        $related = parent::define_related();
        $related['daylink'] = \moodle_url::class;

        return $related;
    }
}
