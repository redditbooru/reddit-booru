<?php

define('RB_ROOT', '/var/www/reddit-booru/');
define('CACHE_DIR', 'cache/');
define('MAX_THUMBNAIL_DATE', 68400 * 14); // Keep thumbnails for 14 days

/**
 * Redditbooru data cleaner
 */

function cleanThumbnailCache() {

    $dir = opendir(RB_ROOT . CACHE_DIR);
    $files = 0;
    $bytes = 0;

    echo 'Performing thumbnail cache cleanup...', PHP_EOL;

    if ($dir) {
        while ($file = readdir($dir)) {
            $path = RB_ROOT . CACHE_DIR . $file;
            if (is_file($path) && (filemtime($path) + MAX_THUMBNAIL_DATE) < time()) {
                $files++;
                $bytes += filesize($path);
                echo 'Deleting thumbnail ', $file, PHP_EOL;
                unlink($path);
            }
        }
    }

    echo 'Cleaned ', $files, ' files/', round($bytes / 1024), 'Kb', PHP_EOL;

}

cleanThumbnailCache();