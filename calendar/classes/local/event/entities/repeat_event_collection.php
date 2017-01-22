<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_collection_interface;
use core_calendar\local\event\entities\event_factory_interface;

final class repeat_event_collection implements event_collection_interface {
    const DB_QUERY_LIMIT = 100;

    private $parentid;
    private $factory;
    private $events;
    private $num;

    public function __construct($parentid, event_factory_interface $factory) {
        $this->parentid = $parentid;
        $this->factory = $factory;
    }

    public function get_id() {
        return $this->parentid;
    }

    public function get_num() {
        global $DB;
        // Subtract one because the original event has repeatid = its own id
        return $this->num = isset($this->num) ? max($this->num, 0) : ($DB->count_records('event', ['repeatid' => $this->parentid]) - 1);
    }

    public function getIterator() {
        foreach ($this->load_event_records() as $eventrecords) {
            foreach ($eventrecords as $eventrecord) {
                yield $this->factory->create_instance(
                    $eventrecord->id,
                    $eventrecord->name,
                    $eventrecord->description, //descriptionvalue
                    $eventrecord->format, //descriptionformat
                    $eventrecord->courseid,
                    $eventrecord->groupid,
                    $eventrecord->userid,
                    $eventrecord->repeatid,
                    $eventrecord->modulename,
                    $eventrecord->instance, //moduleinstance
                    $eventrecord->eventtype, //type
                    $eventrecord->timestart,
                    $eventrecord->timeduration,
                    $eventrecord->timemodified,
                    $eventrecord->timesort,
                    $eventrecord->visible,
                    $eventrecord->subscriptionid
                );
            }
        }
    }

    // Start from 1 to not include the original event
    private function load_event_records($start = 1) {
        global $DB;
        while($records = $DB->get_records(
            'event',
            ['repeatid' => $this->parentid],
            '',
            '*',
            $start,
            self::DB_QUERY_LIMIT
        )) {
            yield $records;
            $start += self::DB_QUERY_LIMIT;
        }
    }
}