(function(undefined) {

    'use strict';

    RB.UploadView = Backbone.View.extend({

        $upload: $('#upload'),
        progressTimer: null,

        initialize: function() {
            $('body').on('click', '.upload', _.bind(this.handleClick, this));
            this.$upload
                .on('change', '#imageUrl', _.bind(this.handleUrlChange, this))
                .on('click', '.close', _.bind(this._hideDialog, this))
                .on('click', '#imageFileButton', _.bind(this.handleUploadClick, this));
        },

        handleClick: function(evt) {
            evt.preventDefault();
            this._showDialog();
        },

        handleUploadClick: function(evt) {
            new RB.Uploader(function(data) {
                console.log('whoa');
            });
        },

        handleUrlChange: function(evt) {
            var url = evt.target.value,
                $upload = this.$upload;

            $upload.find('ul').append(RB.Templates.uploading({ id: url }));
            $.ajax({
                url: '/upload/?action=upload&imageUrl=' + escape(url),
                dataType: 'json',
                success: function(data) {

                    $upload.find('[data-id="' + url + '"]').replaceWith(RB.Templates.uploadImageInfo({ id: url, thumb: data.thumb }));

                }
            });

            setTimeout(_.bind(this._checkProgress, this), 250);

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

        }

    });

}());