
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    var query = function(args) {
        // Normalise the arguments to use limit/offset rather than limitnum/limitfrom.
        if (typeof args.limit === 'undefined') {
            args.limit = 0;
        }

        if (typeof args.offset === 'undefined') {
            args.offset = 0;
        }

        var request = {
            methodname: 'core_todo_query_todos',
            args: args
        };

        var promise = Ajax.call([request])[0];

        promise.fail(Notification.exception);

        return promise;
    };

    return {
        query: query,
    };
});
