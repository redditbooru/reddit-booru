(function($, undefined) {

    var
        
        lastPage = 1,
        
        gallery = $.fn.fbGallery = function(options) {
            
            return this.each(function() {
            
                var
                    
                    $this = $(this),
                    $parent = $this.parents('.entry:first'),
                    $gallery = null,
                
                    galleryImages = null,
                    galleryPosition = 0,
                    imageLoader = null,
                    
                    galleryHtml = '<div class="rb-gallery"><div class="rb-controls"><span class="rb-control rb-prev" data-action="prev">Previous</span><span class="rb-label"></span><span class="rb-control rb-next" data-action="next">Next</span></div><img class="rb-image" /></div>',
                    
                    imageLoaded = function() {
                        $gallery.find('.rb-image').attr('src', this.src);
                        $gallery.removeClass('loading');
                    },
                    
                    imagePreload = function(url) {
                        imageLoader = new Image();
                        imageLoader.onload = imageLoaded;
                        imageLoader.src = url;
                        $gallery.addClass('loading');
                    },
                    
                    galleryDisplayClick = function(e) {
                        $parent.find('.rb-expand').toggleClass('expanded');
                        $gallery.toggleClass('visible');
                    },
                    
                    galleryControlClick = function(e) {
                        switch (e.currentTarget.getAttribute('data-action')) {
                            case 'prev':
                                galleryPosition = galleryPosition > 0 ? galleryPosition - 1 : 0;
                                break;
                            case 'next':
                                galleryPosition = galleryPosition < galleryImages.length - 1 ? galleryPosition + 1 : 0;
                                break;
                        }
                        galleryDisplayItem(galleryPosition);
                        
                        if (galleryPosition === galleryImages.length - 1) {
                            $gallery.find('.rb-next').addClass('replay');
                        } else {
                            $gallery.find('.rb-next').removeClass('replay');
                        }
                        
                    },
                    
                    galleryDisplayItem = function(index) {
                        $gallery.find('.rb-label').html((index + 1) + ' of ' + galleryImages.length);
                        imagePreload(galleryImages[index].cdnUrl);
                    },
                    
                    galleryAjaxCallback = function(data) {
                        if (typeof data === 'object' && typeof data.body === 'object' && data.body.length === 1) {
                            galleryImages = data.body[0].images;
                            galleryDisplayItem(0);
                        }                        
                    };
                
                $('<a class="rb-expand">&nbsp;</a>').insertBefore($parent.find('p.tagline'))
                $parent.find('.rb-expand').on('click', galleryDisplayClick);
                
                $parent.append(galleryHtml);
                $gallery = $parent.find('.rb-gallery');
                $gallery.on('click', '.rb-control', galleryControlClick);
                
                $.ajax({
                    url:'http://redditbooru.com/api/?type=json&method=post.searchPosts&getImages=true&id=' + options.postId,
                    dataType:'json',
                    success:galleryAjaxCallback
                });
                    
            });
            
        },
        
        initExpando = function() {
        
            $('a.title[href*="redditbooru.com/gallery/"]').each(function() {
                if (this.className.indexOf('rb-enabled') === -1) {
                    var
                        href = this.getAttribute('href'),
                        galleryId = /\/gallery\/([\d]+)/.exec(href),
                        $entry = $(this).parents('.entry');
                    
                    if (galleryId) {
                        $(this).fbGallery({ postId:galleryId[1] }).addClass('rb-enabled');
                    }
                }
                
            });
            
        },
        
        initMirrorLinks = function() {
            
            $('.buttons a.comments[href*="r/awwnime"]').each(function() {
                if (!$(this).hasClass('rb-mirror')) {
                    var newHref = this.href.replace('reddit.com', 'redditbooru.com');
                    $(this).parents('.buttons:first').append('<li><a href="' + newHref + '" target="_blank">mirror</a></li>');
                }
            });
            
        },
        
        checkPage = function() {
        
            var 
                $pageMarker = $('.NERPageMarker:last'),
                currentPage = null;
            
            if ($pageMarker.length > 0) {
                currentPage = $.trim($pageMarker.text().replace('Page ', ''));
                if (currentPage !== lastPage) {
                    initExpando();
                    initMirrorLinks();
                }
                lastPage = currentPage;
            }
            
            setTimeout(checkPage, 500);
        
        },
        
        init = (function() {

            initExpando();
            initMirrorLinks();
            
            // If never ending reddit is enabled, we need to check for links periodically
            if ($('.neverEndingReddit').length > 0) {
                setTimeout(checkPage, 500);
            }
            
        }());

}(jQuery));