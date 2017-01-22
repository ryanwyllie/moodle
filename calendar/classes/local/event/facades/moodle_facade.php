<?php

namespace core_calendar\local\event\facades;

use core_calendar\local\event\facades\moodle_facade_interface;
use core_calendar\local\event\facades\module_facade_factory_interface;
use core_calendar\local\event\value_objects\course_module_interface;

final class moodle_facade implements moodle_facade_interface {

    private $factory;

    public function __construct(module_facade_factory_interface $factory) {
        $this->factory = $factory;
    }

    public function get_module(course_module_interface $module) {
        return $this->factory->create_instance($module->get_name());
    }
}