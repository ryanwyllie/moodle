<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * std_proxy class.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\proxies;

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\proxies\proxy_proxy;

/**
 * stdClass proxy.
 *
 * This class is intended to proxy things like user, group, etc 'classes'
 * It will only run the callback to load the object from the DB when necessary.
 *
 * Uses magic getters/setters to allow access to the object properties as if the
 * plain stdClass was being used.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class std_proxy implements proxy_interface {
    /**
     * @var int $id The ID of the database record.
     */
    public $id;

    /**
     * @var \stdClass $class The class we are proxying.
     */
    private $class;

    /**
     * @var callable $callback Callback to run which will load the class to proxy.
     */
    private $callback;

    /**
     * Constructor.
     *
     * @param int      $id       The ID of the record in the database.
     * @param callable $callback Callback to load the class.
     */
    public function __construct($id, callable $callback) {
        $this->id = $id;
        $this->callback = $callback;
    }

    /**
     * Magic getter.
     *
     * @param  string $member
     * @return mixed
     */
    public function __get($member) {
        return $this->get()->{$member};
    }

    /**
     * Magic setter.
     *
     * @param string $member
     * @param mixed  $value
     */
    public function __set($member, $value) {
        $this->get()->{$member} = $value;
    }

    /**
     * Magic isset.
     *
     * @param string $key
     */
    public function __isset($key) {
        return !empty($this->get()->{$member});
    }

    public function get() {
        if ($this->class) {
            return $this->class;
        } else {
            $callback = $this->callback;
            return $callback($this->id);
        }
    }
}
