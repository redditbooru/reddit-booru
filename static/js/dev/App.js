import Backbone from 'backbone';
import $ from 'jquery';

import Router from './controls/Routes.js';
import TplHelpers from './controls/TplHelpers';
import QueryOptionCollection from './model/QueryOptionCollection';
import ImageCollection from './model/ImageCollection';
import FiltersView from './view/FiltersView';
import SearchView from './view/SearchView';
import SidebarView from './view/SidebarView';
import UploadView from './view/UploadView';
import ImageView from './view/ImageView';
import UserView from './view/UserView';
import DragDropView from './view/DragDropView';
import GalleryView from './view/GalleryView';
import MyGalleriesView from './view/MyGalleriesView';
import ImageViewer from './view/ImageViewer';
import NavView from './view/NavView';

// Amount of time to wait on user to stop making changes before firing off requests
const UPDATE_DELAY = 1000;
const HAS_CONTENT = 'hasContent';
const HIDDEN = 'hidden';
const MODAL_OPEN_CLASS = 'modal-open';

var AppView = Backbone.View.extend({

    views: {},
    collections: {},
    router: new Router(),

    $body: $('body'),
    $title: $('#title'),
    $sidebar: $('#supporting'),

    _delayTimer: null,

    initialize: function() {

        var self = this;

        // Global collections
        this.collections = {
            sources: new QueryOptionCollection(),
            images: new ImageCollection(this.router)
        };

        // Bootstrap data
        this.collections.sources.reset(window.sources);
        if (window.startUp instanceof Array) {
            this.collections.images.reset(window.startUp);
        }

        var sidebar = new SidebarView(),
            upload = new UploadView(this.router),
            filters = new FiltersView({
                el: $('.filters').get(0),
                collection: this.collections.sources
            }),
            search = new SearchView(sidebar, this.collections.images, filters, this.router, upload);

        // Views
        this.views = {
            sidebar: sidebar,
            filters: filters,
            images: new ImageView({ collection: this.collections.images }),
            search: search,
            user: new UserView(sidebar, this.collections.images, this.router),
            dragdrop: new DragDropView(upload, search),
            upload: upload,
            gallery: new GalleryView(this.router, sidebar),
            myGalleries: new MyGalleriesView(this.router, upload),
            imageViewer: new ImageViewer(this.router, this.collections.images),
            nav: new NavView({ collection: this.collections.images })
        };

        // If the startup blob has a specific view associated, kick it off
        setTimeout(function() {
            if (window.startUp && 'view' in window.startUp) {
                self.views[window.startUp.view].initData(window.startUp);
            }
        }, 10);

    },

    setTitle: function(title) {
        if (title) {
            this.$title.html(title).removeClass(HIDDEN);
            document.title = this.$title.text() + ' - redditbooru';
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
    },

    toggleModalMode(modalOpen) {
        this.$body.toggleClass(MODAL_OPEN_CLASS, modalOpen);
    }

});

var App = new AppView();
if (!window._App) {
    window._App = App;
}

export default window._App;