(function(undefined) {
  
    var KEY_ENTER = 13;

    RB.SearchView = Backbone.View.extend({

        initialize: function(imageCollection, router) {
            this.imageCollection = imageCollection;
            $('input[name="search"]').on('keypress', _.bind(this._handleSearch, this));
            router.on('route:querySearch', function(params) {
                console.log(params);
            });
        },

        // Router entry points
        _handleSearch: function(evt) {
            var images = this.imageCollection,
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

    });

}());