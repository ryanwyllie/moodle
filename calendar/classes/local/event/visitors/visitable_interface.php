<?php

namespace core_calendar\local\event\visitors;

use core_calendar\local\event\visitors\core_component_visitor_interface;

interface visitable_interface {
    public function accept(core_component_visitor_interface $visitor);
}