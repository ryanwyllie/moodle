<?php

namespace core_calendar\local\event\value_objects;

use core_calendar\local\event\value_objects\description_interface;

final class event_description implements description_interface {
    private $value;
    private $format;

    public function __construct($value, $format) {
        $this->value = $value;
        $this->formate = $format;
    }

    public function get_value() {
        return $this->value;
    }

    public function get_format() {
        return $this->format;
    }
}