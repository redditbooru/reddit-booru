<?php

define('CORE_LOCATION', '/var/www/reddit-booru');

define('DEFAULT_TITLE', '');
define('DEFAULT_CONTROLLER', 'landing');

define('REDDIT_TOKEN', 'REDDIT_TOKEN');
define('REDDIT_SECRET', 'REDDIT_SECRET');
define('REDDIT_OAUTH_HANDLER', 'http://OAUTH_HANDLE');
define('REDDIT_USER', 'USER');

define('TUMBLR_CONSUMER_KEY', 'TUMBLR_CONSUMER_KEY');

define('SESSION_DOMAIN', '.redditbooru.com');

define('CDN_BASE_URL', 'http://CDN_URL');
define('LOCAL_IMAGE_PATH', '/LOCAL/IMAGES');
define('THUMBNAIL_PATH', '/cache/');
define('THUMBNAIL_STORAGE', '/FULL/PATH/TO/THUMBNAILS');

define('CACHE_PREFIX', 'RedditBooru');

define('USE_MIN_JS', true);
define('JS_VERSION', 1);

// Set the time zone
date_default_timezone_set('America/Chicago');