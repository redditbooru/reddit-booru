import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

import App from '../App';
import gallery from '../../../../views/gallery.handlebars';

var GALLERY = 'gallery';

export default Backbone.View.extend({

    initialize: function(router, sidebar) {
        this.sidebar = sidebar;
        router.addRoute('galleryNew', '/gallery/:id/:title', _.bind(this.handleRoute, this));
        router.addRoute('galleryOld', '/gallery/:id', _.bind(this.handleRoute, this));
    },

    handleRoute: function(data) {
        this.sidebar.dismiss();

        if (data instanceof Array) {

        } else {
            if ('id' in data) {

                var id = data.id;

                // If there's a title, base convert the ID from 36 back to 10
                if ('title' in data) {
                    id = parseInt(id, 36);
                }

                $.ajax({
                    url: '/images/?postId=' + id,
                    dataType: 'json',
                    success: _.bind(this.displayGallery, this)
                });
            }
        }

    },

    displayGallery: function(data) {
        if (data instanceof Array && data.length > 0) {
            App.setTitle(data[0].title);
            $('#images')
                .addClass(GALLERY)
                .html(gallery({ images: data }));
        }
    }

});