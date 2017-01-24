<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;

final class action_event implements action_event_interface {
    private $event;
    private $action;

    public function __construct(event_interface $event, $action) {
        $this->event = $event;
        $this->action = $action;
    }

    public function get_id() {
        return $this->event->get_id();
    }

    public function get_name() {
        return $this->event->get_name();
    }

    public function get_description() {
        return $this->event->get_description();
    }

    public function get_course() {
        return $this->event->get_course();
    }

    public function get_course_module() {
        return $this->event->get_course_module();
    }

    public function get_group() {
        return $this->event->get_group();
    }

    public function get_user() {
        return $this->event->get_user();
    }

    public function get_type() {
        return $this->event->get_type();
    }

    public function get_times() {
        return $this->event->get_times();
    }

    public function get_repeats() {
        return $this->event->get_repeats();
    }

    public function get_subscription_id() {
        return $this->event->get_subscription_id();
    }

    public function is_visible() {
        return $this->event->is_visible();
    }

    public function get_action() {
        return $this->action;
    }
}
