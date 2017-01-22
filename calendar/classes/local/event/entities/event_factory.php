<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_abstract_factory;
use core_calendar\local\event\entities\event_interface;

class event_factory extends event_abstract_factory {
    private $moodlefacade;
    private $moodlevisitor;

    public function __construct($moodlefacade, $moodlevisitor) {
        $this->moodlefacade = $moodlefacade;
        $this->moodlevisitor = $moodlevisitor;
    }

    protected function visit_moodle(event_interface $event) {
        return !$event->get_course_module() ? $event :
            ($this->moodlefacade->get_module(
                $event->get_course_module())->accept($this->moodlevisitor)->get_callback())($event);
    }
}