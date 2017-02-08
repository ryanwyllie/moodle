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
 * Abstract event factory.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\factories;

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\event;
use core_calendar\local\event\entities\repeat_event_collection;
use core_calendar\local\event\proxies\std_proxy;
use core_calendar\local\event\value_objects\event_description;
use core_calendar\local\event\value_objects\event_times;
use core_calendar\local\interfaces\action_event_factory_interface;
use core_calendar\local\interfaces\event_factory_interface;
use core_calendar\local\interfaces\event_interface;

/**
 * Abstract factory for creating calendar events.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class event_abstract_factory implements event_factory_interface {
    /**
     * @var action_event_factory_interface $actioneventfactory Action event factory to be passed to component callbacks.
     */
    protected $actioneventfactory;

    /**
     * Applies component callbacks to the event.
     *
     * @param event_interface $event The event to be updated.
     *
     * @return event_interface The potentially modified event.
     */
    protected abstract function apply_component_callbacks(event_interface $event);

    /**
     * Constructor.
     *
     * @param action_event_factory_interface $actioneventfactory Action event factory to be passed to component callbacks.
     */
    public function __construct(action_event_factory_interface $actioneventfactory) {
        $this->actioneventfactory = $actioneventfactory;
    }

    public function create_instance(
        $id,
        $name,
        $descriptionvalue,
        $descriptionformat,
        $courseid,
        $groupid,
        $userid,
        $repeatid,
        $modulename,
        $moduleinstance,
        $type,
        $timestart,
        $timeduration,
        $timemodified,
        $timesort,
        $visible,
        $subscriptionid
    ) {
        $course = null;
        $group = null;
        $user = null;
        $module = null;

        if ($courseid) {
            $course = new std_proxy($courseid, function($id) {
                global $DB;
                return $DB->get_record('course', ['id' => $id]);
            });
        }

        if ($groupid) {
            $group = new std_proxy($groupid, function($id) {
                return groups_get_group($id, 'id,name,courseid');
            });
        }

        if ($userid) {
            $user = new std_proxy($userid, function($id) {
                global $DB;
                return $DB->get_record('user', ['id' => $id]);
            });
        }

        if ($moduleinstance && $modulename) {
            $module = new std_proxy($moduleinstance, function($id) use ($modulename) {
                return get_coursemodule_from_instance($modulename, $id);
            });
        }

        return $this->apply_component_callbacks(new event(
            $id,
            $name,
            new event_description($descriptionvalue, $descriptionformat),
            $course,
            $group,
            $user,
            new repeat_event_collection($id, $this),
            $module,
            $type,
            new event_times(
                (new \DateTimeImmutable())->setTimestamp($timestart),
                (new \DateTimeImmutable())->setTimestamp($timestart + $timeduration),
                (new \DateTimeImmutable())->setTimestamp($timesort ? $timesort : $timestart),
                (new \DateTimeImmutable())->setTimestamp($timemodified)
            ),
            !empty($visible),
            $subscriptionid
        ));
    }
}
