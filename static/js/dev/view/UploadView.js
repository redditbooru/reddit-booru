(function(undefined) {

    'use strict';

    var SAVE_KEY = 'RB_Uploads',
        THUMB_LOCATION = 'http://beta.redditbooru.com/cache/'

    RB.UploadView = Backbone.View.extend({

        $upload: $('#upload'),
        progressTimer: null,

        initialize: function() {
            $('body').on('click', '.upload', _.bind(this.handleClick, this));
            this.$upload
                .on('change', '#imageUrl', _.bind(this.handleUrlChange, this))
                .on('click', '.close', _.bind(this._hideDialog, this))
                .on('click', '#imageFileButton', _.bind(this.handleUploadClick, this));

            this._loadForm();

        },

        handleClick: function(evt) {
            evt.preventDefault();
            this._showDialog();
        },

        handleUploadClick: function(evt) {
            var self = this;
            new RB.Uploader(function(uploadId) {
                self.$upload.find('ul').append(RB.Templates.uploading({ id: uploadId }));
                console.log(self);
            }, _.bind(this._uploadComplete, this));
        },

        handleUrlChange: function(evt) {
            var url = evt.target.value;

            this.$upload.find('ul').append(RB.Templates.uploading({ id: url }));
            $.ajax({
                url: '/upload/?action=upload&imageUrl=' + escape(url),
                dataType: 'json',
                success: _.bind(this._uploadComplete, this)
            });

            setTimeout(_.bind(this._checkProgress, this), 250);

        },

        _uploadComplete: function(data) {
            if (!data.error) {
                this.$upload.find('[data-id="' + data.uploadId + '"]').replaceWith(RB.Templates.uploadImageInfo({ id: data.uploadId, thumb: THUMB_LOCATION + data.thumb }));
                this._saveForm();
            }
        },

        _showDialog: function() {
            this.$upload.fadeIn();
        },

        _hideDialog: function() {
            this.$upload.fadeOut();
        },

        _checkProgress: function() {
            $.ajax({
                url: '/upload/?action=status',
                dataType: 'json',
                success: _.bind(this._progressCallback, this)
            });
        },

        _progressCallback: function(data) {
            var i,
                $item = null;

            if (null !== data) {

                for (i in data) {
                    $item = this.$upload.find('[data-id="' + i + '"]').text('Uploading... ' + Math.round(data[i] * 100) + '%');
                }

                setTimeout(_.bind(this._checkProgress, this), 1000);
            }

        },

        /**
         * Saves the current items to local storage for later
         */
        _saveForm: function() {
            var data = [];

            this.$upload.find('li').each(function() {
                var $this = $(this),
                    item = {
                        id: $this.data('id'),
                        thumb: $this.find('img').attr('src'),
                        caption: $this.find('[name="caption[]"]').val(),
                        source: $this.find('[name="source[]"]').val()
                    };
                data.push(item);
            });

            window.localStorage[SAVE_KEY] = JSON.stringify(data);

        },

        _loadForm: function() {
            var data = window.localStorage[SAVE_KEY],
                $list = this.$upload.find('ul');

            if (data) {
                data = JSON.parse(data);
                _.each(data, function(item) {
                    $list.append(RB.Templates.uploadImageInfo(item));
                });
            }

        }

    });

}());