import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

export default Backbone.View.extend({

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
        $.getJSON(`/api/images/?postId=${galleryId}`).then(data => {
            if (data.length) {
                const first = data[0];
                const editData = {
                    age: first.age,
                    dateCreate: first.dateCreated,
                    id: first.postId,
                    externalId: first.externalId,
                    title: first.title,
                    images: data
                };
                this.uploadForm.loadGallery(editData);
            }
        });
    }

});