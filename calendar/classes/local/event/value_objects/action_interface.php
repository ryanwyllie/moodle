<?php

namespace core_calendar\local\event\value_objects;

interface action_interface {

    /**
     * @return string
     */
    public function get_name();

    /**
     * @return \moodle_url
     */
    public function get_url();

    /**
     * Get the number of items that need actioning.
     *
     * @return int
     */
    public function get_item_count();
}
