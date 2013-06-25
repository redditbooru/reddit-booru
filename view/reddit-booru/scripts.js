/*!
 * jQuery Cookie Plugin v1.3
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2011, Klaus Hartl
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.opensource.org/licenses/GPL-2.0
 */
(function ($, document, undefined) {

    var pluses = /\+/g;

    function raw(s) {
        return s;
    }

    function decoded(s) {
        return decodeURIComponent(s.replace(pluses, ' '));
    }

    var config = $.cookie = function (key, value, options) {

        // write
        if (value !== undefined) {
            options = $.extend({}, config.defaults, options);

            if (value === null) {
                options.expires = -1;
            }

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = config.json ? JSON.stringify(value) : String(value);

            return (document.cookie = [
                encodeURIComponent(key), '=', config.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // read
        var decode = config.raw ? raw : decoded;
        var cookies = document.cookie.split('; ');
        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            if (decode(parts.shift()) === key) {
                var cookie = decode(parts.join('='));
                return config.json ? JSON.parse(cookie) : cookie;
            }
        }

        return null;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        if ($.cookie(key) !== null) {
            $.cookie(key, null, options);
            return true;
        }
        return false;
    };

})(jQuery, document);

(function() {

    var
    
    config = {
        panelWidth:215,
        afterDate:null,
        itemsPerPage:25,
        displayNsfw: $.cookie('nsfw') || false
    },
    
    templates = {
        thumbs:RB.Templates.galleryThumbs,
        images:RB.Templates.galleryImages,
        more:RB.Templates.moreButton,
        subChecks:RB.Templates.subCheckbox,
        imageSearchOriginal:RB.Templates.imageSearchOriginal,
        imageSearchList:RB.Templates.imageSearchList,
        uploadImageItem:RB.Templates.uploadImageItem,
        postTitle:RB.Templates.postTitle,
        sourceMatch:RB.Templates.sourceMatch
    },
    
    parseQueryString = function(qs) {
    
        var
            retVal = {},
            i = null,
            count = 0,
            kvp = null;

        if (!qs) {
            qs = location.href.indexOf('?') !== -1 ? location.href.split('?')[1] : null;
        }
        
        if (qs) {
            qs = qs.split('&');
            for (i = 0, count = qs.length; i < count; i++) {
                kvp = qs[i].split('=');
                retVal[kvp[0]] = kvp.length === 1 ? true : kvp[1];
            }
        }

        return retVal;
    
    },
    
    $images = $('#images'),
    $searchForm = $('#searchForm'),
    $overlay = $('#overlay'),
    $window = $(window),
    
    makeRelativeTime = function(seconds) {
    
        var
            value = seconds,
            label = 'second';
            
        // Years
        if (seconds > 31536000) { 
            value = Math.round(seconds / 31536000);
            label = 'year';
        // Months (30-days)
        } else if (seconds > 2592000) { 
            value = Math.round(seconds  / 2592000);
            label = 'month';
        // Days
        } else if (seconds > 86400) {
            value = Math.round(seconds / 86400);
            label = 'day';
        // Hours
        } else if (seconds > 3600) {
            value = Math.round(seconds / 3600);
            label = 'hour';
        // Minutes
        } else {
            value = Math.round(seconds / 60);
            label = 'minute';
        }
    
        return value + ' ' + label + (value != 1 ? 's' : '');
    
    },
    
    windowResize = function(e) {
        var
            width = $window.width(),
            cols = Math.floor(width / config.panelWidth);
        
        $images.width(cols * config.panelWidth);
    },

    getSources = function() {
        var retVal = $.cookie('sources');
        if (retVal) {
            retVal = retVal.split(',');
            for (var i = 0, count = retVal.length; i < count; i++) {
                retVal[i] = parseInt(retVal[i]);
            }
        } else {
            retVal = [ 1 ];
        }
        return retVal;
    },
    
    moreClick = function() {
        $.ajax({
            url:nextUrl + '&afterDate=' + config.afterDate,
            dataType:'json',
            success:ajaxCallback
        });
    },
    
    displayImages = function(data) {
        
        var
            out = [],
            template = templates[display];

        $images.find('.more').remove();

        if (window.display === 'thumbs') {
            out.push(template(data));
            config.afterDate = data[data.length - 1].dateCreated;
        } else {
            for (var i = 0, count = data.length; i < count; i++) {
                out.push(template(data[i]));
            }
            config.afterDate = data[data.length - 1].dateCreated;
        }
        
        if (data.length >= config.itemsPerPage) {
            out.push(templates.more({}));
        }
        
        $images.append(out.join(''));
    },
    
    searchForm = {
    
        display:function(e) {
            $overlay.find('.form').hide();
            $overlay.find('#searchForm').show();
            $overlay.fadeIn();
        },
        
        hide:function(e) {
            if (e.target === e.currentTarget) {
                $overlay.fadeOut();
            }
        },
        
        typeCheck:function(e) {
            var name = e.currentTarget.getAttribute('value');
            switch (name) {
                case 'all':
                    $overlay.find('#sources input[type="checkbox"]').prop('checked', true);
                    break;
                case 'source':
                    break;
                default:
                    $overlay.find('#sources input[name="rdoType"]').prop('checked', false);
                    break;
            }
        },
        
        keyPress:function(e) {
            var key = e.keyCode || e.charCode;
            if (key === 13) {
                searchForm.submit(e);
            }
        },
        
        submit:function(e, forcedKeywords) {
            
            var
                query = '/images/?dummy',
                sources = $('[name="chkSources"]:checked'),
                sizes = $('[name="chkSizes"]:checked'),
                keywords = forcedKeywords || $('#txtKeywords').val(),
                upload = $('#uplImage').val(),
                temp = [],
                sourceSearch = $('#rdoSource:checked').length > 0,
                nsfw = $('[name="chkNsfw"]:checked').length > 0;

            config.displayNsfw = nsfw;
            $.cookie('nsfw', config.displayNsfw, { expires: 365 });

            // We'll tack on the sources here because they're used in multiple search types
            if (sourceSearch) {
                sources = 'source';
                query += '&sources=source';
            } else {
                if (sources.length) {
                    temp = [];
                    sources.each(function() {
                        temp.push($(this).val());
                    });
                    
                    if (temp[0] !== 'all') {
                        query += '&sources=' + temp.join(',');
                    } else {
                        // If all was checked, adjust the cookie to view all by default
                        temp = [];
                        $('[name="chkSources"][value!="all"]').each(function() {
                            temp.push($(this).val());
                        });
                    }
                    sources = temp.join(',');
                    
                    // Only cookie the sources if the user actually filled the form themselves
                    if (e) {
                        $.cookie('sources', sources, { expires:365 });
                    }
                } else if ($('[name="sourceId"]').length > 0) {
                    sources = $('[name="sourceId"]').val();
                    query += '&sources=' + sources;
                }
            }
            
            // If keywords isn't a url and no upload, do a standard search
            if (keywords.indexOf('http://') === -1 && upload.length === 0 && !sourceSearch) {
            
                if (keywords) {
                    query += '&keywords=' + encodeURIComponent(keywords);
                }
                
                if (sizes.length) {
                    temp = [];
                    sizes.each(function() {
                        temp.push($(this).val());
                    });
                    if (temp[0] !== 'all') {
                        query += '&ratios=' + temp.join(',');
                    }
                }
                
                $images.empty();
                $overlay.fadeOut();
                
                $.ajax({
                    url:query,
                    dataType:'json',
                    success:ajaxCallback
                });
                
                window.nextUrl = query;
                
            } else {
            
                if (upload.length === 0) {
                
                    query = query.replace('searchPosts', 'reverseImageSearch');
                    query += '&imageUri=' + encodeURIComponent(keywords) + '&count=6&getCount=true';
                    $.ajax({
                        url:query,
                        dataType:'json',
                        success:imageSearchCallback
                    });
                
                } else {
                    $('#hdnSources').val(temp.join(','));
                    $('#uploadForm').submit();
                }
            
            }
            
            window.location.hash = '#!q=' + keywords + '&source=' + sources;
            
        },
        
        init:function() {
            $('#btnSubmit').on('click', searchForm.submit);
            $('#searchButton').on('click', searchForm.display);
            $('#txtKeywords').on('keypress', searchForm.keyPress);
            $overlay.on('click', searchForm.hide);
            if ($('#sources').length > 0) {
                var
                    prefSources = ($.cookie('sources') || '1').split(','),
                    sourceChecks = templates.subChecks(sources);
                $('#sources').html(sourceChecks);
                for (var i = 0, count = prefSources.length; i < count; i++) {
                    $('#chkSource' + prefSources[i]).prop('checked', true);
                }
            }
            $overlay.find('#sources input').on('change', searchForm.typeCheck);
            $('#chkNsfw').prop('checked', config.displayNsfw);
        }
        
    },
    
    uploadForm = {
        
        $:{
            form:$overlay.find('#uploadImagesForm'),
            list:$overlay.find('#uploadImagesForm ul'),
            txtTitle:$overlay.find('#txtTitle'),
            txtUrl:$overlay.find('#txtUrl'),
            uploadForm:$overlay.find('#uploadPictureForm'),
            hdnUploadId:$overlay.find('#hdnUploadId')
        },
        
        imageFirst:null,
        images:[],
        
        display:function() {
            $overlay.find('.form').hide();
            uploadForm.$.form.show();
            $overlay.fadeIn();
        },
        
        albumCreateCallback:function(data) {
            if (data.success) {
                window.location.href = '/gallery/' + data.post.id;
            }
        },
        
        addImageClick:function(e) {
            var uploadId = (new Date()).getTime();
            if (uploadForm.$.txtUrl.val().indexOf('http://') !== 0) {
                uploadForm.$.hdnUploadId.val(uploadId);
                $('#uploadPictureForm').submit();
            } else {
                $.ajax({
                    url:'upload.php',
                    type:'POST',
                    dataType:'json',
                    data:{ url:uploadForm.$.txtUrl.val(), hdnUploadId:uploadId },
                    success:uploadForm.uploadImageCallback
                });
                uploadForm.$.txtUrl.val('').focus();
            }
            uploadForm.$.list.append(templates.uploadImageItem({ uploadId:uploadId }));
            
        },
        
        doneClick:function(e) {
            
            if (uploadForm.images.length > 0) {
                
                if (uploadForm.images.length === 1) {
                    window.location.href = uploadForm.imageFirst;
                }
                
                var data = { title:$.trim(uploadForm.$.txtTitle.val()), images:uploadForm.images.join(',') };
                
                // Single images don't require a title
                if (data.title.length > 0) {
                    $.ajax({
                        url:'create.php',
                        type:'POST',
                        dataType:'json',
                        data:data,
                        success:uploadForm.albumCreateCallback
                    });
                }
                
            }
            
        },
        
        uploadImageCallback:function(data) {
            
            if (data.success) {
                var $item = uploadForm.$.form.find('[data-id="' + data.uploadId + '"]');
                $item
                    .removeClass('uploading')
                    .css({ background:'url(/thumb.php?file=' + escape(data.image.cdnUrl) + '&width=180&height=180)' })
                    .append('<img src="/view/reddit-booru/images/upload_okay.png" alt="upload complete" />');
                uploadForm.images.push(data.image.id);
                switch (uploadForm.images.length) {
                    case 1:
                        uploadForm.imageFirst = data.image.cdnUrl;
                        break;
                    case 2:
                        uploadForm.$.form.find('.title').fadeIn();
                        break;
                }
            }
            
        },
        
        init:function() {
            $('#uploadButton').on('click', uploadForm.display);
            $('#btnAddImage').on('click', uploadForm.addImageClick);
            $('#btnCreate').on('click', uploadForm.doneClick);
            window.uploadImageCallback = uploadForm.uploadImageCallback;
        }
    
    },

    screensaver = (function() {
        
        var
            
            delay = 7000,
            $imgContainer = $overlay.find('.ss-image'),
            $controls = $overlay.find('.ss-controls'),
            $pause = $controls.find('button'),
            images = [],
            imageTimer = null,
            controlsTimer = null,
            controlsVisible = true,
            currentImage = 0,
            img = null,
            
            loadNext = function() {
                img = new Image();
                img.onload = imageLoaded;
                img.onerror = loadNext;
                img.src = images[currentImage];
                currentImage = currentImage >= images.length ? 0 : currentImage + 1;
            },
            
            imageLoaded = function() {
                $imgContainer.find('img').fadeOut('slow', function() { $(this).remove(); });
                this.className = 'new';
                $imgContainer.append(this);
                var $new = $imgContainer.find('.new');
                
                $new
                    .css({ 
                        left:(($window.width() - $new.width()) / 2) + 'px',
                        top:(($window.height() - $new.outerHeight()) / 2) + 'px'
                    })
                    .fadeIn('slow', function() { $(this).removeClass('new'); });
                    
                imageTimer = setTimeout(loadNext, delay);
            },
            
            playPauseClick = function(e) {
            
                if (null !== imageTimer) {
                    clearTimeout(imageTimer);
                    imageTimer = null;
                } else {
                    imageTimer = setTimeout(loadNext, delay);
                }
                $pause.toggleClass('pause').toggleClass('play');
                e.stopPropagation();
            
            },
            
            mouseMove = function(e) {
                if (!controlsVisible) {
                    clearTimeout(controlsTimer);
                    controlsVisible = true;
                    $controls.stop().fadeTo('slow', 1, function() {
                        controlsTimer = setTimeout(controlsTimeout, 4000);
                    });
                }
            },
            
            controlsTimeout = function() {
                controlsVisible = false;
                $controls.fadeOut();
            },
            
            launch = function() {
                $overlay.addClass('screenSaver').fadeIn();
                $overlay.find('.form').hide();
                $overlay.find('#screenSaverArea').fadeIn();
                
                // Get the images on the page to use as the source of the screen saver
                images = [];
                currentImage = 0;
                $('.image').each(function() {
                    images.push(this.getAttribute('data-full'));
                });
                
                loadNext();
                $overlay.on('mousemove', mouseMove);
                controlsTimer = setTimeout(controlsTimeout, 4000);
            },
            
            close = function() {
                img = null;
                $imgContainer.empty();
                clearTimeout(imageTimer);
                clearTimeout(controlsTimer);
                $overlay.removeClass('screenSaver');
                $overlay.off('mousemove', mouseMove);
                $controls.hide();
            },
            
            init = function() {
                $('#screenButton').on('click', launch);
                $overlay.on('click', close);
                $pause.on('click', playPauseClick);
            };
            
            return { init:init };
    
    }()),
    
    /**
     * Determine whether or not an image is NSFW based upon the user's saved preferences
     */
    determineNsfw = function(post) {
        var retVal = post.nsfw,
            sources = getSources();
        // return retVal ? sources.indexOf(post.sourceId) === -1 : retVal;
        return post.nsfw && !config.displayNsfw ? 'nsfw' : '';
    },

    imageSearchCallback = window.imageSearchCallback = function(data) {
    
        var out = '', match = {};
        
        $overlay.fadeOut();
        
        if (data && data.length > 0) {
            out = templates.imageSearchOriginal($('#txtKeywords').val());
            for (var i = 0, count = data.length; i < count; i++) {
                data[i].age = makeRelativeTime(data[i].age);
                data[i].showCount = data[i].idxInAlbum > 1;
            }
            
            out += templates.imageSearchList(data);
            $images.html(out);
        }
    
    },
    
    displayDefault = function() {
        if (startUp) {
            if (postTitle) {
                $images.html(templates.postTitle(postTitle));
            }
            displayImages(startUp);
        } else {
            $.ajax({
                url:'/api/?type=json&method=image.getImagesBySource&sources=' + window.sources + '&deep=true',
                dataType:'jsonp',
                success:ajaxCallback
            });
        }
    },
    
    ajaxCallback = function(data) {
        if (typeof data == 'object' && data.hasOwnProperty('body') && data.body) {
            displayImages(data.body);
        } else if (data instanceof Array) {
            displayImages(data);
        }
    },
    
    init = (function() {
        


        $(window).on('resize', windowResize);
        $(function() {
            windowResize();
        });
        
        $images
            .on('click', '.more', moreClick)
            .addClass(display);

        // Initialize sub modules
        searchForm.init();
        uploadForm.init();
        screensaver.init();
        
        // Template helpers
        Handlebars.registerHelper('determineNsfw', determineNsfw);

        // Check for a search on the query string (genuine query string or hashbang)
        var queryString = window.location.href.split('?');
        queryString = queryString.length === 1 ? window.location.hash.split('#!') : queryString;
        
        if (queryString.length === 1) {        
            displayDefault();
        } else {
            queryString = parseQueryString(queryString[1]);
            
            if (queryString.hasOwnProperty('q')) {
                $('#txtKeywords').val(queryString.q);
                if (queryString.hasOwnProperty('source')) {
                    switch (queryString.source) {
                        case 'all':
                            $('#rdoAll').prop('checked', true);
                            break;
                        case 'source':
                            $('#rdoSource').prop('checked', true);
                            break;
                        default:
                            var sources = queryString.source.split(',');
                            $('input[name="chkSources"]').prop('checked', false);
                            for (var i = 0, count = sources.length; i < count; i++) {
                                $('#chkSource' + sources[i]).prop('checked', true);
                            }
                            break;
                    }
                }
                
                searchForm.submit(null, decodeURIComponent(queryString.q));
            } else if (queryString.hasOwnProperty('dialog')) {
                
                switch (queryString.dialog) {
                    case 'search':
                        searchForm.display();
                        break;
                }
                
                displayDefault();
                
            } else {
                displayDefault();
            }
            
        }

    }());

}());