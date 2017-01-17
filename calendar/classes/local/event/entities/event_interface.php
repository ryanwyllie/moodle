<?php

namespace core_calendar\local\event\entities;

interface event_interface {

    /**
     * @return integer
     */
    public function get_id();

    /**
     * @return string
     */
    public function get_name();

    /**
     * @return \core_calendar\local\event\value_objects\description_interface
     */
    public function get_description();

    /**
     * Get the course object associated with the event
     *
     * @return \core_calendar\local\event\proxies\proxy_interface
     */
    public function get_course();

    /**
     * Get the course module object that created the event
     *
     * @return \core_calendar\local\event\proxies\course_module_interface
     */
    public function get_course_module();

    /**
     * Get the group object associated with the event
     *
     * @return \core_calendar\local\event\value_objects\proxy_interface
     */
    public function get_group();

    /**
     *  Get the user object associated with the event
     *
     * @return \core_calendar\local\event\proxies\proxy_interface
     */
    public function get_user();

    /**
     * @return string
     */
    public function get_type();

    /**
     * Get the times associated with the event
     *
     * @return \core_calendar\local\event\value_objects\times_interface
     */
    public function get_times();

    /**
     * Get repeats of this event
     *
     * @return \core_calendar\local\event\entities\event_collection_interface
     */
    public function get_repeats();

    /**
     * @return integer
     */
    public function get_num_repeats();

    /**
     * @return bool
     */
    public function get_visibility();
}
