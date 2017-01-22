<?php

require_once('../config.php');
global $CFG;

require_once($CFG->dirroot . '/calendar/lib.php');

$events = calendar_get_events_by_timesort();

foreach($events as $event) {
    if($event->get_repeats()->get_num() > 0) {
        echo "Got some repeats for " . $event->get_repeats()->get_id()  ." here boi. Like " . $event->get_repeats()->get_num();
        foreach($event->get_repeats() as $repeat) {
            echo "Another one:<br />";
            print_object($repeat);
        }
    }
}

// $moodlefacade = new \core_calendar\local\event\facades\moodle_facade(
//     new \core_calendar\local\event\facades\module_facade_factory()
// );

// $assignfacade = $moodlefacade->get_module('assign');
// $cb = $assignfacade->get_callback('transform_event');
// $cb($events[1]);
