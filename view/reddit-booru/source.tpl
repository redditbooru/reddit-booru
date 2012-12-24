<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
		<title>redditbooru - a place where cute girls come to meet</title>
		<link rel="stylesheet" type="text/css" href="/view/reddit-booru/sources/{SOURCE_NAME}/styles.css" />
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script type="text/javascript" src="/view/reddit-booru/mustache.min.js"></script>
	</head>
	<body>
		<header>
			<h1>
				<a href="/">
					<img src="/view/reddit-booru/sources/{SOURCE_NAME}/images/logo.png" alt="RedditBooru" />
				</a>
			</h1>
			<div id="searchButton" class="searchButton">Search</div>
		</header>
		<div id="images">
		
		</div>
		<div id="overlay">
			<div id="searchForm">
				<h2>Search</h2>
				<div class="field">
					<div class="subField">
						<label for="txtKeywords">By Keyword Or URL</label>
						<input type="text" id="txtKeywords" />
					</div>
					<div class="subField">
						<label for="uplImage">By Upload</label>
						<form action="/match.php?flushCache" method="post" target="ifUpload" enctype="multipart/form-data" id="uploadForm">
							<input type="file" id="uplImage" name="uplImage" />
							<input type="hidden" id="hdnSources" />
						</form>
						<iframe id="ifUpload" name="ifUpload"></iframe>
					</div>
				</div>
				<div class="field">
					<label>Filter By Size</label>
					<ul class="checkboxList">
						<li>
							<input type="checkbox" id="chkAll" name="chkSizes" value="all" checked="checked" />
							<label for="chkAll">All</label>
						</li>
						<li>
							<input type="checkbox" id="chkSD" name="chkSizes" value="1.33" />
							<label for="chkSD">4x3</label>
						</li>
						<li>
							<input type="checkbox" id="chkWidescreen" name="chkSizes" value="1.78,1.6" />
							<label for="chkWidescreen">Widescreen</label>
						</li>
						<li>
							<input type="checkbox" id="chkPhone" name="chkSizes" value="1.5,0.6,0.56" />
							<label for="chkPhone">Smart Phone</label>
						</li>
					</ul>
				</div>
				<div id="btnSubmit" class="searchButton">Search</div>
				<p class="tip"><strong>PRO TIP</strong> - Leave the keywords box blank to see the latest images from the selected subreddits. Your selections will be remembered!</p>
				<input type="hidden" value="{SOURCE_ID}" name="sourceId" />
			</div>
		</div>
		<script type="text/javascript">
			window.startUp = {START_UP};
			window.nextUrl = '{NEXT_URL}';
		</script>
		<script type="text/template" id="tplGalleryThumbs">
			{{#images}}
				<div class="image" data-id="{{postId}}" data-source="{{sourceId}}" data-full="{{cdnUrl}}">
					<a href="{{source.baseUrl}}/comments/{{externalId}}/" target="_blank">
						<img src="/thumb.php?file={{cdnUrl}}&height=180&width=180" alt="{{title}} [Score: {{score}}]" title="{{title}} [Score: {{score}}]" />
					</a>
				</div>
			{{/images}}
		</script>
		<script type="text/template" id="tplSubCheckbox">
			{{#.}}
				<li>
					<input type="checkbox" id="chkSource{{id}}" name="chkSources" value="{{id}}" />
					<label for="chkSource{{id}}">{{name}}</label>
				</li>
			{{/.}}
		</script>
		<script type="text/template" id="tplImageSearchOriginal">
			<div class="imageSearchOriginal">
				<h2>Original Image</h2>
				<img src="{{original}}" />
			</div>
		</script>
		<script type="text/template" id="tplImageSearchList">
			<div class="imageSearchList">
				<h2>Similar Images</h2>
				{{#.}}
					<div class="imageSearchResult" data-id="{{postId}}" data-source="{{sourceId}}">
						<a href="{{source.baseUrl}}/comments/{{externalId}}" target="_blank">
							<img src="/thumb.php?file={{image.cdnUrl}}&height=180&&width=360" alt="{{title}} [Score: {{score}}]" title="{{title}} [Score: {{score}}]" />
						</a>
						<span class="source">{{source.name}}</span>
						<span class="age">{{age}} ago</span>
					</div>
				{{/.}}
			</div>
		</script>
		<script type="text/template" id="tplMoreButton">
			<div class="image more">More</div>
		</script>
		<script type="text/javascript" src="/view/reddit-booru/scripts.js?20121215"></script>
		<script type="text/javascript">
			var _gaq=_gaq||[];_gaq.push(["_setAccount","UA-280226-8"]);_gaq.push(["_trackPageview"]);(function(){var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src=("https:"==document.location.protocol?"https://ssl":"http://www")+".google-analytics.com/ga.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})();
		</script>
	</body>
</html>