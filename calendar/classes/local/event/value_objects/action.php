<?php

namespace core_calendar\local\event\value_objects;

final class action implements action_interface {

    // from DB
    private $name;
    private $url;
    private $itemcount;

    public function __construct(
        $name,
        \moodle_url $url,
        $itemcount = null
    ) {
        $this->name = $name;
        $this->url = $url;
        $this->itemcount = $itemcount;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_url() {
        return $this->url;
    }

    public function get_item_count() {
        return $this->itemcount;
    }
}
