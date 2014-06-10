(function(undefined) {

    RB.GalleryView = Backbone.View.extend({

        initialize: function(router, sidebar) {
            router.on('newGallery', function(data) {
                console.log(data);
            });
        }

    });

}())