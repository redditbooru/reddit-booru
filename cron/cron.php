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

function _logDbError($header) {
    print_r(Lib\Db::$lastError); exit;
    _log($header . Lib\Db::$lastError);
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
        $retVal = new Api\Image(Lib\Db::Fetch($result));
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
                    $retVal = $image;
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

    $post = Api\Post::createFromRedditObject($post);
    $post->sourceId = $source->id;
    $logHead = '[ ' . $post->externalId . ' ] ';

    // Check to see if the post has already been processed
    $result = Api\PostData::query([ 'externalId' => $post->externalId ]);
    if ($result && $result->count > 0) {
        _log($logHead . 'Updating...');
        $row = Lib\Db::Fetch($result);
        $dataRow = new Api\PostData($row);
        $dataRow->updateAll($post);
    } else {

        // Resolve the post link to images
        $images = Lib\ImageLoader::getImagesFromUrl($post->link);
        if (is_array($images) && count($images) > 0) {
            _log($logHead . 'resolved to ' . count($images) . ' images');

            // Process each image
            $imgObjs = [];
            foreach ($images as $image) {
                _log($logHead . 'Processing image ' . $image);
                $imgObj = processImage($image);
                if ($imgObj) {
                    $imgObjs[] = $imgObj;
                }
            }

            // Create the post object
            _log($logHead . 'Syncing post to database...');
            $syncOkay = false;
            try {
                if (count($imgObjs) > 0) {
                    $post->visible = 1;
                }
                $syncOkay = $post->sync();
            } catch (Exception $e) {
                // Continue on duplicate entry, it probably means that something else failed down the line
                // Fetch the post record and we'll try again
                if ($e->errorInfo[1] === 1062) {
                    $result = Api\Post::query([ 'externalId' => $post->externalId ]);
                    if ($result && $result->count > 0) {
                        $post = new Api\Post(Lib\Db::Fetch($result));
                        $syncOkay = true;
                    }
                }
            }

            if ($syncOkay && count($imgObjs) > 0) {

                // Assign all the images to the post
                _log($logHead . 'Assigning images to post...');
                if (Api\PostImages::assignImagesToPost($imgObjs, $post)) {

                    // Save off denormalized versions of the data
                    _log($logHead . 'Denormalizing data...');
                    if (Api\PostData::denormalizeForPost($post->id)) {
                        _log($logHead . 'DONE');
                    } else {
                        _log($logHead . 'Error saving denormalized data');
                        _logDbError($logHead);
                    }

                } else {
                    _log($logHead . 'Error assigning images');
                    _logDbError($logHead);
                }

            } else {
                if (!$syncOkay) {
                    _log($logHead . 'Error syncing post to database');
                    _logDbError($logHead);
                } else {
                    _log($logHead . 'No valid images for post');
                }
            }

        }

    }

}

/**
 * Checks for post removals
 */
function checkPostRemovals($sourceId, $listing) {

    // We'll check the latest 3/4 of a normal full load, the idea being that if one isn't in the larger set, it's been removed
    $posts = Api\Post::queryReturnAll([ 'sourceId' => $sourceId ], [ 'dateCreated' => 'desc' ], ITEMS_TO_LOAD * 0.75);
    foreach ($posts as $post) {

        if ($post->visible) {
            $isGood = false;

            for ($i = 0, $count = count($listing); $i < $count; $i++) {
                if ($post->externalId === $listing[$i]->data->id) {

                    // Mark okay and remove this item from the listings
                    $isGood = true;
                    array_splice($listing, $i, 1);
                    break;

                }
            }

            if (!$isGood) {
                _log('[ ' . $post->externalId . ' ] Post no longer found in listing, marking as removed');
                $post->visible = 0;
                $postData = new Api\PostData();
                $postData->postId = $post->id;
                $postData->updateAll($post);
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
            checkPostRemovals($id, $listing->children);
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