<?php

namespace core_calendar\local\event\proxies;

use core_calendar\local\event\proxies\proxy_proxy;

final class std_proxy implements proxy_interface {
    public $id;
    private $class;
    private $callback;

    public function __construct($id, Callable $callback) {
        $this->id = $id;
        $this->callback = $callback;
    }

    public function __get($member) {
        return $this->get($this->id)->{$member};
    }

    public function __set($member, $value) {
        $this->get($this->id)->{$member} = $value;
    }

    public function __isset($key) {
        return !empty($this->get($this->id)->{$member});
    }

    // TODO: Maybe this should be in an abstract class instead of the interface
    // Not much point in it being public.
    public function get() {
        return $this->class ? $this->class : $this->callback($this->id);
    }
}