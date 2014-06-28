(function(undefined) {

    var $globalUpload = $('#globalUpload'),
        $upload = $globalUpload.find('[type="file"]'),
        $globalProgress = $('#globalUploaderProgress'),
        progress = new RB.ProgressCircle($globalProgress, 100, 260, '#e94e77', 20),

        ACCEPTED_FORMATS = [
            'image/jpeg',
            'image/gif',
            'image/png'
        ],

        triggerFileDialog = function() {
            var self = this;
            $upload
                .on('change', function(evt) {
                    uploadChange.call(self, evt);
                })
                .click();
        },

        uploadFile = function(file) {
            var xhr = new XMLHttpRequest(),
                self = this,
                formData = new FormData();

            this.onBegin(this.uploadId, file.name);

            if (this.showProgress) {
                progress.progress(0);
                $globalProgress.fadeIn();
            }

            // Validate the type
            if (ACCEPTED_FORMATS.indexOf(file.type) === -1) {
                alert('Image must be a JPEG, GIF, or PNG');
                return;
            }

            if (typeof this.onProgress === 'function') {
                xhr.upload.addEventListener('progress', function(evt) {
                    var percent = Math.round(evt.loaded / evt.total * 100);
                    if (self.showProgress) {
                        progress.progress(percent);
                    }
                    self.onProgress(percent, self.uploadId);
                });
            }

            xhr.addEventListener('readystatechange', function() {
                if (xhr.readyState === 4) {
                    try {
                        self.onComplete(JSON.parse(xhr.responseText));
                    } catch (e) {
                        // do nothing because I hate error management. Someday...
                    }

                    if (self.showProgress) {
                        $globalProgress.fadeOut();
                    }

                }
            });

            xhr.open('POST', this.endpoint, true);
            xhr.setRequestHeader('X-FileName', file.name);
            formData.append('upload', file);
            formData.append('uploadId', this.uploadId);
            xhr.send(formData);

            $upload.off('change');

        },

        uploadChange = function(evt) {
            var files = evt.target.files,
                file;

            if (files.length > 0) {

                file = files[0];

                // Kick off the upload
                uploadFile.call(this, file);

            }
        };

    RB.Uploader = function(onBegin, onComplete, onProgress, file, endpoint, showProgress) {
        this.onBegin = onBegin;
        this.onComplete = onComplete;
        this.onProgress = onProgress || null;
        this.uploadId = Date.now();
        this.endpoint = endpoint || '/upload/?action=upload';
        this.showProgress = showProgress || false;

        if (!file) {
            triggerFileDialog.call(this);
        } else {
            uploadFile.call(this, file);

        }
    };

    RB.Uploader.showGlobalProgress = function() {
        $globalProgress.show();
    };

    RB.Uploader.hideGlobalProgress = function() {
        $globalProgress.hide();
    };

    RB.Uploader.getGlobalProgress = function() {
        return progress;
    };

}());