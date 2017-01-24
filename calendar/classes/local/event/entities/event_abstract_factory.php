<?php

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event;
use core_calendar\local\event\entities\repeat_event_collection;
use core_calendar\local\event\value_objects\event_description;
use core_calendar\local\event\value_objects\event_times;
use core_calendar\local\event\value_objects\event_course_module;
use core_calendar\local\event\proxies\std_proxy;

use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\value_objects\course_module_interface;

abstract class event_abstract_factory implements event_factory_interface {
    protected function visit_moodle (event_interface $event) {}

    public function create_instance(
        $id,
        $name,
        $descriptionvalue,
        $descriptionformat,
        $courseid,
        $groupid,
        $userid,
        $repeatid,
        $modulename,
        $moduleinstance,
        $type,
        $timestart,
        $timeduration,
        $timemodified,
        $timesort,
        $visible,
        $subscriptionid
    ) {
        return $this->visit_moodle(new event(
            $id,
            $name,
            new event_description($descriptionvalue, $descriptionformat),
            new std_proxy($courseid, function($id) {
                global $DB;
                return $DB->get_record('course', ['id' => $id]);
            }),
            new std_proxy($groupid, function($id) {
                return groups_get_group($id, 'id,name,courseid');
            }),
            new std_proxy($userid, function($id) {
                global $DB;
                return $DB->get_record('user', ['id' => $id]);
            }),
            new repeat_event_collection($id, $this),
            $moduleinstance && $modulename ? new event_course_module($moduleinstance, $modulename)
                                           : NULL,
            $type,
            new event_times(
                (new \DateTimeImmutable())->setTimestamp($timestart),
                (new \DateTimeImmutable())->setTimestamp($timestart + $timeduration),
                (new \DateTimeImmutable())->setTimestamp($timesort ? $timesort : $timestart),
                (new \DateTimeImmutable())->setTimestamp($timemodified)
            ),
            !empty($visible),
            $subscriptionid
        ));
    }
}
