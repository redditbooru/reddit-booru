(function (undefined) {

    RB.SidebarView = Backbone.View.extend({

        $sidebar: $('#supporting'),

        initialize: function() {

        },

        populate: function(html, owner) {
            this.$sidebar.html(html).addClass('hasContent');

            // TODO - this should probably be event controlled
            RB.App.views.images.calculateWindowColumns();
            RB.App.views.images.render();
        }

    });

}());