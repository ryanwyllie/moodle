<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;

interface action_event_interface extends event_interface {
    /**
     * @return \core_calendar\local\event\value_objects\action_interface
     */
    public function get_action();
}
