<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\entities\event_collection_interface;
use core_calendar\local\event\proxies\proxy_interface;
use core_calendar\local\event\value_objects\times_interface;
use core_calendar\local\event\value_objects\description_interface;
use core_calendar\local\event\value_objects\course_module_interface;

final class event implements event_interface {

    // from DB
    private $id;
    private $name;
    private $description;
    private $course;
    private $group;
    private $user;
    private $repeatid;
    private $modulename;
    private $moduleinstance;
    private $type;
    private $times;
    private $visible;
    private $timemodified;
    private $subscriptionid;

    // extra shit
    private $repeats;

    public function __construct(
        $id,
        $name,
        description_interface $description,
        proxy_interface $course,
        proxy_interface $group,
        proxy_interface $user,
        event_collection_interface $repeats,
        course_module_interface $coursemodule = NULL,
        $type,
        times_interface $times,
        $visible,
        $subscriptionid
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->course = $course;
        $this->group = $group;
        $this->user = $user;
        $this->repeats = $repeats;
        $this->coursemodule = $coursemodule;
        $this->type = $type;
        $this->times = $times;
        $this->visible = $visible;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_course() {
        return $this->course;
    }

    public function get_course_module() {
        return $this->coursemodule;
    }

    public function get_group() {
        return $this->group;
    }

    public function get_user() {
        return $this->user;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_times() {
        return $this->times;
    }

    public function get_repeats() {
        return $this->repeats;
    }

    public function is_visible() {
        return $this->visible;
    }

    public function get_subscription_id() {
        return $this->subscriptionid;
    }
}
