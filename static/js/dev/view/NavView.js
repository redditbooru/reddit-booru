import Backbone from 'backbone';
import $ from 'jquery';

import App from '../App';

const MENU_OPEN_CLASS = 'menu-open';

export default Backbone.View.extend({

  el: 'header',

  initialize() {
    this.$el.on('click', '.open-menu', (evt) => {
      App.$body.toggleClass(MENU_OPEN_CLASS);
    });

    this.collection.on('updated', (evt) => {
      App.$body.removeClass(MENU_OPEN_CLASS);
    });
  }

});