<?php

namespace core_calendar\local\event\entities;

interface event_collection_interface extends \IteratorAggregate {
    public function get_id();
    public function get_num();
}
