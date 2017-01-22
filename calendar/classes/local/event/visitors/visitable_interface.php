<?php

namespace core_calendar\local\event\visitors;

use core_calendar\local\event\visitors\moodle_visitor_interface;

interface visitable_interface {
    public function accept(moodle_visitor_interface $visitor);
}