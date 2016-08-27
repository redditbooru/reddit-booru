<?php

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'USER');
define('DB_PASS', 'PASSWORD');
define('DB_NAME', 'reddit-booru');

// Global AWS S3 enabler
define('AWS_ENABLED', true);

// If S3 is turned on, information for connecting
define('AWS_KEY', 'KEY');
define('AWS_SECRET', 'SECRET');
define('AWS_BUCKET', 'BUCKET');
define('AWS_PATH', 'PATH/');

// Call me pessimistic, but I don't expect awwnime nor reddit to exist in 20 years
define('AWS_EXPIRATION', 630720000);

// View directory
define('VIEW_PATH', './view/');

// API key for fetching images from tumblr
define('TUMBLR_CONSUMER_KEY', 'TUMBLR_CONSUMER_KEY');

// Custom user-agent when making outband calls to services. Reddit particularly likes custom UAs
define('HTTP_UA', 'moe downloader by /u/dxprog');

// Port that the SauceNao service runs on
define('SAUCENAO_PORT', 'SAUCENAO_SERVICE_PORT');

// Client-ID for imgur API calls
define('IMGUR_CLIENT_ID', 'REGISTERED_APP_ID');

// Reddit username of the bot (if enabled)
define('RB_BOT', 'ai-tan');