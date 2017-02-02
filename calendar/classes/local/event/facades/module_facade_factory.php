<?php

namespace core_calendar\local\event\facades;

use core_calendar\local\event\facades\module_facade_visitable;
use core_calendar\local\event\facades\module_facade_factory_interface;

final class module_facade_factory implements module_facade_factory_interface {
    public function create_instance($modulename) {
        return new module_facade_visitable($modulename);
    }
}