import Backbone from 'backbone';
import $ from 'jquery';

import App from '../App';

export default Backbone.View.extend({

    el: '#supporting',

    populate: function(html, owner) {
        this.$el.html(html).addClass('hasContent');

        // TODO - this should probably be event controlled
        App.views.images.calculateWindowColumns();
        App.views.images.render();
    },

    dismiss: function() {
        this.$el.html('').removeClass('hasContent');
        // TODO - this should probably be event controlled
        App.views.images.calculateWindowColumns();
        App.views.images.render();
    }

});