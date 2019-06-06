define(['core/todo_list_repository'], function(Repository) {

    var sortItems = function(items, asc) {
        if (asc) {
            items.sort(function(a, b) {
                if (a.time < b.time) {
                    return -1;
                } else if (a.time > b.time) {
                    return 1;
                } else {
                    return 0;
                }
            });
        } else {
            items.sort(function(a, b) {
                if (a.time < b.time) {
                    return 1;
                } else if (a.time > b.time) {
                    return -1;
                } else {
                    return 0;
                }
            });
        }
    };

    var init = function(root, viewState) {
        root.on('click', '[data-action="refresh"]', function() {
            viewState.isrefreshing = true;

            Repository.query().then(function(serverItems) {
                var newItems = viewState.items.concat(serverItems);
                sortItems(newItems, viewState.sortasc)
                viewState.items = newItems;
                viewState.isrefreshing = false;
            });
        });

        root.on('click', '[data-action="sort-asc"]', function() {
            viewState.sortasc = true;
            viewState.sortdesc = false;
            var items = viewState.items.slice();
            sortItems(items, true)
            items.forEach(function(item) {
                console.log(item.author, item.done);
            });
            viewState.items = items;
        });

        root.on('click', '[data-action="sort-desc"]', function() {
            viewState.sortasc = false;
            viewState.sortdesc = true;
            var items = viewState.items.slice();
            sortItems(items, false)
            items.forEach(function(item) {
                console.log(item.author, item.done);
            });
            viewState.items = items;
        });

        root.on('submit', 'form', function(e) {
            e.preventDefault();
            var form = e.target;
            var newTodo = {
                done: form.elements.done.checked,
                task: form.elements.task.value,
                author: form.elements.author.value,
                time: form.elements.time.value
            };

            viewState.isadding = true;
            Repository.add().then(function() {
                var newItems = viewState.items.concat([newTodo]);
                sortItems(newItems, viewState.sortasc);
                viewState.items = newItems;
                viewState.isadding = false;

                form.elements.done.checked = false;
                form.elements.task.value = '';
                form.elements.author.value = '';
                form.elements.time.value = '';
            });
        });
    };

    return {
        init: init
    };
});