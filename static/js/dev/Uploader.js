(function(undefined) {

    var IFRAME = '<iframe id="upload_%TIMESTAMP%"></iframe>',

        $globalUpload = $('#globalUpload'),
        $upload = $globalUpload.find('[type="file"]'),
        $timestamp = $globalUpload.find('[type="hidden"]'),

        triggerFileDialog = function() {
            var self = this;
            $upload
                .on('change', function(evt) {
                    uploadChange.call(self, evt);
                })
                .click();
        },

        insertIframe = function(timestamp) {
            $(IFRAME.replace('%TIMESTAMP%', timestamp)).insertAfter($globalUpload);
        },

        removeIframe = function(timestamp) {
            $('#upload_' + timestamp).remove();
        },

        uploadChange = function(evt) {
            var timestamp = Date.now(),
                self = this;
            $upload.off('change');
            if (evt.target.value) {
                insertIframe(timestamp);
                $timestamp.val(timestamp);
                window['callback_' + timestamp] = function(data) {
                    self.callback(data);
                    removeIframe(timestamp);
                };
                $globalUpload.attr('target', 'upload_' + timestamp).submit();
            }
        };

    RB.Uploader = function(callback) {
        this.callback = callback;
        triggerFileDialog.call(this);
    };

}());