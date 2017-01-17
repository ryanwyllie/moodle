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
 * Contains event class for displaying a calendar event icon.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;

/**
 * Class for displaying a calendar event icon.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon implements templatable, renderable {

    /**
     * @var string The icon key.
     */
    protected $key;

    /**
     * @var string The icon component.
     */
    protected $component;

    /**
     * @var string The icon alt text.
     */
    protected $alttext;

    /**
     * Constructor.
     *
     * @param string $key
     * @param string $component
     * @param string $alttext
     */
    public function __construct($key, $component, $alttext) {
        $this->key = $key;
        $this->component = $component;
        $this->alttext = $alttext;
    }

    /**
     * Get the icon key.
     *
     * @return string
     */
    public function get_key() {
        return $this->key;
    }

    /**
     * Get the icon component.
     *
     * @return string
     */
    public function get_component() {
        return $this->component;
    }

    /**
     * Get the icon alt text.
     *
     * @return string
     */
    public function get_alt_text() {
        return $this->alttext;
    }

    /**
     * Get the output for a template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        return [
            'key' => $this->get_key(),
            'component' => $this->get_component(),
            'alttext' => $this->get_alt_text(),
        ];
    }
}
