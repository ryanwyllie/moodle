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
 * std_proxy tests.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\proxies\std_proxy;

/**
 * std_proxy testcase.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_std_proxy_testcase extends advanced_testcase {
    /**
     * @var \stdClass[] $objects Array of objects to proxy.
     */
    public static $objects;

    public static function setUpBeforeClass() {
        self::$objects = [
            1 => (object) [
                'member1' => 'Hello',
                'member2' => 1729,
                'member3' => 'Something else'
            ],
            5 => (object) [
                'member1' => 'Hej',
                'member2' => 87539319,
                'member3' => 'nagot annat'
            ]
        ];
    }

    /**
     * Test proxying.
     *
     * @dataProvider test_proxy_testcases()
     * @param int    $id       Object ID.
     * @param string $member   Object member to retrieve.
     * @param mixed  $expected Expected value of member.
     */
    public function test_proxy($id, $member, $expected) {
        $proxy = new std_proxy($id, function($id) {
            return self::$objects[$id];
        });

        $this->assertEquals($proxy->get($member), $expected);

        // Test changing the value.
        $proxy->set($member, 'something else');
        $this->assertEquals($proxy->get($member), 'something else');
    }

    /**
     * Test getting a non existant member.
     *
     * @dataProvider test_get_set_testcases()
     * @expectedException \core_calendar\local\event\exceptions\member_does_not_exist_exception
     */
    public function test_get_invalid_member($id) {
        $proxy = new std_proxy($id, function($id) {
            return self::$objects[$id];
        });

        $proxy->get('thisdoesnotexist');
    }

    /**
     * Test setting a non existant member.
     *
     * @dataProvider test_get_set_testcases()
     * @expectedException \core_calendar\local\event\exceptions\member_does_not_exist_exception
     */
    public function test_set_invalid_member($id) {
        $proxy = new std_proxy($id, function($id) {
            return self::$objects[$id];
        });

        $proxy->set('thisdoesnotexist', 'should break');
    }

    /**
     * Test get proxied instance.
     *
     * @dataProvider test_get_set_testcases()
     * @param int $id Object ID.
     */
    public function test_get_proxied_instance($id) {
        $proxy = new std_proxy($id, function($id) {
            return self::$objects[$id];
        });

        $this->assertEquals($proxy->get_proxied_instance(), self::$objects[$id]);
    }

    /**
     * Test cases for proxying test.
     */
    public function test_proxy_testcases() {
        return [
            'Object 1 member 1' => [
                1,
                'member1',
                'Hello'
            ],
            'Object 1 member 2' => [
                1,
                'member2',
                1729
            ],
            'Object 1 member 3' => [
                1,
                'member3',
                'Something else'
            ],
            'Object 2 member 1' => [
                5,
                'member1',
                'Hej'
            ],
            'Object 2 member 2' => [
                5,
                'member2',
                87539319
            ],
            'Object 3 member 3' => [
                5,
                'member3',
                'nagot annat'
            ]
        ];
    }

    /**
     * Test cases for getting and setting tests.
     */
    public function test_get_set_testcases() {
        return [
            'Object 1' => [1],
            'Object 2' => [5]
        ];
    }
}
