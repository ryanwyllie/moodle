define([], function() {
    return {
        init: function(root, viewState) {
            root.on('input', 'input', function(e) {
                viewState.title = e.target.value;
            });
        }
    };
});