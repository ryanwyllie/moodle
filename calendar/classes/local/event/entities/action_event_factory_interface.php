<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;

interface action_event_factory_interface {
    public function create_instance(
        event_interface $event,
        $action
    );
}