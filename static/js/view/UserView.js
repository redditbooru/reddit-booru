import Backbone from 'backbone';
import _ from 'underscore';

export default Backbone.View.extend({

    initialize: function(sidebar, imageCollection, router) {
        this.imageCollection = imageCollection;
        router.on('route:user', _.bind(this.onRoute, this));
    },

    onRoute: function(userName) {
        this.imageCollection.setQueryOption('user', userName, true);
    }

});