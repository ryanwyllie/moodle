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
 * Contains event class for creating a calendar event icon from
 * a calendar event.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\output;

defined('MOODLE_INTERNAL') || die();

use \core_calendar\local\event\entities\event_interface;

/**
 * Class to create a calendar event icon from a calendar event.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon_factory {

    /**
     * Create an icon for the given calendar event.
     *
     * @param \core_calendar\local\event\entities\event_interface $event
     * @return \core_calendar\output\icon
     */
    public static function create(event_interface $event) {
        $coursemodule = $event->get_course_module();
        $course = $event->get_course();
        $group = $event->get_group();
        $user = $event->get_user();
        $isactivityevent = !empty($coursemodule);
        $isglobalevent = ($course && $course->id == SITEID);
        $iscourseevent = ($course && !empty($course->id) && $course->id != SITEID && $group && empty($group->id));
        $isgroupevent = ($group && !empty($group->id));
        $isuserevent = ($user && !empty($user->id));

        if ($isactivityevent) {
            $key = 'icon';
            $component = $coursemodule->get_name();

            if (get_string_manager()->string_exists($event->get_type(), $coursemodule->get_name())) {
                $alttext = get_string($event->get_type(), $coursemodule->get_name());
            } else {
                $alttext = get_string('activityevent', 'calendar');
            }
        } else if ($isglobalevent) {
            $key = 'i/siteevent';
            $component = 'core';
            $alttext = get_string('globalevent', 'calendar');
        } else if ($iscourseevent) {
            $key = 'i/courseevent';
            $component = 'core';
            $alttext = get_string('courseevent', 'calendar');
        } else if ($isgroupevent) {
            $key = 'i/groupevent';
            $component = 'core';
            $alttext = get_string('groupevent', 'calendar');
        } else if ($isuserevent) {
            $key = 'i/userevent';
            $component = 'core';
            $alttext = get_string('userevent', 'calendar');
        } else {
            // Default to site event icon?
            $key = 'i/siteevent';
            $component = 'core';
            $alttext = get_string('globalevent', 'calendar');
        }

        return new icon($key, $component, $alttext);
    }
}
