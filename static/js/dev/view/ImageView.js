/**
 * Image view
 */
(function(undefined) {

    var $window = $(window),

        // This number helps determine the total number of columns there should be per row
        AVERAGE_COLUMN_WIDTH = 300,

        // MINUMUM WIDTH TO HEIGHT RATIO ALLOWED
        minRatio = 0.9,

        // The number of pixels between each image
        IMAGE_GUTTER = 20;

    RB.ImageView = Backbone.View.extend({

        templates: {
            imagesRow: RB.Templates.imagesRow,
            moreRow: RB.Templates.moreRow
        },

        collection: null,
        sources: [ 1 ], // TODO - make this not hard coded

        initialize: function($el, collection) {
            this.collection = collection;
            this.$el = $el;
            this.calculateWindowColumns();

            $(window).on('resize', _.bind(this.calculateWindowColumns, this));

            $('body').on('click', '.more-row button', _.bind(this.handleMoreClick, this));

            this.collection.on('updated', _.bind(function() {
                this.render();
            }, this));
            this.collection.on('reset', function() {
                $el.empty();
            });
        },

        handleMoreClick: function(evt) {
            this.collection.loadNext();
        },

        render: function() {

            var itemsToRender = new RB.ImageCollection(),
                out = '',
                newItems = 0,
                append = false,
                $el = this.$el;

            this.collection.each(function(item) {
                itemsToRender.push(item);

                if (item.rendered) {
                    append = true;
                } else {
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
                    count = 0;
                }
            }, this);

            // Originally, at this point we'd force render the last row, but it's going to look ugly, so don't
            /*
            if (itemsToRender.length > 0) {
                out = out.concat(this._drawColumn(itemsToRender));
            }
            */

            // If there is already content on the page, we don't want to force a complete refresh on a partial
            // update, so remove the more button and the last row and append the diff

            // Slap on the more button
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
        calculateWindowColumns: function(evt) {
            var oldColumnCount = this.columns,
                currentWidth = this.$el.width(),
                self = this;

            if (currentWidth !== this.windowWidth) {
                this.windowWidth = this.width = currentWidth;
                this.columns = Math.floor(this.width / AVERAGE_COLUMN_WIDTH);
                this.width -= this.columns * IMAGE_GUTTER;
                if (evt) {
                    self.resize();
                } else {
                    this.render();
                }
            }
        },

        resize: function() {

            var $images = this.$el.find('.image'),
                $out = $('<section id="images"></section>'),
                $col = null,
                itemsInCol = 0,
                self = this,
                cols = [],
                widthRatioSum = 0,
                gutterPercent = Math.round(IMAGE_GUTTER / this.width * 10000) / 100;

            // Quick fix for https://github.com/dxprog/reddit-booru/issues/16
            if (!$images.length) {
                return;
            }

            // Recalculate the width of the images
            $images.each(function() {
                widthRatioSum += parseFloat(this.dataset.ratio);
                itemsInCol++;
                if (itemsInCol === self.columns) {
                    cols.push(widthRatioSum);
                    itemsInCol = 0;
                    widthRatioSum = 0;
                }
            });
            itemsInCol = 0;

            // Relayout the columns
            _.each($images, function(image) {
                var $this = $(image);

                if (itemsInCol === 0) {
                    widthRatioSum = cols.shift();
                    $col = $('<div class="image-row"></div>');
                }

                $this.css('width', (Math.round(parseFloat(image.dataset.ratio) / widthRatioSum * 10000) / 100 - (itemsInCol > 0 ? gutterPercent : 0)) + '%');

                $col.append($this);
                itemsInCol++;

                if (itemsInCol === self.columns) {
                    $out.append($col);
                    itemsInCol = 0;
                }

            });

            $out.append(this.templates.moreRow());
            this.$el.replaceWith($out);
            this.$el = $out;

        },

        _drawColumn: function(images) {

            var widthRatioSum = 0,
                gutterPercent = Math.round(IMAGE_GUTTER / this.width * 10000) / 100;

            // We're going to make some view specific changes to the data,
            // so serialize a bland copy for us to edit and pipe to the template
            images = images.toJSON();

            // Now we loop through each image in the row, get it's width to height ratio,
            // and sum them all together for later
            _.each(images, function(image, index) {
                image.widthHeightRatio = image.width / image.height;
                image.widthHeightRatio = image.widthHeightRatio < minRatio ? minRatio : image.widthHeightRatio;
                widthRatioSum += image.widthHeightRatio;
            });

            // Using the sum we just got, we'll figure out what percentage of the total
            // width each image should get
            _.each(images, function(image, index) {
                image.viewWidth = Math.round(image.widthHeightRatio / widthRatioSum * 10000) / 100 - (index > 0 ? gutterPercent : 0);
            }, this);

            // Finally, render and return the template
            return this.templates.imagesRow(images);

        }

    });

}());
