<?php

namespace core_calendar\local\event\value_objects;

interface times_interface {
    /**
     * @return \DateTimeImmutable
     */
    public function get_starttime();

    /**
     * @return \DateTimeImmutable
     */
    public function get_endttime();

    /**
     * @return \DateInterval
     */
    public function get_duration();

    /**
     * @return \DateTimeImmutable
     */
    public function get_sorttime();
}
