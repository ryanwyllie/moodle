<?php

namespace core_calendar\local\event\visitors;

use core_calendar\local\event\visitors\core_component_visitor_interface;
use core_calendar\local\event\facades\module_facade_interface;
use core_calendar\local\event\facades\core_facade_interface;

final class callback_event_visitor_immutable implements core_component_visitor_interface {
    private $factory;
    private $callbacks;

    public function __construct($factory, $callbacks = []) {
        $this->factory = $factory;
        $this->callbacks = $callbacks;
    }

    public function visit_core(core_facade_interface $core) {
        return $this;
    }

    public function visit_module(module_facade_interface $module) {
        $factory = $this->factory;
        $callback = function($event) use ($module, $factory) {
            $callback = $module->get_callback('transform_event');
            return $callback($event, $factory);
        };

        return new callback_event_visitor_immutable($this->factory, array_merge_recursive(
            $this->callbacks,
            ['mod' => [$module->get_module_name() => \Closure::bind($callback, NULL)]]));
    }

    public function get_callbacks() {
        return $this->callbacks;
    }
}
