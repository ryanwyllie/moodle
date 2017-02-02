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
 * Calendar event class
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\entities\event_collection_interface;
use core_calendar\local\event\proxies\proxy_interface;
use core_calendar\local\event\value_objects\times_interface;
use core_calendar\local\event\value_objects\description_interface;
use core_calendar\local\event\value_objects\course_module_interface;

/**
 * Class representing a calendar event
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class event implements event_interface {
    /**
     * @var int $id The event's id in the database
     */
    private $id;

    /**
     * @var string $name The name of this event
     */
    private $name;

    /**
     * @var description_interface $description Description for this event
     */
    private $description;

    /**
     * @var proxy_interface $course Course for this event
     */
    private $course;

    /**
     * @var proxy_interface $group Group for this event
     */
    private $group;

    /**
     * @var proxy_interface $user User for this event
     */
    private $user;

    /**
     * @var event_collection_interface $repeats Collection of repeat events
     */
    private $repeats;

    /**
     * @var string type The type of this event
     */
    private $type;

    /**
     * @var times_interface $times The times for this event
     */
    private $times;

    /**
     * @var bool $visible The visibility of this event
     */
    private $visible;

    /**
     * @var int $subscriptionid The event's subscription ID
     */
    private $subscriptionid;

    /**
     * Constructor
     *
     * @param int                        $id             The event's ID in the database
     * @param string                     $name           The event's name
     * @param description_interface      $description    The event's description
     * @param proxy_interface            $course         The course associated with the event
     * @param proxy_interface            $group          The group associated with the event
     * @param proxy_interface            $user           The user associated with the event
     * @param event_collection_interface $coursemodule   The course module that created the event
     * @param string                     $type           The event's type
     * @param times_interface            $times          The times associated with the event
     * @param bool                       $visible        The event's visibility true for visible, false for invisible
     * @param int                        $subscriptionid The event's subscription ID
     */
    public function __construct(
        $id,
        $name,
        description_interface $description,
        proxy_interface $course,
        proxy_interface $group,
        proxy_interface $user,
        event_collection_interface $repeats,
        course_module_interface $coursemodule = NULL,
        $type,
        times_interface $times,
        $visible,
        $subscriptionid
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->course = $course;
        $this->group = $group;
        $this->user = $user;
        $this->repeats = $repeats;
        $this->coursemodule = $coursemodule;
        $this->type = $type;
        $this->times = $times;
        $this->visible = $visible;
        $this->subscriptionid = $subscriptionid;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_course() {
        return $this->course;
    }

    public function get_course_module() {
        return $this->coursemodule;
    }

    public function get_group() {
        return $this->group;
    }

    public function get_user() {
        return $this->user;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_times() {
        return $this->times;
    }

    public function get_repeats() {
        return $this->repeats;
    }

    public function get_subscription_id() {
        return $this->subscriptionid;
    }

    public function is_visible() {
        return $this->visible;
    }
}
