(function(undefined) {

    var KEY_ENTER = 13,
        UPDATE_DELAY = 1000,

        NSFW_TOGGLE = 'hideNsfw',
        NSFW_EXPIRES = 365, // cookie is good for a year
        NSFW_DISABLE = 'false',
        NSFW_ENABLE = 'true';

    RB.SearchView = Backbone.View.extend({

        $clearSearch: $('#clearSearch'),
        $searchInput: $('input[name="search"]'),
        $body: $('body'),
        currentParams: {},

        initialize: function(sidebar, imageCollection, sources, router) {
            var self = this;

            this.imageCollection = imageCollection;
            this.sidebar = sidebar;
            this.router = router;
            this.$searchInput.on('keypress', _.bind(this._handleSearch, this));
            this.$clearSearch.on('click', _.bind(this.clearSearch, this));

            // TODO - think about whether this really belongs here. Might need a NavView at some point
            $('#showNsfw').on('change', _.bind(this._handleNsfwChange, this));

            if (typeof window.filters === 'object') {
                this.currentParams = window.filters;

                // Use a timeout because the app may still be loading
                setTimeout(function() {
                    self._buildBreadCrumb();
                }, 10);
            }

            // Global catch for source select links
            $('body').on('click', '.singleSourceSearch', _.bind(this.singleSourceSearch, this));
            router.addRoute('search', '/search/', _.bind(this.routeSearch, this));

            this.sources = sources;
            sources.on('update', _.bind(this._handleSourcesUpdate, this));

        },

        routeSearch: function(params) {
            params = _.defaults(params, this.currentParams);
            this.imageCollection.setQueryOption(params);
            this.currentParams = params;
            if (!params.user) {
                this.sidebar.dismiss();
            }
            this._buildBreadCrumb();
            return this.currentParams;
        },

        clearSearch: function() {
            this.sidebar.dismiss();
            this.imageCollection.clearQueryOptions();
            RB.App.setTitle('');
            this.$searchInput.val('');
            this.$clearSearch.hide();
        },

        singleSourceSearch: function(evt) {
            this.imageCollection.setQueryOption('sources', evt.currentTarget.dataset['id']);
        },

        _handleNsfwChange: function(evt) {
            if (evt.currentTarget.checked) {
                this.$body.removeClass(NSFW_TOGGLE);
                RB.Cookie.bake('showNsfw', NSFW_ENABLE, NSFW_EXPIRES, '/');
            } else {
                this.$body.addClass(NSFW_TOGGLE);
                RB.Cookie.bake('showNsfw', NSFW_DISABLE, NSFW_EXPIRES, '/');
            }
        },

        _handleSourcesUpdate: function(item) {
            var collections = this.imageCollection,
                self = this;
            clearTimeout(this._delayTimer);
            this._delayTimer = setTimeout(function() {
                var sources = self.sources.collection.where({ checked: true }),
                    updated = [];

                _.each(sources, function(item) {
                    updated.push(item.attributes.value);
                });

                self.router.go('search', { sources: updated.join(',') });

            }, UPDATE_DELAY);
        },

        // Router entry points
        _handleSearch: function(evt) {
            var self = this,
                keyCode = evt.keyCode || evt.charCode,
                submitSearch = function() {
                    var value = evt.target.value;
                    if (value.indexOf('http') === 0) {
                        self._reverseImageSearch(value);
                    } else {
                        // self._querySearch(value);
                        self.router.go('search', { q: value });
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

        _reverseImageSearch: function(url) {
            var images = this.imageCollection,
                self = this;

            images.clearQueryOptions(true);
            images.setQueryOption('imageUri', url, false);

            // We're going to hijack the usual request chain so that reverse image search specific logic can be done
            images.reset();
            images.loadNext(function(data, type) {
                var data = JSON.parse(data),
                    results = data.results;
                self.sidebar.populate(RB.Templates.imageSearchDetails(data), self);
                return JSON.stringify(results);
            });

            RB.App.setTitle('Reverse image search');
            this.$clearSearch.show();
        },

        // not really a bread crumb, but naming things is hard
        _buildBreadCrumb: function() {
            if ('q' in this.currentParams || 'user' in this.currentParams) {
                RB.App.setTitle(RB.Templates.breadcrumb(this.currentParams));
                this.$clearSearch.show();
            }
        }

    });

}());