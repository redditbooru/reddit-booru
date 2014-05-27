(function(undefined) {

    'use strict';

    var SAVE_KEY = 'RB_Uploads',
        THUMB_LOCATION = 'http://beta.redditbooru.com/cache/',
        SAVE_DELAY = 500,
        KEY_ENTER = 13;

    RB.UploadView = Backbone.View.extend({

        $el: null,
        $albumTitle: null,

        progressTimer: null,
        delayTimer: null,

        events: {
            'paste #imageUrl': 'handlePaste',
            'keyup input': 'handleTextChange',
            'click .close': '_hideDialog',
            'click #imageFileButton': 'handleUploadClick',
            'click .remove': 'handleRemoveClick',
            'click .repostImage': 'handleRepostClick',
            'submit form': 'handleSubmit'
        },

        initialize: function() {
            $('body').on('click', '.upload', _.bind(this.handleNavClick, this));

            this.$el = $('#upload');
            this.$albumTitle = this.$el.find('.albumTitle');

            this._loadForm();

        },

        handleSubmit: function(evt) {
            evt.preventDefault();
            $.ajax({
                url: '/images/',
                data: this.$el.find('form').serialize(),
                dataType: 'json',
                type: 'POST',
                success: _.bind(this.submitSuccess, this)
            });
        },

        handleTextChange: function(evt) {
            var keyCode = evt.keyCode || evt.charCode;
            if (evt.target.getAttribute('id') === 'imageUrl') {
                if (keyCode === KEY_ENTER) {
                    this._urlUpload(evt.target.value, false, evt.target);
                }
            } else {
                clearTimeout(this.delayTimer);
                this.delayTimer = setTimeout(_.bind(this._saveForm, this), SAVE_DELAY);
            }
        },

        handleRemoveClick: function(evt) {
            $(evt.currentTarget).closest('li').remove();
            this._saveForm();
        },

        handleNavClick: function(evt) {
            evt.preventDefault();
            this._showDialog();
        },

        handleUploadClick: function(evt) {
            var self = this;
            new RB.Uploader(function(uploadId) {
                self.$el.find('ul').append(RB.Templates.uploading({ id: uploadId }));
            }, _.bind(this._uploadComplete, this));
        },

        handlePaste: function(evt) {
            // I'm feeling too lazy to write browser detection support for clipboard data,
            // so just wait for the value to update in textbox and then handle stuff
            var self = this;
            setTimeout(function() {
                self._urlUpload(evt.target.value, false, evt.target);
            }, 10);
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

        /**
         * If the URL is valid, begins a server side download.
         */
        _urlUpload: function(url, force, target) {
            // QnD URL validation
            var $target = target instanceof jQuery ? target : $(target);
            if (url.indexOf('http://') === 0 || url.indexOf('https://')) {
                this.$el.find('ul').append(RB.Templates.uploading({ id: url }));
                $.ajax({
                    url: '/upload/?action=upload&imageUrl=' + escape(url) + (force ? '&force=true' : ''),
                    dataType: 'json',
                    success: _.bind(this._uploadComplete, this)
                });
                setTimeout(_.bind(this._checkProgress, this), 250);
                $target.val('').blur();
            }
        },

        _updateForm: function(titleOverride) {
            if (titleOverride) {
                this.$albumTitle.val(titleOverride);
            }

            if (this.$el.find('li').length > 1) {
                this.$albumTitle.show();
            } else {
                this.$albumTitle.hide();
            }
        },

        _uploadComplete: function(data) {
            var out = '';

            if (data && !data.error) {
                data.thumb = THUMB_LOCATION + data.thumb;
                console.log(data);
                if (!data.identical) {
                    out = RB.Templates.uploadImageInfo(data);
                } else {
                    out = RB.Templates.uploadRepost(data);
                }
            } else {
                // error handling here
            }

            this.$el.find('[data-id="' + data.uploadId + '"]').replaceWith(out);
            this._saveForm();

        },

        _showDialog: function() {
            this.$el.fadeIn();
        },

        _hideDialog: function() {
            this.$el.fadeOut();
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
                    $item = this.$el.find('[data-id="' + i + '"] span').text(Math.round(data[i] * 100));
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

            this.$el.find('li').each(function() {
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
                $list = this.$el.find('ul');

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