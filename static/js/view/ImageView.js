// TODO - This whole file is fucking awful. Blow it away and try again
import Backbone from 'backbone';
import $ from 'jquery';

import ImageCollection from '../model/ImageCollection';

import imagesRow from '@views/imagesRow.hbs';
import moreRow from '@views/moreRow.hbs';

const $window = $(window);

// This number helps determine the total number of columns there should be per row
const AVERAGE_COLUMN_WIDTH = 300;

// MINUMUM WIDTH TO HEIGHT RATIO ALLOWED
const MIN_RATIO = 0.9;

// The number of pixels between each image
const IMAGE_GUTTER = 20;

export default Backbone.View.extend({

  el: '#images',

  templates: {
    imagesRow: imagesRow,
    moreRow: moreRow
  },

  sources: [ 1 ], // TODO - make this not hard coded

  initialize() {
    this.calculateWindowColumns();

    $window.on('resize', this.calculateWindowColumns.bind(this));
    $('body').on('click', '.more-row button', this.handleMoreClick.bind(this));
    this.collection.on('updated', this.render.bind(this));
    this.collection.on('reset', this.handleCollectionReset.bind(this));
  },

  handleMoreClick(evt) {
    this.collection.loadNext();
  },

  handleCollectionReset() {
    this.$el.empty();
  },

  render() {
    const { $el } = this;
    const itemsToRender = new ImageCollection();
    let out = '';
    let newItems = 0;
    this.collection.each(item => {
      itemsToRender.push(item);

      // TODO - What the actual fuck is going on here?
      if (!item.rendered) {
        newItems++;
        item.rendered = true;
      }

      if (itemsToRender.length === this.columns) {

        // Only add this to the output if there were new items on this row
        if (newItems) {
          out = out.concat(this._drawColumn(itemsToRender));
        }

        itemsToRender.reset();
        newItems = 0;
      }
    });

    // If nothing was rendered, just run with what we have. Fixes https://github.com/dxprog/reddit-booru/issues/41
    if (!out.length && itemsToRender.length > 0) {
      out = this._drawColumn(itemsToRender);
    }

    // If there is already content on the page, we don't want to force a complete refresh on a partial
    // update, so remove the more button and the last row and append the diff
    $el.find('.more-row').remove();
    $el.append(out);

    // Only display the more row if there are more images than columns
    if (this.collection.length > this.columns) {
      $el.append(this.templates.moreRow());
    }
  },

  /**
   * Calculates how many columns there should be and redraws if there's been a change
   */
  calculateWindowColumns(evt) {
    const oldColumnCount = this.columns;
    const currentWidth = this.$el.width();

    if (currentWidth !== this.windowWidth) {
      this.windowWidth = this.width = currentWidth;
      this.columns = Math.floor(this.width / AVERAGE_COLUMN_WIDTH);
      this.width -= this.columns * IMAGE_GUTTER;
      if (evt) {
        this.resize();
      } else {
        this.render();
      }
    }
  },

  resize() {
    const $images = this.$el.find('.image');
    const $out = $('<section id="images"></section>');
    const cols = [];
    const gutterPercent = this._calculateGutterPercent();
    const self = this;
    let $col = null;
    let itemsInCol = 0;
    let widthRatioSum = 0;
    let itemsAppended = 0;

    // Quick fix for https://github.com/dxprog/reddit-booru/issues/16
    if (!$images.length) {
      return;
    }

    // Recalculate the width of the images
    $images.each((index, image) => {
      widthRatioSum += parseFloat(image.dataset.ratio);
      itemsInCol++;
      if (itemsInCol === self.columns) {
        cols.push(widthRatioSum);
        itemsInCol = 0;
        widthRatioSum = 0;
      }
    });
    itemsInCol = 0;

    // Relayout the columns
    $images.each((index, image) => {
      const $this = $(image);

      if (itemsInCol === 0) {
        widthRatioSum = cols.shift();
        $col = $('<div class="image-row"></div>');
      }

      $this.css('width', (Math.round(parseFloat(image.dataset.ratio) / widthRatioSum * 10000) / 100 - (itemsInCol > 0 ? gutterPercent : 0)) + '%');

      $col.append($this);
      itemsInCol++;

      if (itemsInCol === self.columns) {
        $out.append($col);
        itemsAppended += itemsInCol;
        itemsInCol = 0;
      }
    });

    // If there's something to render but nothing was rendered, render what we have
    if (!itemsAppended && itemsInCol) {
      $out.append($col);
    }

    $out.append(this.templates.moreRow());
    this.$el.replaceWith($out);
    this.$el = $out;
  },

  _drawColumn(images) {
    let widthRatioSum = 0;
    const gutterPercent = this._calculateGutterPercent();

    // We're going to make some view specific changes to the data,
    // so serialize a bland copy for us to edit and pipe to the template
    images = images.toJSON();

    // Now we loop through each image in the row, get it's width to height ratio,
    // and sum them all together for later
    images.forEach(image => {
      image.widthHeightRatio = image.width / image.height;
      image.widthHeightRatio = image.widthHeightRatio < MIN_RATIO ? MIN_RATIO : image.widthHeightRatio;
      widthRatioSum += image.widthHeightRatio;
    });

    // Using the sum we just got, we'll figure out what percentage of the total
    // width each image should get
    images.forEach((image, index) => {
      image.viewWidth = Math.round(image.widthHeightRatio / widthRatioSum * 10000) / 100 - (index > 0 ? gutterPercent : 0);
    });

    // Finally, render and return the template
    return this.templates.imagesRow(images);
  },

  _calculateGutterPercent() {
    return Math.round(IMAGE_GUTTER / this.width * 10000) / 100;
  }

});