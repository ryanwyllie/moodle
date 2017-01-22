<?php

namespace core_calendar\local\event\visitors;

use core_calendar\local\event\visitors\visitable_interface;

interface moodle_visitor_interface {
    public function visit_module(visitable_interface $visitee);
}