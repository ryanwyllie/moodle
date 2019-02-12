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
 * Class for exporting rating information from an stdClass.
 *
 * @copyright  2019 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_rating\external;
defined('MOODLE_INTERNAL') || die();

use renderer_base;
use stdClass;

/**
 * Class for exporting rating settings from an stdClass.
 *
 * @copyright  2019 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_settings_exporter extends \core\external\exporter {
    private $ratingsettings;

    public function __construct(stdClass $ratingsettings, $related = []) {
        $this->ratingsettings = $ratingsettings;
        return parent::__construct([], $related);
    }

    public static function define_other_properties() {
        return [
            'aggregationmethod' => ['type' => PARAM_INT],
            'capabilities' => [
                'type' => [
                    'view' => ['type' => PARAM_BOOL],
                    'viewany' => ['type' => PARAM_BOOL],
                    'viewall' => ['type' => PARAM_BOOL],
                    'rate' => ['type' => PARAM_BOOL]
                ]
            ],
            'scale' => [
                'type' => [
                    'id' => ['type' => PARAM_INT],
                    'isnumeric' => ['type' => PARAM_BOOL],
                    'max' => ['type' => PARAM_INT],
                    'name' => [
                        'type' => PARAM_TEXT,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'scaleitems' => [
                        'multiple' => true,
                        'type' => [
                            'name' => ['type' => PARAM_NOTAGS],
                            'value' => ['type' => PARAM_NOTAGS]
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function get_other_values(renderer_base $output) {
        $ratingsettings = $this->ratingsettings;
        $permissions = $ratingsettings->permissions;
        $pluginpermissions = $ratingsettings->pluginpermissions;
        $scale = $ratingsettings->scale;
        $exportedscaleitems = [];

        foreach ($scale->scaleitems as $key => $value) {
            $exportedscaleitems[] = [
                'name' => $key,
                'value' => $value
            ];
        }

        return [
            'aggregationmethod' => $ratingsettings->aggregationmethod,
            'capabilities' => [
                'view' => $permissions->view && $pluginpermissions->view,
                'viewany' => $permissions->viewany && $pluginpermissions->viewany,
                'viewall' => $permissions->viewall && $pluginpermissions->viewall,
                'rate' => $permissions->rate && $pluginpermissions->rate
            ],
            'scale' => [
                'id' => $scale->id,
                'isnumeric' => $scale->isnumeric,
                'max' => $scale->max,
                'name' => $scale->name,
                'scaleitems' => $exportedscaleitems
            ]
        ];
    }
}
