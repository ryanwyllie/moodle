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
 * Class for exporting a question from an stdClass.
 *
 * @package    core_question
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_question\external;
defined('MOODLE_INTERNAL') || die();

use \renderer_base;

/**
 * Class for exporting a question from an stdClass.
 *
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_exporter extends \core\external\exporter {

    /**
     * @var \stdClass $question
     */
    protected $question;

    /**
     * Constructor.
     *
     * @param \stdClass $question
     * @param array $related The related data.
     */
    public function __construct(\stdClass $question, $related = []) {
        $this->question = $question;
        return parent::__construct($question, $related);
    }

    protected static function define_related() {
        return ['context' => '\\context'];
    }

    public static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
            ],
            'category' => [
                'type' => PARAM_INT,
            ],
            'parent' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'questiontext' => [
                'type' => PARAM_TEXT,
            ],
            'questiontextformat' => [
                'type' => PARAM_INT,
            ],
            'generalfeedback' => [
                'type' => PARAM_TEXT,
            ],
            'generalfeedbackformat' => [
                'type' => PARAM_INT,
            ],
            'defaultmark' => [
                'type' => PARAM_FLOAT,
            ],
            'penalty' => [
                'type' => PARAM_FLOAT,
            ],
            'qtype' => [
                'type' => PARAM_ALPHANUM,
            ],
            'length' => [
                'type' => PARAM_INT,
            ],
            'stamp' => [
                'type' => PARAM_TEXT,
            ],
            'version' => [
                'type' => PARAM_TEXT,
            ],
            'hidden' => [
                'type' => PARAM_INT,
            ],
            'timecreated' => [
                'type' => PARAM_INT,
            ],
            'timemodified' => [
                'type' => PARAM_INT,
            ],
            'createdby' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED
            ],
            'modifiedby' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED
            ]
        ];
    }

    protected static function define_other_properties() {
        return [
            'icon' => [
                'type' => question_icon_exporter::read_properties_definition(),
            ]
        ];
    }

    protected function get_other_values(\renderer_base $output) {
        $iconexporter = new question_icon_exporter($this->question, $this->related);

        return [
            'icon' => $iconexporter->export($output),
        ];
    }
}
