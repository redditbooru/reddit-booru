import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

var HOVER = 'hover';

export default Backbone.View.extend({

    el: '#dragdrop',

    initialize: function(uploadView, searchView) {

        var body = document.documentElement;

        // Temporary hack to get around what seems to be backbone returning the wrong element
        this.el = document.getElementById('dragdrop');
        this.$el = $(this.el);

        body.addEventListener('dragover', _.bind(this.handleDragOver, this));
        body.addEventListener('dragleave', _.bind(this.handleDragLeave, this));
        body.addEventListener('drop', _.bind(this.handleDrop, this));

        this.$el.find('.search').on('drop', _.bind(this.handleDrop, this));

        this.uploadView = uploadView;
        this.searchView = searchView;

    },

    handleDragOver: function(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        this.$el.show();
        this.$el.find('.' + HOVER).removeClass(HOVER);
        this.$el.find(evt.target).addClass(HOVER);
    },

    handleDragLeave: function(evt) {

        evt.stopPropagation();
        evt.preventDefault();

        var position = this.$el.position(),
            width = this.$el.width(),
            height = this.$el.height();

        if (evt.clientX < position.left || evt.clientY < position.top
            || evt.clientX > position.left + width || evt.clientY > position.top + height) {
            this.$el.hide().find('.' + HOVER).removeClass(HOVER);
        }

    },

    handleDrop: function(evt) {
        evt.preventDefault();
        this.$el.hide();
        if (evt.dataTransfer && evt.dataTransfer.files.length > 0) {
            var file = evt.dataTransfer.files[0];
            if ($(evt.target).hasClass('upload')) {
                this.uploadView.openWithUpload(file);
            } else {
                this.searchView.uploadSearch(file);
            }
        }
    }

});