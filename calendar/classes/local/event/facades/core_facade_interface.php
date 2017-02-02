<?php

namespace core_calendar\local\event\facades;

use core_calendar\local\event\value_objects\course_module_interface;

interface core_facade_interface {
    public function get_module(course_module_interface $module);
}