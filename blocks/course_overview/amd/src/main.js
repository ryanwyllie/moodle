define(['jquery', 'core/templates', 'core/todo_repository'], function($, Templates, TodoRepository) {
    var init = function() {
        TodoRepository.query({}).done(function(data) {
            var context = {
                todos: data
            };

            Templates.render('block_course_overview/todo_list', context).done(function(html, js) {
                Templates.replaceNodeContents($('#add-todos-here'), html, js);
            });
        });
    };

    return {
        init: init,
    };
});
