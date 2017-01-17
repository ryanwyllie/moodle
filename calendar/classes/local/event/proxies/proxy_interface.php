<?php

namespace core_calendar\local\event\proxies;

interface proxy_interface {
    /**
     * Get the full instance of the proxied class
     *
     * @return \stdClass
     */
    public function get();
}
