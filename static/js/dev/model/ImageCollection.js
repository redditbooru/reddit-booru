/**
 * Image collection
 */
(function(undefined) {

    var API_PATH = '/images/',
        EVT_UPDATED = 'updated';

    RB.ImageCollection = Backbone.Collection.extend({

        model: RB.Image,

        // Params
        lastDate: 0,
        queryOptions: {},
        queryUrl: API_PATH,

        url: function() {
            var separator = this.queryUrl.indexOf('?') !== -1 ? '&' : '?',
                params = [ 'saveSources=true' ];

            if (this.lastDate > 0) {
                params = [ 'afterDate=' + this.lastDate ];
            }

            return this.queryUrl + separator + params.join('&');
        },

        initialize: function(router) {
            var self = this;

            _.extend(this, Backbone.Events);
            this.on('add', function(item) {
                self._checkLastDate.call(self, item);
            });

            this.on('reset', function(stuff) {
                self.lastDate = 0;
                _.each(self.models, function(item) {
                    self._checkLastDate.call(self, item);
                });
            });

            if (router) {
                router.addRoute('home', '/');
                this.router = router;
            }

            if (typeof window.filters === 'object') {
                this.queryOptions = window.filters;
                this.queryUrl = this._buildQueryUrl();
            }

        },

        _checkLastDate: function(item) {
            var date = parseInt(item.attributes.dateCreated);
            if (date < this.lastDate || this.lastDate === 0) {
                this.lastDate = item.attributes.dateCreated;
            }
        },

        setQueryOption: function(name, value, noRequest) {
            var oldQueryUrl = this.queryUrl;

            if (typeof name === 'object') {
                this.queryOptions = name;
                noRequest = !!value;
            } else {
                this.queryOptions[name] = value;
            }

            this.queryUrl = this._buildQueryUrl();

            // If the query has changed, reset the paging and invalidate the current results
            if (oldQueryUrl !== this.queryUrl && !noRequest) {
                this.lastDate = 0;
                this.reset();
                this.loadNext();
            }

        },

        clearQueryOptions: function(noRequest) {
            var oldQueryUrl = this.queryUrl;
            this.queryOptions = {};
            this.queryUrl = this._buildQueryUrl();

            // If the query has changed, reset the paging and invalidate the current results
            if (oldQueryUrl !== this.queryUrl && !noRequest) {
                this.lastDate = 0;
                this.reset();
                this.loadNext();
                if (this.router) {
                    this.router.go('home');
                }
            }
        },

        _buildQueryUrl: function() {
            var retVal = [];
            for (var i in this.queryOptions) {
                if (_.has(this.queryOptions, i)) {
                    retVal.push(i + '=' + this.queryOptions[i]);
                }
            }
            return API_PATH + '?' + retVal.join('&');
        },

        loadNext: function(filter) {
            this.fetch({
                dataFilter: filter,
                success: _.bind(function() {
                    this.trigger(EVT_UPDATED);
                }, this)
            });
        }

    });

}());