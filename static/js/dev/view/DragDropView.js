import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

var HOVER = 'hover';

export default Backbone.View.extend({

    $dragdrop: $('#dragdrop'),

    initialize: function(uploadView, searchView) {

        var body = document.getElementsByTagName('body')[0];

        body.addEventListener('dragover', _.bind(this.handleDragOver, this));
        body.addEventListener('dragleave', _.bind(this.handleDragLeave, this))
        body.addEventListener('drop', _.bind(this.handleDrop, this));

        this.$dragdrop.find('.search').on('drop', _.bind(this.handleDrop, this));

        this.uploadView = uploadView;
        this.searchView = searchView;

    },

    handleDragOver: function(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        this.$dragdrop.show();
        this.$dragdrop.find('.' + HOVER).removeClass(HOVER);
        this.$dragdrop.find(evt.target).addClass(HOVER);
    },

    handleDragLeave: function(evt) {

        evt.stopPropagation();
        evt.preventDefault();

        var position = this.$dragdrop.position(),
            width = this.$dragdrop.width(),
            height = this.$dragdrop.height();

        if (evt.clientX < position.left || evt.clientY < position.top
            || evt.clientX > position.left + width || evt.clientY > position.top + height) {
            this.$dragdrop.hide().find('.' + HOVER).removeClass(HOVER);
        }

    },

    handleDrop: function(evt) {
        evt.preventDefault();
        this.$dragdrop.hide();
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