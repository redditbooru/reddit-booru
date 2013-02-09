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
        itemsPerPage:25
	},
	
	templates = {
		thumbs:$('#tplGalleryThumbs').html(),
		images:$('#tplGalleryImages').html(),
		more:$('#tplMoreButton').html(),
		subChecks:$('#tplSubCheckbox').html(),
		imageSearchOriginal:$('#tplImageSearchOriginal').html(),
		imageSearchList:$('#tplImageSearchList').html(),
        uploadImageItem:$('#tplUploadImageItem').html(),
        postTitle:$('#tplPostTitle').html()
	},
	
	$images = $('#images'),
	$searchForm = $('#searchForm'),
	$overlay = $('#overlay'),
	
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
			width = $(window).width(),
			cols = Math.floor(width / config.panelWidth);
		
		$images.width(cols * config.panelWidth);
	},
	
	moreClick = function() {
		$.ajax({
			url:nextUrl + '&afterDate=' + config.afterId,
			dataType:'json',
			success:ajaxCallback
		});
	},
	
	displayImages = function(data) {
        
        var
            out = [],
            template = templates[display];
		
		$images.find('.more').remove();
		for (var i = 0, count = data.length; i < count; i++) {
			out.push(Mustache.to_html(template, data[i]));
		}
        
        if (data.length >= config.itemsPerPage) {
            out.push(Mustache.to_html(templates.more));
        }
		config.afterId = data[data.length - 1].dateCreated;
		
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
		
		allCheck:function(e) {
			var name = e.currentTarget.getAttribute('name');
			$overlay.find('input[name="' + name + '"][value!="all"]').attr('checked', false);
		},
		
		keyPress:function(e) {
			var key = e.keyCode || e.charCode;
			if (key === 13) {
				searchForm.submit(e);
			}
		},
		
		submit:function(e, forcedKeywords) {
			
			var
				query = '/api/?type=json&method=post.searchPosts&getImages=true&getSource=true',
				sources = $('[name="chkSources"]:checked'),
				sizes = $('[name="chkSizes"]:checked'),
				keywords = forcedKeywords || $('#txtKeywords').val(),
				upload = $('#uplImage').val(),
				temp = [];
			
			// We'll tack on the sources here because they're used in multiple search types
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
				$.cookie('sources', temp.join(','), { expires:365 });
			} else if ($('[name="sourceId"]').length > 0) {
				query += '&sources=' + $('[name="sourceId"]').val();
			}
			
			// If keywords isn't a url and no upload, do a standard search
			if (keywords.indexOf('http://') === -1 && upload.length === 0) {
			
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
					query += '&imageUri=' + encodeURIComponent(keywords) + '&count=6';
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
            
            window.location.hash = '!#?q=' + keywords;
			
		},
		
		init:function() {
			$('#btnSubmit').on('click', searchForm.submit);
			$('#searchButton').on('click', searchForm.display);
			$('#txtKeywords').on('keypress', searchForm.keyPress);
			$overlay.on('click', searchForm.hide);
			if ($('#sources').length > 0) {
				var
					prefSources = ($.cookie('sources') || '1').split(','),
					sourceChecks = Mustache.to_html(templates.subChecks, [{ id:'all', name:'All' }]) +  Mustache.to_html(templates.subChecks, sources);
				$('#sources').html(sourceChecks);
				for (var i = 0, count = prefSources.length; i < count; i++) {
					$('#chkSource' + prefSources[i]).attr('checked', true);
				}
			}
			$overlay.find('input[value="all"]').on('click', searchForm.allCheck);
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
            uploadForm.$.list.append(Mustache.to_html(templates.uploadImageItem, { uploadId:uploadId }));
            
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
                        uploadForm.images.imageFirst = data.image.cdnUrl;
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
	
	imageSearchCallback = window.imageSearchCallback = function(data) {
	
		var out = '';
		
		$overlay.fadeOut();
		
		if (typeof data === 'object' && data.hasOwnProperty('body') && data.body.hasOwnProperty('results') && data.body.results.length > 0) {
			out = Mustache.to_html(templates.imageSearchOriginal, data.body);
			
			for (var i = 0, count = data.body.results.length; i < count; i++) {
				data.body.results[i].age = makeRelativeTime(data.body.results[i].age);
			}
			
			out += Mustache.to_html(templates.imageSearchList, data.body.results);
			$images.html(out);
		}
	
	},
	
	ajaxCallback = function(data) {
		if (typeof data == 'object' && data.hasOwnProperty('body') && data.body) {
			displayImages(data.body);
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
		searchForm.init();
        uploadForm.init();
        
        // Check for a search on the query string
        var queryString = window.location.href.split('#!?q=');
        if (queryString.length === 1) {        
            if (startUp) {
                if (postTitle) {
                    $images.html(Mustache.to_html(templates.postTitle, postTitle));
                }
                displayImages(startUp); 
            } else {
                $.ajax({
                    url:'http://beta.redditbooru.com/api/?type=json&method=image.getImagesBySource&sources=' + window.sources + '&deep=true',
                    dataType:'jsonp',
                    success:ajaxCallback
                });
            }
        } else {
            searchForm.submit(null, queryString[1]);
        }
	
	}());

}());