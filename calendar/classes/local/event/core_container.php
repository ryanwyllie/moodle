<?php

namespace core_calendar\local\event;

use core_calendar\local\event\dataaccess\event_vault_factory;
use core_calendar\local\event\entities\event_factory;
use core_calendar\local\event\entities\action_event_factory;
use core_calendar\local\event\facades\core_facade_visitable;
use core_calendar\local\event\facades\module_facade_factory;
use core_calendar\local\event\visitors\core_component_visitor;
use core_calendar\local\event\visitors\callback_event_visitor_immutable;

final class core_container {
    private static $event_factory;
    private static $core_facade;
    private static $module_facade_factory;
    private static $core_component_visitor;
    private static $action_event_factory;
    private static $event_vault;

    private static function init() {
        if (empty(self::$event_factory)) {
            self::$module_facade_factory = new module_facade_factory();
            self::$action_event_factory = new action_event_factory();
            self::$core_facade = new core_facade_visitable(self::$module_facade_factory);
            self::$core_component_visitor = new callback_event_visitor_immutable(self::$action_event_factory);
            self::$event_factory = new event_factory(self::$core_facade, self::$core_component_visitor);
        }

        if (empty(self::$event_vault)) {
            $vaultfactory = new event_vault_factory();
            self::$event_vault = $vaultfactory->create_instance(self::$event_factory);
        }
    }

    public static function get_event_factory() {
        self::init();
        return self::$event_factory;
    }

    /**
     * Return an event vault.
     *
     * @return event_vault_interface
     */
    public static function get_event_vault() {
        self::init();
        return self::$event_vault;
    }
}
