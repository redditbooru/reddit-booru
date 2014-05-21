(function(undefined) {

    'use strict';

    var SAVE_KEY = 'RB_Uploads',
        THUMB_LOCATION = 'http://beta.redditbooru.com/cache/',
        SAVE_DELAY = 500,
        CLICK = 'click',
        CHANGE = 'change';

    RB.UploadView = Backbone.View.extend({

        $upload: $('#upload'),
        $albumTitle: null,
        progressTimer: null,
        delayTimer: null,

        initialize: function() {
            $('body').on('click', '.upload', _.bind(this.handleClick, this));
            this.$upload
                .on(CHANGE, '#imageUrl', _.bind(this.handleUrlChange, this))
                .on(CHANGE, 'input', _.bind(this.handleTextChange, this))
                .on(CLICK, '.close', _.bind(this._hideDialog, this))
                .on(CLICK, '#imageFileButton', _.bind(this.handleUploadClick, this))
                .on(CLICK, '.remove', _.bind(this.handleRemoveClick, this))
                .on(CLICK, '.repostImage', _.bind(this.handleRepostClick, this))
                .on('submit', 'form', _.bind(this.handleSubmit, this));

            this.$albumTitle = this.$upload.find('.albumTitle');

            this._loadForm();

        },

        handleSubmit: function(evt) {
            evt.preventDefault();
            $.ajax({
                url: '/images/',
                data: this.$upload.find('form').serialize(),
                dataType: 'json',
                type: 'POST',
                success: _.bind(this.submitSuccess, this)
            });
        },

        handleTextChange: function(evt) {
            clearTimeout(this.delayTimer);
            this.delayTimer = setTimeout(_.bind(this._saveForm, this), SAVE_DELAY);
        },

        handleRemoveClick: function(evt) {
            $(evt.currentTarget).closest('li').remove();
            this._saveForm();
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
            this._urlUpload(evt.target.value);
        },

        handleRepostClick: function(evt) {
            var $parent = $(evt.currentTarget).closest('li');
            this._urlUpload($parent.attr('data-id'), true);
        },

        submitSuccess: function(data) {
            console.log(data);
            if (data.redirect) {
                RB.App.router.navigate(data.redirect);
            }
        },

        _urlUpload: function(url, force) {
            this.$upload.find('ul').append(RB.Templates.uploading({ id: url }));
            $.ajax({
                url: '/upload/?action=upload&imageUrl=' + escape(url) + (force ? '&force=true' : ''),
                dataType: 'json',
                success: _.bind(this._uploadComplete, this)
            });

            setTimeout(_.bind(this._checkProgress, this), 250);
        },

        _updateForm: function(titleOverride) {
            if (titleOverride) {
                this.$albumTitle.val(titleOverride);
            }

            if (this.$upload.find('li').length > 1) {
                this.$albumTitle.show();
            } else {
                this.$albumTitle.hide();
            }
        },

        _uploadComplete: function(data) {
            var out = '';

            if (!data.error) {
                data.thumb = THUMB_LOCATION + data.thumb;
                console.log(data);
                if (!data.identical) {
                    out = RB.Templates.uploadImageInfo(data);
                } else {
                    out = RB.Templates.uploadRepost(data);
                }
            } else {

            }

            this.$upload.find('[data-id="' + data.uploadId + '"]').replaceWith(out);
            this._saveForm();

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
                    $item = this.$upload.find('[data-id="' + i + '"] span').text(Math.round(data[i] * 100));
                }

                setTimeout(_.bind(this._checkProgress, this), 1000);
            }

        },

        /**
         * Saves the current items to local storage for later
         */
        _saveForm: function() {
            var data = {
                fields: [],
                title: this.$albumTitle.val()
            };

            this.$upload.find('li').each(function() {
                var $this = $(this),
                    item = {
                        id: $this.data('id'),
                        thumb: $this.find('img').attr('src'),
                        caption: $this.find('[name="caption[]"]').val(),
                        source: $this.find('[name="source[]"]').val(),
                        imageId: $this.find('[name="imageId[]"]').val()
                    };

                if (item.imageId) {
                    data.fields.push(item);
                }

            });

            window.localStorage[SAVE_KEY] = JSON.stringify(data);

        },

        _loadForm: function() {
            var data = window.localStorage[SAVE_KEY],
                $list = this.$upload.find('ul');

            if (data) {
                data = JSON.parse(data);
                _.each(data.fields, function(item) {
                    $list.append(RB.Templates.uploadImageInfo(item));
                });

                this._updateForm(data.title);

            }

        }

    });

}());