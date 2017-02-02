<?php

namespace core_calendar\local\event\facades;

interface module_facade_factory_interface {
    public function create_instance($modulename);
}