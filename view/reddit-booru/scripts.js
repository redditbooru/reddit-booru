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
		afterDate:null
	},
	
	templates = {
		images:$('#tplGalleryThumbs').html(),
		more:$('#tplMoreButton').html(),
		subChecks:$('#tplSubCheckbox').html()
	},
	
	$images = $('#images'),
	$searchForm = $('#searchForm'),
	$overlay = $('#overlay'),
	
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
		var out = [];
		
		$images.find('.more').remove();
		for (var i = 0, count = data.length; i < count; i++) {
			out.push(Mustache.to_html(templates.images, data[i]));
		}
		out.push(Mustache.to_html(templates.more));
		config.afterId = data[data.length - 1].dateCreated;
		
		$images.append(out.join(''));
	},
	
	searchForm = {
	
		display:function(e) {
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
		
		submit:function(e) {
			
			var
				query = '/api/?type=json&method=post.searchPosts&getImages=true&getSource=true',
				sources = $('[name="chkSources"]:checked'),
				sizes = $('[name="chkSizes"]:checked'),
				keywords = $('#txtKeywords').val(),
				upload = $('#uplImage').val(),
				temp = [];
			
			// If keywords isn't a url and no upload, do a standard search
			if (keywords.indexOf('http://') === -1 && upload.length === 0) {
			
				if (keywords) {
					query += '&keywords=' + escape(keywords);
				}
				
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
				
			}
			
		},
		
		init:function() {
			var sourceChecks = Mustache.to_html(templates.subChecks, [{ id:'all', name:'All' }]) +  Mustache.to_html(templates.subChecks, sources);
			$('#btnSubmit').on('click', searchForm.submit);
			$('#searchButton').on('click', searchForm.display);
			$('#sources').html(sourceChecks);
			$overlay.on('click', searchForm.hide);
			var prefSources = ($.cookie('sources') || '1').split(',');
			for (var i = 0, count = prefSources.length; i < count; i++) {
				$('#chkSource' + prefSources[i]).attr('checked', true);
			}
			$overlay.find('input[value="all"]').on('click', searchForm.allCheck);
		}
		
	}
	
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
		
		if (startUp) {
			displayImages(startUp);
		} else {
			$.ajax({
				url:'http://beta.redditbooru.com/api/?type=json&method=image.getImagesBySource&sources=8&deep=true',
				dataType:'jsonp',
				success:ajaxCallback
			});
		}
		
		$images.on('click', '.more', moreClick);
		searchForm.init();
	
	}());

}());