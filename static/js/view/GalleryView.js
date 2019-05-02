import Backbone from 'backbone';
import $ from 'jquery';

import App from '../App';
import gallery from '@views/gallery.hbs';

const RESIZE_BUTTON_ID = 'resizeGalleryImages';
const GALLERY = 'gallery';
// Should be calculated, but then again, a lot of things should be a lot of things
const HEADER_HEIGHT = 150;

export default Backbone.View.extend({

  initialize(router, sidebar) {
    this.sidebar = sidebar;
    const routeHandler = this.handleRoute.bind(this);
    router.addRoute('galleryNew', '/gallery/:id/:title', routeHandler);
    router.addRoute('galleryOld', '/gallery/:id', routeHandler);
    $('#images').on('click', `#${RESIZE_BUTTON_ID}`, this.handleExpandClick.bind(this));
  },

  initData(data) {
    this.displayGallery(data.images);
  },

  handleRoute(data) {
    this.sidebar.dismiss();

    if (!Array.isArray(data)) {
      if ('id' in data) {

        var id = data.id;

        // If there's a title, base convert the ID from 36 back to 10
        if ('title' in data) {
          id = parseInt(id, 36);
        }

        $.ajax({
          url: '/images/?postId=' + id,
          dataType: 'json',
          success: this.displayGallery.bind(this)
        });
      }
    }

  },

  displayGallery(data) {
    if (Array.isArray(data) && data.length > 0) {
      App.setTitle(data[0].title);

      // Add in resize info
      const viewportHeight = $(window).height() - HEADER_HEIGHT;
      let wasResized = false;
      data.forEach(image => {
          if (image.height > viewportHeight) {
            image.height = viewportHeight;
            wasResized = true;
          }
      });

      $('#images')
        .addClass(GALLERY)
        .html(gallery({ images: data, wasResized }));

    }
  },

  // Oh what a hack this is
  handleExpandClick(evt) {
      let buttonText = 'Shrink Images';
      document.querySelectorAll('.gallery-image img').forEach(image => {
        const styleAttr = image.getAttribute('style');
        const cachedStyleAttr = image.getAttribute('data-style');

        if (styleAttr) {
            image.removeAttribute('style');
            image.setAttribute('data-style', styleAttr);
            buttonText = 'Shrink Images';
        } else {
            // There's really no reason to remove the data attribute,
            // so just leave it
            image.setAttribute('style', cachedStyleAttr);
            buttonText = 'Expand Images';
        }
      });
      $(`#${RESIZE_BUTTON_ID}`).text(buttonText);
  }

});