import Backbone from 'backbone';
import $ from 'jquery';

import App from '../App';

export default Backbone.View.extend({

    $sidebar: $('#supporting'),

    initialize: function() {

    },

    populate: function(html, owner) {
        this.$sidebar.html(html).addClass('hasContent');

        // TODO - this should probably be event controlled
        App.views.images.calculateWindowColumns();
        App.views.images.render();
    },

    dismiss: function() {
        this.$sidebar.html('').removeClass('hasContent');
        // TODO - this should probably be event controlled
        App.views.images.calculateWindowColumns();
        App.views.images.render();
    }

});