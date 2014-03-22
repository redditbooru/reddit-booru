<?php

// RedditBooru Unit Test Harness

if (!defined('USE_MOCK_DB')) {
    define('USE_MOCK_DB', false);
}

if (USE_MOCK_DB) {
    require('test/test_db.php');
}

chdir('/var/www/reddit-booru');
require('lib/aal.php');