<?php

require_once('../config.php');
global $CFG;

require_once($CFG->dirroot . '/calendar/lib.php');

$events = calendar_get_action_events_by_timesort();

foreach($events as $event) {
    print_object($event);
}

// $moodlefacade = new \core_calendar\local\event\facades\core_facade(
//     new \core_calendar\local\event\facades\module_facade_factory()
// );

// $assignfacade = $moodlefacade->get_module('assign');
// $cb = $assignfacade->get_callback('transform_event');
// $cb($events[1]);
