<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;

interface action_event_interface extends event_interface {
    /**
     * No interface for return type yet
     */
    public function get_action();
}
