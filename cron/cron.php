<?php

require('lib/aal.php');

define('ARG_SOURCE', 'source');
define('ARG_SORT', 'sort');
define('ITEMS_TO_LOAD', 100);

/**
 * Throws a timestamped message to the console and log file
 */
function _log($message) {
    echo date('[Y-m-d h:i:s]'), ' ' . $message, PHP_EOL;
}

/**
 * Parses the command line arguments
 */
function parseArgs($argv, $argc) {

    $retVal = [];

    for ($i = 0; $i < $argc; $i++) {
        if (preg_match('/--([\w]+)=([\w]+)/', $argv[$i], $match)) {
            $retVal[$match[1]] = $match[2];
        }
    }

    return $retVal;

}

/**
 * Downloads, processes, and saves image information and returns the row ID
 */
function processImage($url) {

    $retVal = null;

    $logHead = '[ ' . $url . ' ] ';

    // Check to see if this image is already in the database
    $result = Api\Image::query([ 'url' => $url ]);
    if (null !== $result && $result->count) {
        _log($logHead . 'Found in database');
        $image = new Api\Image(Lib\Db::Fetch($result));
        $retVal = (int) $image->id;
    } else {

        // Attempt a fetch of the image
        _log($logHead . 'Downloading...');
        $imageData = Lib\ImageLoader::fetchImage($url);
        if (null !== $imageData) {

            // Generate the histogram
            _log($logHead . 'Processing...');
            $image = Api\Image::createFromBuffer($imageData->data);
            if (null !== $image) {
                $image->url = $url;
                $image->type = $imageData->type;

                // Save to the database
                if ($image->sync()) {

                    // Save all the various copies of the image
                    $retVal = $image->id;
                    _log($logHead . 'DONE');

                } else {
                    _log($logHead . 'Unable to sync image to database');
                }

            } else {
                _log($logHead . 'Unable to process image');
            }

        } else {
            _log($logHead . 'Unable to download image');
        }

    }

    return $retVal;

}

/**
 * Processes all the images in a post
 */
function processPost($post, $source) {

    // Resolve the post link to images
    $images = Lib\ImageLoader::getImagesFromUrl($post->url);
    if (is_array($images) && count($images) > 0) {
        _log('[ ' . $post->id . ' ] resolved to ' . count($images) . ' images');

        // Process each image
        $ids = [];
        foreach ($images as $image) {
            _log('[ ' . $post->id . ' ] Processing image ' . $image);
            $id = processImage($image);
            if ($id) {
                $ids[] = $id;
            }
        }

    }

}

/**
 * Processes the top X posts in a subreddit page
 */
function processSubreddit($id, $page) {

    // Get the source information
    $source = Api\Source::getById($id);
    if (null !== $source && $source->enabled) {

        // Get the reddit page listing
        $reddit = new Api\reddit();
        $listing = $reddit->GetPageListing($source->name . $page, 0, null, ITEMS_TO_LOAD);
        if ($listing && isset($listing->children) && is_array($listing->children)) {
            foreach ($listing->children as $post) {
                processPost($post->data, $source);
            }
        } else {
            _log('Unable to retrieve page listing for ' . $source->name);
        }

    } else {
        _log('Invalid source ID or source not enabled: ' . $id);
    }

}


$args = parseArgs($argv, $argc);
$sourceId = Lib\Url::Get(ARG_SOURCE, null, $args);
$sort = Lib\Url::Get(ARG_SORT, 'new', $args);

if ($sourceId) {
    processSubreddit($sourceId, '/' . $sort);
} else {
    _log('Must provide a source ID');
}