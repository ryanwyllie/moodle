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
 *
 */
define(
    [
        'jquery',
        'core/ajax',
        'core/templates',
        'core/notification',
        'core/custom_interaction_events'
    ],
    function(
        $,
        Ajax,
        Templates,
        Notification,
        CustomEvents
    ) {

    var init = function(root) {
        var contextids = JSON.parse(root.attr('data-context-ids'));
        var resultsContainer = root.find('[data-region="search-results"]');

        root.find('[data-action="search"]').click(function(e) {
            resultsContainer.empty();

            var tags = root.find('[data-region="search-input"]').val();
            var request = {
                methodname: 'core_question_search_by_tags',
                args: {
                    tags: tags.split(' '),
                    contextids: contextids
                }
            };

            Ajax.call([request])[0].then(function(results) {
                var questions = JSON.parse(results.data);

                questions.forEach(function(question) {
                    resultsContainer.append(question.questiontext);
                });
            });
        });
    };

    return {
        init: init
    };
});
