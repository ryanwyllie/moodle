define(['jquery'], function($) {
    return {
        query: function() {
            var deferred = $.Deferred();

            setTimeout(function() {
                deferred.resolve([
                    {
                        done: true,
                        task: 'task 1 from the server',
                        author: 'Person 1',
                        time: 2
                    },
                    {
                        done: false,
                        task: 'task 2 from the server',
                        author: 'Person 2',
                        time: 4
                    }
                ]);
            }, 2000);

            return deferred.promise();
        },
        add: function() {
            var deferred = $.Deferred();

            setTimeout(function() {
                deferred.resolve(true);
            }, 2000);

            return deferred.promise();
        }
    };
});