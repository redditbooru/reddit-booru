/**
 * RedditBooru routes
 */
(function(undefined) {
    RB.Router = Backbone.Router.extend({
        routes: {
            'search/?*params': 'querySearch',
            'user/:userName': '_user'
        },

        querySearch: function(params) {
            console.log('fuck it all');
        },

        _user: function(userName) {
            console.log('hey guys');
        }

    });
}());