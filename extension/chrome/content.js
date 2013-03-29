(function($, undefined) {

    var
        
        gallery = $.fn.fbGallery = function(options) {
            
            return this.each(function() {
            
                var
                    
                    $parent = $(this).parents('.entry:first'),
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
                
                $('<a class="toggleImage expando-button collapsed image gallery linkImg rb-display">&nbsp;</a>').insertBefore($parent.find('p.tagline'))
                $parent.find('.rb-display').on('click', galleryDisplayClick);
                
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
        
        init = (function() {
            $('a.title[href*="redditbooru.com/gallery/"]').each(function() {
                
                var
                    href = this.getAttribute('href'),
                    galleryId = /\/gallery\/([\d]+)/.exec(href),
                    $entry = $(this).parents('.entry');
                
                if (galleryId) {
                    $(this).fbGallery({ postId:galleryId[1] });
                }
                
            });
            
        }());

}(jQuery));