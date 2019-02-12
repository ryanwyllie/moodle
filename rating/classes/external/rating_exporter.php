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
 * Class for exporting a rating from an stdClass.
 *
 * @copyright  2019 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_rating\external;
defined('MOODLE_INTERNAL') || die();

use rating;
use renderer_base;

/**
 * Class for exporting a rating from an stdClass.
 *
 * @copyright  2019 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_exporter extends \core\external\exporter {
    private $rating;

    public function __construct(rating $rating, $related = []) {
        $this->rating = $rating;
        return parent::__construct([], $related);
    }

    protected static function define_related() {
        return [
            'user' => '\stdClass',
            'ratingmanager' => '\rating_manager'
        ];
    }

    public static function define_other_properties() {
        return [
            'itemid' => ['type' => PARAM_INT],
            'scaleid' => ['type' => PARAM_INT],
            'userid' => ['type' => PARAM_INT],
            'rating' => ['type' => PARAM_INT],
            'contextid' => ['type' => PARAM_INT],
            'component' => ['type' => PARAM_COMPONENT],
            'ratingarea' => ['type' => PARAM_NOTAGS],
            'itemuserid' => ['type' => PARAM_INT],
            'aggregate' => [
                'type' => PARAM_FLOAT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'aggregatestr' => [
                'type' => PARAM_NOTAGS,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'aggregatelabel' => [
                'type' => PARAM_NOTAGS,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'count' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'capabilities' => [
                'type' => [
                    'rate' => ['type' => PARAM_BOOL],
                    'viewaggregate' => ['type' => PARAM_BOOL],
                    'viewall' => ['type' => PARAM_BOOL]
                ]
            ],
            'urls' => [
                'type' => [
                    'viewall' => ['type' => PARAM_URL],
                    'viewallpopup' => ['type' => PARAM_URL]
                ]
            ]
        ];
    }

    protected function get_other_values(renderer_base $output) {
        $rating = $this->rating;
        $user = $this->related['user'];
        $ratingmanager = $this->related['ratingmanager'];
        $canviewaggregate = $rating->user_can_view_aggregate($user->id);

        return [
            'itemid' => $rating->itemid,
            'scaleid' => $rating->scaleid,
            'userid' => $rating->userid,
            'rating' => $rating->rating,
            'contextid' => $rating->context->id,
            'component' => $rating->component,
            'ratingarea' => $rating->ratingarea,
            'itemuserid' => $rating->itemuserid,
            'aggregate' => $canviewaggregate ? $rating->aggregate : null,
            'aggregatestr' => $canviewaggregate ? $rating->get_aggregate_string() : null,
            'aggregatelabel' => $canviewaggregate ? $ratingmanager->get_aggregate_label($rating->settings->aggregationmethod) : null,
            'count' => $canviewaggregate ? $rating->count : null,
            'capabilities' => [
                'rate' => $rating->user_can_rate($user->id),
                'viewaggregate' => $canviewaggregate,
                'viewall' => $rating->settings->permissions->viewall && $rating->settings->pluginpermissions->viewall,
            ],
            'urls' => [
                'rate' => $rating->get_rate_url()->out_omit_querystring(),
                'viewall' => $rating->get_view_ratings_url(false)->out(false),
                'viewallpopup' => $rating->get_view_ratings_url(true)->out(false)
            ]
        ];
    }
}
