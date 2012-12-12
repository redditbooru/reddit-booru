<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
		<title>redditbooru - a place where cute girls come to meet</title>
		<link rel="stylesheet" type="text/css" href="view/reddit-booru/styles.css" />
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
	</head>
	<body>
		<h1>RedditBooru</h1>
		<div id="searchButton">Search</div>
		<div id="searchForm">
			<ul class="searchOptions">
				<li class="label">Search By:</li>
				<li class="searchOption" data-form="keyword">Keyword</li>
				<li class="searchOption" data-form="url">URL</li>
				<li class="searchOption" data-form="upload">Upload</li>
				<li class="label">Subreddits:</li>
				<li class="searchOption">
					<input type="checkbox" value="1" id="chkSubreddit1" data-filter="subreddit" /><label for="chkSubreddit1">r/awwnime</label>
				</li>
				<li class="searchOption">
					<input type="checkbox" value="2" id="chkSubreddit2" data-filter="subreddit" /><label for="chkSubreddit2">r/kitsunemimi</label>
				</li>
				<li class="searchOption">
					<input type="checkbox" value="3" id="chkSubreddit3" data-filter="subreddit" /><label for="chkSubreddit3">r/melanime</label>
				</li>
				<li class="searchOption">
					<input type="checkbox" value="4" id="chkSubreddit4" data-filter="subreddit" /><label for="chkSubreddit4">r/pantsu</label>
				</li>
				<li class="label">Sizes:</li>
				<li class="searchOption">
					<input type="checkbox" value="all" id="chkSizeAll" data-filter="size" /><label for="chkSizeAll">All</label>
				</li>
				<li class="searchOption">
					<input type="checkbox" value="1.333" id="chkSize43" data-filter="size" /><label for="chkSize43">4x3</label>
				</li>
				<li class="searchOption">
					<input type="checkbox" value="1.778" id="chkSize169" data-filter="size" /><label for="chkSize169">16x9</label>
				</li>
				<li class="searchOption">
					<input type="checkbox" value="1.6" id="chkSize1610" data-filter="size" /><label for="chkSize1610">16x10</label>
				</li>
			</ul>
			<div class="searchForms">
				<div class="searchForm" data-form="keyword">
					<input type="text" id="txtKeyword" />
					<label for="txtKeyword">Enter search keywords</label>
					<button id="btnKeyword">Search</button>
				</div>
				<div class="searchForm" data-form="url">
					<input type="text" id="txtUrl" />
					<label for="txtUrl">Enter image URL</label>
					<button id="btnUrl">Search</button>
				</div>
				<div class="searchForm" data-form="upload">
					<input type="file" id="filUpload" />
					<button id="btnUpload">Search</button>
				</div>
			</div>
		</div>
		<div id="images">
			{CONTENT}
		</div>
	</body>
</html>