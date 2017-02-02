<?php

namespace core_calendar\local\event\facades;

use core_calendar\local\event\facades\core_facade_interface;
use core_calendar\local\event\facades\module_facade_factory_interface;
use core_calendar\local\event\value_objects\course_module_interface;
use core_calendar\local\event\visitors\visitable_interface;
use core_calendar\local\event\visitors\core_component_visitor_interface;

final class core_facade_visitable implements core_facade_interface, visitable_interface {

    private $components;

    public function __construct(module_facade_factory_interface $modfactory) {
        $componentnames = array_keys(\core_component::get_plugin_list('mod'));
        foreach(\core_component::get_plugin_list('mod') as $componentname => $dir) {
            $this->components['mod'][$componentname] = $modfactory->create_instance($componentname);
        }
    }

    public function get_module(course_module_interface $module) {
        return $this->components['mod'][$module->get_name()];
    }

    public function accept(core_component_visitor_interface $visitor) {
        return array_reduce($this->components['mod'], function($visitorcarry, $component) {
            return $component->accept($visitorcarry);
        }, $visitor)->visit_core($this);
    }
}
