/**
 * reddit-booru extension for chrome
 */
(function() {

    var
        
        xhRequest = function(options) {
        
            if (typeof options.url === 'string') {
                
                options.type = options.type || 'GET';
                
                var xhr = new XMLHttpRequest();
                if (typeof options.success === 'function') {
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            options.success(JSON.parse(xhr.responseText));
                        }
                    };
                }
                
                if (typeof options.error === 'function') {
                    xhr.onerror = options.error;
                }
                
                xhr.open(options.type, options.url, true);
                xhr.send();
                
            }
        
        },
        
        imageSearch = function(info, tab) {
            window.open('http://redditbooru.com/#!q=' + encodeURIComponent(info.srcUrl), '_blank');
        },
        
        sourceSearch = function(info, tab) {
            window.open('http://redditbooru.com/#!q=' + encodeURIComponent(info.srcUrl) + '&source=source', '_blank');
        },
        
        rehostSuccess = function(data) {
            if (typeof data === 'object') {
                if (data.success) {
                    window.open(data.image.cdnUrl, '_blank');
                } else {
                    alert('Unable to rehost image: ' + data.message);
                }
            } else {
                alert('Unable to rehost image: invalid server response');
            }
        },
        
        rehostError = function() {
            alert('Unable to rehost image: invalid server response');
        },
        
        rehostClick = function(info, tab) {
            window.open('http://redditbooru.com/upload.php?url=' + encodeURIComponent(info.srcUrl) + '&redirect=true', '_blank');
        },
        
        init = (function() {
        
            // Create the context menu
            chrome.contextMenus.create({ title:'Search for repost', contexts:[ 'image' ], onclick:imageSearch });
            // chrome.contextMenus.create({ title:'Search for image source', contexts:[ 'image' ], onclick:sourceSearch });
            chrome.contextMenus.create({ title:'Rehost image', contexts:[ 'image' ], onclick:rehostClick });
        
        }());

}());