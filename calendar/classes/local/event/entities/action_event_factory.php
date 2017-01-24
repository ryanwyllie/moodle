<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\entities\action_event_factory_interface;
use core_calendar\local\event\entities\action_event;
use core_calendar\local\event\value_objects\action_interface;

final class action_event_factory implements action_event_factory_interface {

    public function create_instance(
        event_interface $event,
        action_interface $action
    ) {
        return new action_event($event, $action);
    }
}
