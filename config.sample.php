<?php

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'USER');
define('DB_PASS', 'PASSWORD');
define('DB_NAME', 'reddit-booru');

define('AWS_KEY', 'KEY');
define('AWS_SECRET', 'SECRET');
define('AWS_ENABLED', true);
define('AWS_BUCKET', 'BUCKET');
define('AWS_PATH', 'PATH/');

// Call me pessimistic, but I don't expect awwnime nor reddit to exist in 20 years
define('AWS_EXPIRATION', 630720000);

// View directory
define('VIEW_PATH', './view/');

define('HTTP_UA', 'moe downloader by /u/dxprog');

define('SAUCENAO_PORT', 'SAUCENAO_SERVICE_PORT');

define('RB_BOT', 'ai-tan');

define('REDIS_SERVER', 'tcp://127.0.0.1:6379');