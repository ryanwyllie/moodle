<?php

require_once('../config.php');
require_once($CFG->dirroot . '/calendar/lib.php');

$events = calendar_get_action_events_by_timesort();

print_object($events);
