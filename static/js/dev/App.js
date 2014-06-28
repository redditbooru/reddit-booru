/**
 * RedditBooru main app view (controller)
 */
(function(undefined) {

    // Amount of time to wait on user to stop making changes before firing off requests
    var UPDATE_DELAY = 1000,
        HAS_CONTENT = 'hasContent',
        HIDDEN = 'hidden';

    RB.AppView = Backbone.View.extend({

        views: {},
        collections: {},
        router: new RB.Router,

        $title: $('#title'),
        $sidebar: $('#supporting'),

        _delayTimer: null,

        initialize: function() {

            var self = this;

            // Global collections
            this.collections = {
                sources: new RB.QueryOptionCollection(),
                images: new RB.ImageCollection(this.router)
            };

            // Bootstrap data
            this.collections.sources.reset(window.sources);
            if (window.startUp instanceof Array) {
                this.collections.images.reset(window.startUp);
            }

            var sidebar = new RB.SidebarView(),
                upload = new RB.UploadView(this.router),
                sources = new RB.QueryOptionsView($('#sources'), this.collections.sources),
                search = new RB.SearchView(sidebar, this.collections.images, sources, this.router, upload);

            // Views
            this.views = {
                sidebar: sidebar,
                sources: sources,
                images: new RB.ImageView($('#images'), this.collections.images),
                search: search,
                user: new RB.UserView(sidebar, this.collections.images, this.router),
                dragdrop: new RB.DragDropView(upload, search),
                upload: upload,
                gallery: new RB.GalleryView(this.router, sidebar),
                myGalleries: new RB.MyGalleriesView(this.router, upload),
                imageViewer: new RB.ImageViewer(this.router, this.collections.images)
            };

            // If the startup blob has a specific view associated, kick it off
            setTimeout(function() {
                if ('view' in window.startUp) {
                    self.views[window.startUp.view].initData(window.startUp);
                }
            }, 10);

        },

        setTitle: function(title) {
            if (title) {
                this.$title.html(title).removeClass(HIDDEN);
                document.title = title + ' - redditbooru';
            } else {
                this.$title.html('').addClass(HIDDEN);
                document.title = 'redditbooru - a place where cute girls come to meet';
            }
        },

        setSidebar: function(content) {
            if (content) {
                this.$sidebar.addClass(HAS_CONTENT).html(content);
            } else {
                this.$sidebar.removeClass(HAS_CONTENT).empty();
            }
            this.views.images.calculateWindowColumns();
        }

    });

    // Kick off execution
    RB.App = new RB.AppView();

}());