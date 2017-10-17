<?php

// This is where the "backend" of RedditBooru lives. A single backend can serve multiple frontends.
define('CORE_LOCATION', '/var/www/reddit-booru');

// Default page title
define('DEFAULT_TITLE', '');

// Reddit OAuth information. Register your application at https://www.reddit.com/prefs/apps
define('REDDIT_TOKEN', 'REDDIT_TOKEN');
define('REDDIT_SECRET', 'REDDIT_SECRET');
define('REDDIT_OAUTH_HANDLER', 'http://OAUTH_HANDLER');

// Cookie domain for sessions. If you're using subdomains, it's recommended to set for all sub domains
define('SESSION_DOMAIN', '.redditbooru.com');

// Full URL to where images are served from (with trailing slash)
// Ex: http://cdn.awwni.me/
define('CDN_BASE_URL', 'http://CDN_URL/');

// Full path to where images are stored locally (with trailing slash)
define('LOCAL_IMAGE_PATH', '/LOCAL/IMAGES/');

// Relative path to where thumbnail files are served from. Can be on the same domain or separate.
define('THUMBNAIL_PATH', '/cache/');

// Full path to where thumbnails will be written. This folder should be served
// where THUMBNAIL_PATH points.
define('THUMBNAIL_STORAGE', '/FULL/PATH/TO/THUMBNAILS');

// If using an rbthumbs thumbnail server, the full URL to that
define('THUMBNAIL_SERVER_URL', 'https://beta.thumb.awwni.me/');

// Used to namespace this frontend in caching operations
define('CACHE_PREFIX', 'RedditBooru');

// Use minified JavaScript. Recommended to be enabled for production, disabled for development
define('USE_MIN_JS', true);

// Cache buster for JavaScript payload. Just increment on change. TODO - make this work off of
// md5 hash of JS file instead
define('JS_VERSION', 1);

// The timezone that all timestamps will be generated from
date_default_timezone_set('America/Chicago');