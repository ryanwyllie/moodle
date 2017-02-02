<?php

namespace core_calendar\local\event\visitors;

use core_calendar\local\event\facades\core_facade_interface;
use core_calendar\local\event\facades\module_facade_interface;

interface core_component_visitor_interface {
    public function visit_core(core_facade_interface $core);
    public function visit_module(module_facade_interface $module);

    //probably should put more here for futureproofing or something?
}