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
 * Event cache class
 *
 * @package    core_calendar
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\dataaccess;

use core_cache\cache_loader;
use core_calendar\local\event\entities\event_interface;

/**
 * Event cache class
 *
 * This is a repository. It's called a vault to reduce confusion because
 * Moodle has already taken the name repository. Vault is cooler anyway.
 *
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_cache {

    private $loader;

    /**
     * Create an event cache.
     *
     */
    public function __construct(cache_loader $loader) {
        $this->loader = $loader;
    }

    public function save_event(event_interface $event) {
        $this->loader->set($event->get_id(), $event);
    }

    /**
     * Retrieve an event for the given id.
     *
     * @param in $id The event id
     * @return event_interface
     */
    public function get_event(int $id) {
        return $this->loader->get($id);
    }

    /**
     * Retrieve an array of events for the given user and time constraints.
     *
     * @param \stdClass            $user         The user for whom the events belong
     * @param int|null             $timesortfrom Events with timesort from this value (inclusive)
     * @param int|null             $timesortto   Events with timesort until this value (inclusive)
     * @param event_interface|null $afterevent   Only return events after this one
     * @param int                  $limitnum     Return at most this number of events
     * @return action_event_interface
     */
    public function get_action_events_by_timesort(
        \stdClass $user,
        int $timesortfrom = null,
        int $timesortto = null,
        event_interface $afterevent = null,
        int $limitnum = null
    ) {
        $timesortfrom = is_null($timesortfrom) ? '' : $timesortfrom;
        $timesortto   = is_null($timesortto) ? '' : $timesortto;
        $eventid      = is_null($afterevent) ? '' : $aftereven->get_id();
        $limitnum     = is_null($limitnum) ? '' : $limitnum;
        $key          = implode('_', [$user->id, $timesortfrom, $timesortto, $eventid, $limitnum]);
        $eventids     = $this->loader->get($key);

        if (is_bool($eventids)) {
            return $eventids;
        }

        return array_values($this->loader->get_many($eventids));
    }

    /**
     * Retrieve an array of events for the given user and time constraints.
     *
     * @param \stdClass            $user         The user for whom the events belong
     * @param int|null             $timesortfrom Events with timesort from this value (inclusive)
     * @param int|null             $timesortto   Events with timesort until this value (inclusive)
     * @param event_interface|null $afterevent   Only return events after this one
     * @param int                  $limitnum     Return at most this number of events
     * @return action_event_interface
     */
    public function save_action_events_by_timesort(
        \stdClass $user,
        int $timesortfrom = null,
        int $timesortto = null,
        event_interface $afterevent = null,
        int $limitnum = null,
        array $events = []
    ) {
        $timesortfrom = is_null($timesortfrom) ? '' : $timesortfrom;
        $timesortto   = is_null($timesortto) ? '' : $timesortto;
        $eventid      = is_null($afterevent) ? '' : $aftereven->get_id();
        $limitnum     = is_null($limitnum) ? '' : $limitnum;
        $key          = implode('_', [$user->id, $timesortfrom, $timesortto, $eventid, $limitnum]);
        $thisvalues   = [];
        $eventsvalues = [];

        foreach ($events as $event) {
            $thisvalues[] = $event->get_id();
            $eventsvalues[$event->get_id()] = $event;
        }

        $this->loader->set($key, $thisvalues);
        $this->loader->set_many($eventsvalues);
    }
}
