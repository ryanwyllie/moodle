<?php

namespace core_calendar\local\event\value_objects;

use core_calendar\local\event\value_objects\times_interface;

final class event_times implements times_interface {
    private $start;
    private $end;
    private $sort;

    public function __construct(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        \DateTimeImmutable $sort
    ) {
        $this->start = $start;
        $this->end = $end;
        $this->sort = $sort;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function get_starttime() {
        return $this->start;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function get_endttime() {
        return $this->end;
    }

    /**
     * @return \DateInterval
     */
    public function get_duration() {
        return $this->end->diff($this->start);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function get_sorttime() {
        return $this->sort;
    }
}