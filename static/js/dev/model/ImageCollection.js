/**
 * Image collection
 */
(function(undefined) {

    var API_PATH = '/api/images/',
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
                params.push('afterDate=' + this.lastDate);
            }

            return this.queryUrl + separator + params.join('&');
        },

        initialize: function() {
            var that = this;
            _.extend(this, Backbone.Events);
            this.on('add', function(item) {
                that._checkLastDate.call(that, item);
            });

            this.on('reset', function(stuff) {
                that.lastDate = 0;
                _.each(that.models, function(item) {
                    that._checkLastDate.call(that, item);
                });
            });
        },

        _checkLastDate: function(item) {
            var date = parseInt(item.attributes.dateCreated);
            if (date < this.lastDate || this.lastDate === 0) {
                this.lastDate = item.attributes.dateCreated;
            }
        },

        setQueryOption: function(name, value, noRequest) {
            var oldQueryUrl = this.queryUrl;
            this.queryOptions[name] = value;
            this.queryUrl = this._buildQueryUrl();

            // If the query has changed, reset the paging and invalidate the current results
            if (oldQueryUrl !== this.queryUrl && !noRequest) {
                this.lastDate = 0;
                this.reset();
                this.loadNext();
                RB.App.router.navigate('search/' + this.queryUrl.replace(API_PATH, ''), { trigger: true });
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
                RB.App.router.navigate('search/' + this.queryUrl.replace(API_PATH, ''), { trigger: true });
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