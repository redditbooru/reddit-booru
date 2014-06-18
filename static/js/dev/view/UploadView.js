(function(undefined) {

    'use strict';

    var SAVE_KEY = 'RB_Uploads',
        SAVE_DELAY = 500,
        KEY_ENTER = 13,

        PROGRESS_BAR_THICKNESS = 20;

    RB.UploadView = Backbone.View.extend({

        $el: null,
        $albumTitle: null,

        progressTimer: null,
        delayTimer: null,
        editingPostId: null,

        events: {
            'paste #imageUrl': 'handlePaste',
            'keyup input': 'handleTextChange',
            'click .close': '_hideDialog',
            'click #imageFileButton': 'handleUploadClick',
            'click .remove': 'handleRemoveClick',
            'click .repostImage': 'handleRepostClick',
            'submit form': 'handleSubmit'
        },

        initialize: function(router) {
            $('body').on('click', '.upload', _.bind(this.handleNavClick, this));

            this.$el = $('#upload');
            this.$albumTitle = this.$el.find('.albumTitle');

            this.router = router;
            this._loadForm();

        },

        loadGallery: function(post) {
            var $list = this.$el.find('ul');
            this._pushForm();
            this.editingPostId = post.id;

            this.$albumTitle.val(post.title).show();

            _.each(post.images, function(item) {
                $list.append(RB.Templates.uploadImageInfo(item));
            });

            this._showDialog();
        },

        handleSubmit: function(evt) {
            var postId = this.editingPostId ? '?postId=' + this.editingPostId : '';
            evt.preventDefault();
            $.ajax({
                url: '/images/' + postId,
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
            new RB.Uploader(function(uploadId, fileName) {
                self._renderUploader(uploadId, fileName);
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
            $parent.remove();
        },

        submitSuccess: function(data) {
            if ('route' in data) {
                console.log('going to ' + data.route);
                this.router.go(data.route);
            } else if ('redirect' in data) {
                window.open(data.redirect);
            }
            // this._clearForm();
            this._hideDialog();
        },

        /**
         * If the URL is valid, begins a server side download.
         */
        _urlUpload: function(url, force, target) {
            // QnD URL validation
            var $target = target instanceof jQuery ? target : $(target);
            if (url.indexOf('http://') === 0 || url.indexOf('https://')) {

                this._renderUploader(url, url);

                $.ajax({
                    url: '/upload/?action=upload&imageUrl=' + escape(url) + (force ? '&force=true' : ''),
                    dataType: 'json',
                    success: _.bind(this._uploadComplete, this)
                });

                setTimeout(_.bind(this._checkProgress, this), 500);
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
                data.thumb = data.thumb;
                if (!data.identical) {
                    out = RB.Templates.uploadImageInfo(data);
                } else {
                    out = RB.Templates.uploadRepost(data);
                }
            } else {
                // error handling here
            }

            this.$el.find('[data-id="' + data.uploadId + '"]').replaceWith(out);
            this._updateForm();
            this._saveForm();

        },

        _showDialog: function() {
            this.$el.fadeIn();
        },

        _hideDialog: function() {
            this.$el.fadeOut();

            // If editing, revert back to the previous state
            if (this.editingPostId) {
                this._clearForm();
                this._loadForm();
                this.editingPostId = null;
            }

        },

        /**
         * Clears all upload data from the form
         */
        _clearForm: function() {
            this.$albumTitle.val('').hide();
            this.$el.find('li').remove();
            this._saveForm();
        },

        /**
         * Pushes the form state to before clearing it
         */
        _pushForm: function() {
            this._saveForm();
            this.$albumTitle.val('').hide();
            this.$el.find('li').remove();
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
console.log(data);
            if (null !== data) {

                for (i in data) {
                    $item = this.$el.find('[data-id="' + i + '"]').get(0).progress.progress(Math.round(data[i] * 100));
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

        },

        _renderUploader: function(id, url) {
            var $upload = $(RB.Templates.uploading({ id: id, url: url }));
            this.$el.find('ul').append($upload);
            $upload[0].progress = new RB.ProgressCircle($upload.find('.donut-loader'), 100, 150, '#e94e77', PROGRESS_BAR_THICKNESS);
        }

    });

}());