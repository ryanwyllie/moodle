<?php

namespace core_calendar\local\event\value_objects;

use core_calendar\local\event\value_objects\course_module_interface;

final class event_course_module implements course_module_interface {
    private $id;
    private $name;
    private $instance;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_instance_id() {
        return $this->id;
    }

    public function get_instance() {
        return $this->instance = $this->instance ? $this->instance
                               : get_coursemodule_from_instance($this->name, $this->id);

    }
}


