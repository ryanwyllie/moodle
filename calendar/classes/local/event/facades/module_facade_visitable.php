<?php

namespace core_calendar\local\event\facades;

use core_calendar\local\event\facades\module_facade_interface;
use core_calendar\local\event\visitors\visitable_interface;
use core_calendar\local\event\visitors\core_component_visitor_interface;

final class module_facade_visitable implements module_facade_interface, visitable_interface {
    private $modulename;

    public function __construct($modulename) {
        $this->modulename = strpos($modulename, 'mod_') === 0 ? substr($modulename, 4) : $modulename;
    }

    public function get_callback($callbackname) {
        return function(...$args) use ($callbackname) {
            return component_callback('mod_' . $this->modulename, $callbackname, $args);
        };
    }

    public function get_module_name() {
        return $this->modulename;
    }

    public function accept(core_component_visitor_interface $visitor) {
        return $visitor->visit_module($this);
    }
}