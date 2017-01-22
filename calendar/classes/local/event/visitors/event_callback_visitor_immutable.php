<?php

namespace core_calendar\local\event\visitors;

use core_calendar\local\event\visitors\moodle_visitor_interface;
use core_calendar\local\event\visitors\visitable_interface;

final class event_callback_visitor_immutable implements moodle_visitor_interface {
    private $factory;
    private $callback;

    public function __construct($factory, $callback = NULL) {
        $this->factory = $factory;
        $this->callback = $callback;
    }

    public function visit_module(visitable_interface $module) {
        return new event_callback_visitor_immutable($this->factory,
            function($event) use ($module) {
                return ($module->get_callback('transform_event'))($event, $this->factory);
            });
    }

    public function get_callback() {
        return $this->callback;
    }
}