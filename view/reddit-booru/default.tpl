<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
		<title>redditbooru - a place where cute girls come to meet</title>
		<link rel="stylesheet" type="text/css" href="/view/reddit-booru/styles.css?2013-03-08" />
        <link rel="stylesheet" type="text/css" href="/view/reddit-booru/sources/{SOURCE_NAME}/styles.css?2013-04-28" />
	</head>
	<body>
		<header class="rb-header">
			<h1>
				<a href="/">
					<img src="/view/reddit-booru/sources/{SOURCE_NAME}/images/logo.png" alt="RedditBooru" />
				</a>
			</h1>
			<button id="screenButton" class="rb-button button screenButton" title="Screen Saver Mode">Screen Saver</button>
			<button id="uploadButton" class="rb-button uploadButton" title="Upload Images">Upload</button>
			<button id="searchButton" class="rb-button searchButton" title="Search">Search</button>
		</header>
		<div id="images">
            <img src="{THUMB}" class="redditThumb" />
		</div>
		<div id="overlay">
			<div id="searchForm" class="form">
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
							<input type="hidden" id="hdnSources" name="sources" />
						</form>
						<iframe id="ifUpload" name="ifUpload"></iframe>
					</div>
				</div>
				<fieldset class="field" id="sources"></fieldset>
				<fieldset class="field">
					<legend>Filter By Size</legend>
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
				</fieldset>
                <div class="buttons">
                    <button id="btnSubmit" class="searchButton">Search</button>
                    <p class="tip"><strong>PRO TIP</strong> - Leave the keywords box blank to see the latest images from the selected subreddits. Your selections will be remembered!</p>
                </div>
			</div>
            
            <div id="uploadImagesForm" class="form">
                <h2>Upload</h2>
                <div class="field title">
                    <label for="txtTitle">Title</label>
                    <input type="text" name="txtTitle" id="txtTitle" />
                </div>
                <div class="field">
                    <div class="subField">
                        <label for="txtUrl">Upload from URL</label>
                        <input type="text" name="txtUrl" id="txtUrl" />
                    </div>
                    <div class="subField">
                        <label for="uplPicture">Upload from Computer</label>
                        <form action="/upload.php" method="post" target="ifUpload" enctype="multipart/form-data" id="uploadPictureForm">
                            <input type="file" name="uplPicture" id="uplPicture" />
                            <input type="hidden" name="hdnUploadId" id="hdnUploadId" />
                        </form>
                    </div>
                </div>
                <ul></ul>
                <div class="buttons">
                    <button id="btnAddImage">Add Image</button>
                    <button id="btnCreate">Done</button>
                </div>
            </div>
            
            <div id="screenSaverArea">
                <div class="ss-image"></div>
                <div class="ss-controls">
                    <button class="ss-control pause">Play/Pause</button>
                </div>
            </div>
            
		</div>
        
		<script type="text/javascript">
			window.display = '{DISPLAY}';
			window.postTitle = '{POST_TITLE}';
            window.startUp = {START_UP};
			window.nextUrl = '{NEXT_URL}';
			window.sources = {SOURCES};
		</script>
        
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script type="text/javascript" src="/view/reddit-booru/handlebars.runtime.js"></script>
		<script type="text/javascript" src="/view/reddit-booru/templates.js?2013-03-11"></script>
		<script type="text/javascript" src="/view/reddit-booru/scripts.js?2013-04-28"></script>
        
		<script type="text/javascript">
			var _gaq=_gaq||[];_gaq.push(["_setAccount","UA-280226-8"]);_gaq.push(["_trackPageview"]);(function(){var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src=("https:"==document.location.protocol?"https://ssl":"http://www")+".google-analytics.com/ga.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})();
		</script>
	</body>
</html>