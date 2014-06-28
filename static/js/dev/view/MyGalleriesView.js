(function(undefined) {

    'use strict';

    RB.MyGalleriesView = Backbone.View.extend({

        uploadForm: null,
        router: null,

        initialize: function(router, uploadForm) {
            this.uploadForm = uploadForm;
            this.router = router;
            this._attachEventListeners();
        },

        _attachEventListeners: function() {
            $('body').on('click', '.user-galleries .edit-album', _.bind(this.handleEditClick, this));
        },

        handleEditClick: function(evt) {
            var $parent = $(evt.currentTarget).closest('li'),
                galleryId = $parent.data('id');
            this.uploadForm.loadGallery(window.galleries[galleryId]);
        }

    });

}());