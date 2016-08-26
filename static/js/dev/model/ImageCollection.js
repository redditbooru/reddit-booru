import Backbone from 'backbone';
import _ from 'underscore';

import ImageModel from './Image';

var API_PATH = '/images/',
    EVT_UPDATED = 'updated';

export default Backbone.Collection.extend({

    model: ImageModel,

    // Params
    lastDate: 0,
    queryOptions: {},
    queryUrl: API_PATH,

    url: function() {
        var separator = this.queryUrl.indexOf('?') !== -1 ? '&' : '?',
            params = [];

        if (this.lastDate > 0) {
            params = [ 'afterDate=' + this.lastDate ];
        }

        return this.queryUrl + separator + params.join('&');
    },

    initialize: function(router) {

        _.extend(this, Backbone.Events);
        this.on('add', (item) => {
            this._checkLastDate(item);
        });

        this.on('reset', () => {
            this.lastDate = 0;
            _.each(this.models, (item) => {
                this._checkLastDate(item);
            });
        });

        if (router) {
            router.addRoute('home', '/');
            this.router = router;
        }

        if (typeof window.filters === 'object') {
            this.queryOptions = window.filters || {};
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
        Object.keys(this.queryOptions).forEach((option) => {
            retVal.push(encodeURIComponent(option) + '=' + encodeURIComponent(this.queryOptions[option]));
        });
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