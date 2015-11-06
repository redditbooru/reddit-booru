import Backbone from 'backbone';
import $ from 'jquery';

import App from '../App';

const MENU_OPEN_CLASS = 'menu-open';

export default Backbone.View.extend({

  $body: $('body'),
  el: 'nav',

  initialize() {
    this.$el.on('click', '.open-menu', (evt) => {
      this.$body.toggleClass(MENU_OPEN_CLASS);
    });

    this.collection.on('updated', (evt) => {
      this.$body.removeClass(MENU_OPEN_CLASS);
    });
  }

});