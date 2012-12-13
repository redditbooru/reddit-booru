<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
		<title>redditbooru - a place where cute girls come to meet</title>
		<link rel="stylesheet" type="text/css" href="/view/reddit-booru/styles.css" />
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script type="text/javascript" src="/view/reddit-booru/mustache.min.js"></script>
	</head>
	<body>
		<h1>
			<a href="/">
				<img src="/view/reddit-booru/images/logo.png" alt="RedditBooru" />
			</a>
		</h1>
		<div id="searchButton" class="searchButton">Search</div>
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
						<input type="file" id="uplImage" />
					</div>
				</div>
				<div class="field">
					<label>Filter By Subreddit</label>
					<ul class="checkboxList" id="sources">
						
					</ul>
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
			</div>
		</div>
		<script type="text/javascript">
			window.startUp = {START_UP};
			window.nextUrl = '{NEXT_URL}';
			window.sources = {SOURCES};
		</script>
		<script type="text/template" id="tplGalleryThumbs">
			{{#images}}
				<div class="image" data-id="{{postId}}" data-source="{{sourceId}}">
					<a href="{{source.baseUrl}}/comments/{{externalId}}/">
						<img src="/thumb.php?file={{cdnUrl}}&height=180&width=180" />
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
		<script type="text/template" id="tplMoreButton">
			<div class="image more">More</a>
		</script>
		<script type="text/javascript" src="/view/reddit-booru/scripts.js"></script>
	</body>
</html>