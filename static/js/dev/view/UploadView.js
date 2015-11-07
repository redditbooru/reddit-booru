import Backbone from 'backbone';
import $ from 'jquery';
import _ from 'underscore';

import App from '../App';
import ProgressCircle from '../controls/ProgressCircle';
import Uploader from '../controls/Uploader';
import { initOverlay, showOverlay, hideOverlay } from '../controls/overlay';

import uploadImageInfo from '../../../../views/uploadImageInfo.hbs';
import uploadRepost from '../../../../views/uploadRepost.hbs';
import uploading from '../../../../views/uploading.hbs';

const SAVE_KEY = 'RB_Uploads';
const SAVE_DELAY = 500;
const KEY_ENTER = 13;
const PROGRESS_BAR_THICKNESS = 20;
const OPEN_CLASS = 'open';
const IMAGE_UPLOAD_CLASS = '.image-upload';

export default Backbone.View.extend({

    el: '#upload',
    $albumTitle: null,
    $body: $('body'),
    $uploads: null,

    progressTimer: null,
    delayTimer: null,
    editingPostId: null,

    events: {
        'paste #imageUrl': 'handlePaste',
        'click .close': '_hideDialog',
        'click #imageFileButton': 'beginUpload',
        'click .remove': 'handleRemoveClick',
        'click .repostImage': 'handleRepostClick',
        'keypress form': 'handleTextChange',
        'submit form': 'handleSubmit'
    },

    initialize: function(router) {
        this.$body.on('click', '.upload', _.bind(this.handleNavClick, this));
        this.$albumTitle = this.$el.find('.albumTitle');
        this.$uploads = this.$el.find('ul.uploads');

        initOverlay(this.el);

        this.router = router;
        this._loadForm();
    },

    /**
     * Method for external views to open the dialog and start an image upload
     * @param image {String|File} URL or image file object to begin uploading
     */
    openWithUpload: function(image) {
        this._showDialog();

        if (image instanceof File) {
            this.beginUpload(image);
        } else if (image) {
            this._urlUpload(image);
        }
    },

    loadGallery: function(post) {
        var $list = this.$uploads;
        this._pushForm();
        this.editingPostId = post.id;

        this.$albumTitle.val(post.title).show();

        _.each(post.images, function(item) {
            $list.append(uploadImageInfo(item));
        });

        this._showDialog();
    },

    handleSubmit: function(evt) {
        var postId = this.editingPostId ? '?postId=' + this.editingPostId : '';
        evt.preventDefault();

        this.$albumTitle.removeClass('error');
        if (this.$uploads.find(IMAGE_UPLOAD_CLASS).length > 1 && $.trim(this.$albumTitle.val()).length === 0) {
            this.$albumTitle.addClass('error');
            return;
        }

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

        // If the target is the image URL textbox, check for submit, otherwise save the for
        // because it was a change on the album title
        if (evt.target.getAttribute('id') === 'imageUrl') {
            if (keyCode === KEY_ENTER) {
                evt.preventDefault();
                evt.stopPropagation();
                this._urlUpload(evt.target.value, false, evt.target);
            }
        } else {
            if ($.trim(this.$albumTitle.val()).length > 0) {
                this.$albumTitle.removeClass('error');
            }
            clearTimeout(this.delayTimer);
            this.delayTimer = setTimeout(_.bind(this._saveForm, this), SAVE_DELAY);
        }
    },

    handleRemoveClick: function(evt) {
        $(evt.currentTarget).closest(IMAGE_UPLOAD_CLASS).remove();
        if (this.$uploads.find(IMAGE_UPLOAD_CLASS).length <= 1) {
            this.$albumTitle.hide();
        }
        this._saveForm();
    },

    handleNavClick: function(evt) {
        evt.preventDefault();
        this._showDialog();
    },

    beginUpload: function(evt) {
        var self = this,
            file = evt instanceof File ? evt : undefined;
        new Uploader(function(uploadId, fileName) {
                self._renderUploader(uploadId, fileName);
            },
            _.bind(this._uploadComplete, this),
            _.bind(this._fileUploadProgress, this),
            file);
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
        var $parent = $(evt.currentTarget).closest(IMAGE_UPLOAD_CLASS);
        this._urlUpload($parent.attr('data-id'), true);
        $parent.remove();
    },

    submitSuccess: function(data) {
        this._clearForm();
        this._hideDialog();
        if ('route' in data) {
            this.router.go(data.route);
        } else if ('redirect' in data) {
            window.location.href = data.redirect;
        }
    },

    /**
     * If the URL is valid, begins a server side download.
     */
    _urlUpload: function(url, force, target) {
        // QnD URL validation
        var $target = target instanceof $ ? target : $(target);
        if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0 || url.indexOf('/tmp') === 0) {

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

        if (this.$uploads.find(IMAGE_UPLOAD_CLASS).length > 1) {
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
                out = uploadImageInfo(data);
            } else {
                out = uploadRepost(data);
            }
        } else {
            // error handling here
        }

        this.$el.find('[data-id="' + data.uploadId + '"]').replaceWith(out);
        this._updateForm();
        this._saveForm();

    },

    _showDialog: function() {
        showOverlay(this.el, () => {
            this.$el.addClass(OPEN_CLASS);
            App.toggleModalMode(true);
        });
    },

    _hideDialog: function() {
        this.$el.removeClass(OPEN_CLASS);
        App.toggleModalMode(false);
        hideOverlay(this.el);

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
        this.$uploads.find(IMAGE_UPLOAD_CLASS).remove();
        this._saveForm();
    },

    /**
     * Pushes the form state to before clearing it
     */
    _pushForm: function() {
        this._saveForm();
        this.$albumTitle.val('').hide();
        this.$uploads.find(IMAGE_UPLOAD_CLASS).remove();
    },

    _checkProgress: function() {
        $.ajax({
            url: '/upload/?action=status',
            dataType: 'json',
            success: _.bind(this._progressCallback, this)
        });
    },

    _progressCallback(data) {
        let $item = null;
        let itemsUpdated = false;

        if (null !== data) {

            for (let i in data) {
                $item = this.$el.find('[data-id="' + i + '"]');
                if ($item.length) {
                    $item.get(0).progress.progress(Math.round(data[i] * 100));
                    itemsUpdated = true;
                }
            }

            // Only return if something actually caught our eye
            if (itemsUpdated) {
                setTimeout(_.bind(this._checkProgress, this), 250);
            }
        }

    },

    _fileUploadProgress: function(progress, uploadId) {
        this.$el.find('[data-id="' + uploadId + '"]').get(0).progress.progress(progress);
    },

    /**
     * Saves the current items to local storage for later
     */
    _saveForm: function() {
        var data = {
            fields: [],
            title: this.$albumTitle.val()
        };

        this.$uploads.find(IMAGE_UPLOAD_CLASS).each(function() {
            var $this = $(this),
                item = {
                    id: $this.data('id'),
                    thumb: $this.find('img').attr('src'),
                    caption: $this.find('[name="caption[]"]').val(),
                    sourceUrl: $this.find('[name="source[]"]').val(),
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
            $list = this.$uploads;

        if (data) {
            data = JSON.parse(data);
            _.each(data.fields, function(item) {
                $list.append(uploadImageInfo(item));
            });

            this._updateForm(data.title);

        }

    },

    _renderUploader: function(id, url) {
        var $upload = $(uploading({ id: id, url: url })),
            $list = this.$uploads;
        $list.append($upload);
        $upload[0].progress = new ProgressCircle($upload.find('.donut-loader'), 100, 150, '#e94e77', PROGRESS_BAR_THICKNESS);
        $list.scrollTop($list[0].scrollHeight);
    }

});