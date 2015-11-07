(function(undefined) {

    // quick underscore fill because this file needs to execute quickly
    var _ = {
            each: function(collection, callback) {
                for (var i in collection) {
                    if (collection.hasOwnProperty(i)) {
                        callback(collection[i], i);
                    }
                }
            }
        },

        displayUploadDialog = function(url) {
            if (!window._App) {
                setTimeout(() => { displayUploadDialog(url); }, 10);
            } else {
                window._App.views.upload.openWithUpload(url);
            }
        },

        parseQueryString = function(qs) {
            var params = {};
            qs = qs.split('&');

            _.each(qs, function(item) {
                var kvp = item.split('=', 2);
                if (kvp.length === 1) {
                    params[kvp[0]] = true;
                } else {
                    params[kvp[0]] = kvp[1];
                }
            });

            return params;
        },

        hash = window.location.hash.replace('#!', '') || '',
        params = {};

    // Redirects old RB1 URLs to the equivelent RB2 URL
    if (window.location.hash || window.location.href.indexOf('?')) {

        // if there is no hash, we're on a dialog request. process and bail
        if (!hash.length) {

            qs = window.location.href.split('?');
            if (qs.length > 1) {
                params = parseQueryString(qs[1]);
                if ('dialog' in params) {
                    switch (params.dialog) {
                        case 'upload':
                            displayUploadDialog(params.rehost ? decodeURIComponent(params.rehost) : undefined);
                            break;
                        case 'screensaver':
                            setTimeout(function() {
                                RB.App.views.imageViewer.startScreensaver();
                            }, 1000);
                            break;
                    }
                }
            }

        } else {

            params = parseQueryString(hash);

            // dialog is handled through internal object routing, everything else is redirected to search
            if ('dialog' in params) {
                if ('upload' === params.dialog) {
                    displayUploadDialog();
                }
            } else {
                if ('q' in params && params.q.indexOf('http') === 0) {
                    params.imageUri = params.q;
                    delete params.q;
                }

                if ('source' in params) {
                    params.sources = params.source;
                    delete params.source;
                }

                qs = [];
                _.each(params, function(value, key) {
                    qs.push(key + (value ? '=' + value : ''));
                });

                window.location.href = '/search/?' + qs.join('&');
            }

        }

    }

}());
