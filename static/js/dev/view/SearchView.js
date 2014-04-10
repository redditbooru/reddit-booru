(function(undefined) {

    var KEY_ENTER = 13,
        UPDATE_DELAY = 1000;

    RB.SearchView = Backbone.View.extend({

        $clearSearch: $('#clearSearch'),
        $searchInput: $('input[name="search"]'),

        initialize: function(sidebar, imageCollection, router) {
            this.imageCollection = imageCollection;
            this.sidebar = sidebar;
            this.$searchInput.on('keypress', _.bind(this._handleSearch, this));
            this.$clearSearch.on('click', _.bind(this.clearSearch, this));
            router.on('route:querySearch', function(params) {
                console.log(params);
            });
        },

        clearSearch: function() {
            this.sidebar.dismiss();
            this.imageCollection.clearQueryOptions();
            RB.App.setTitle('');
            this.$searchInput.val('');
            this.$clearSearch.hide();
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
                        self._querySearch(value);
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

        _querySearch: function(query) {
            this.imageCollection.setQueryOption('q', query);
            RB.App.setTitle('Search results for "' + query + '"');
            this.$clearSearch.show();
        }

    });

}());