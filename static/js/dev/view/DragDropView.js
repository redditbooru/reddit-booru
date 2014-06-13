(function(undefined) {
    
    'use strict';

    var HOVER = 'hover';

    RB.DragDropView = Backbone.View.extend({

        $dragdrop: $('#dragdrop'),

        initialize: function() {

            $('body')
                .on('dragover', _.bind(this.handleDragOver, this))
                .on('dragleave', _.bind(this.handleDragLeave, this))
                .on('drop', _.bind(this.handleDrop, this));

            this.$dragdrop.find('.search').on('drop', _.bind(this.handleDrop, this));

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

            if (evt.originalEvent.clientX < position.left || evt.originalEvent.clientY < position.top
                || evt.originalEvent.clientX > position.left + width || evt.originalEvent.clientY > position.top + height) {
                this.$dragdrop.hide().find('.' + HOVER).removeClass(HOVER);
            }

        },

        handleDrop: function(evt) {
            evt.preventDefault();
            this.$dragdrop.hide();
            console.log(evt.target);
        }

    });

}());