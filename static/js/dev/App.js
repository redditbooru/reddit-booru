/**
 * RedditBooru main app view (controller)
 */
(function(undefined) {

    // Amount of time to wait on user to stop making changes before firing off requests
    var UPDATE_DELAY = 1000,
        KEY_ENTER = 13;

    RB.Router = Backbone.Router.extend({
        routes: {
            'search/?*params': '_default',
            'user/*': '_user'
        },

        _default: function(params) {
            console.log(params);
        },

        _user: function(params) {
            console.log(params);
        }

    });

    RB.AppView = Backbone.View.extend({

        views: {},
        collections: {},
        router: new RB.Router,

        $title: $('#title'),

        _delayTimer: null,

        initialize: function() {

            // Start the router
            Backbone.history.start({
                pushState: true
            });

            this.collections.sources = new RB.QueryOptionCollection();
            this.collections.sources.reset(window.sources);
            this.views.sources = new RB.QueryOptionsView($('#sources'), this.collections.sources);
            this.views.sources.on('update', _.bind(this._handleSourcesUpdate, this));

            this.collections.images = new RB.ImageCollection();
            this.collections.images.reset(window.startUp);
            this.views.images = new RB.ImageView($('#images'), this.collections.images);

            $('input[name="search"]').on('keypress', _.bind(this._handleSearch, this));

        },

        _handleSourcesUpdate: function(item) {
            var collections = this.collections;
            clearTimeout(this._delayTimer);
            this._delayTimer = setTimeout(function() { 
                var sources = collections.sources.where({ checked: true }),
                    updated = [];

                _.each(sources, function(item) {
                    updated.push(item.attributes.value);
                });

                collections.images.setQueryOption('sources', updated.join(','));
            }, UPDATE_DELAY);
        },

        // Router entry points
        _handleSearch: function(evt) {
            var images = this.collections.images,
                keyCode = evt.keyCode || evt.charCode,
                that = this,
                submitSearch = function() {
                    var value = evt.target.value;
                    if (value.indexOf('http') !== -1) {
                        images.setQueryOption('imageUri', value);
                        that.setTitle('Reverse image search');
                    } else {
                        images.setQueryOption('q', value);
                        that.setTitle('Search results for "' + evt.target.value + '"');
                    }
                };

            // Force submit on enter press, otherwise submit after delay
            clearTimeout(this._delayTimer);
            if (keyCode === KEY_ENTER) {
                submitSearch();
            } else {
                this._delayTimer = setTimeout(submitSearch, UPDATE_DELAY);
            }
        },

        setTitle: function(title) {
            this.$title.html(title);
            document.title = title + ' - redditbooru';
        }

    });

    // Kick off execution
    RB.App = new RB.AppView();

}());