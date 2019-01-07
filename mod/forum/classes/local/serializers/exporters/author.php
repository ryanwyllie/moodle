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
 * Forum class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\serializers\exporters;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\author as author_entity;
use core\external\exporter;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class author extends exporter {
    private $author;

    public function __construct(author_entity $author, $related = []) {
        $this->author = $author;
        return parent::__construct([], $related);
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => ['type' => PARAM_INT],
            'fullname' => ['type' => PARAM_TEXT],
            'profileurl' => ['type' => PARAM_URL],
            'profileimageurl' => ['type' => PARAM_URL]
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        return [
            'id' => $this->author->get_id(),
            'fullname' => $this->author->get_full_name(),
            'profileurl' => $this->author->get_profile_url()->out(false),
            'profileimageurl' => $this->author->get_profile_image_url()->out(false)
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'context' => 'context'
        ];
    }
}
