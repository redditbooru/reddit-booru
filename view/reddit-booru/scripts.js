(function() {

	var
	
	config = {
		panelWidth:215,
		afterDate:null
	},
	
	templates = {
		images:$('#tplGalleryThumbs').html(),
		more:$('#tplMoreButton').html()
	},
	
	$images = $('#images'),
	
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
	
	ajaxCallback = function(data) {
		if (typeof data == 'object' && data.hasOwnProperty('body')) {
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
	
	}());

}());