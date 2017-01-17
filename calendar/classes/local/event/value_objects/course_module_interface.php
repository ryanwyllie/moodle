<?php

namespace core_calendar\local\event\value_objects;

interface course_module_interface {
    public function get_name();
    public function get_instance_id();
    public function get_instance();
}
