<?php

require('lib/aal.php');

define('ARG_SOURCE', 'source');
define('ARG_SORT', 'sort');
define('ARG_PAGE_COUNT', 'page-count');
define('ITEMS_TO_LOAD', 100);

/**
 * Throws a timestamped message to the console and log file
 */
function _log($message) {
    echo date('[Y-m-d h:i:s]'), ' ' . $message, PHP_EOL;
}

function _logDbError($header) {
    _log($header . Lib\Db::$lastError);
}

/**
 * Parses the command line arguments
 */
function parseArgs($argv, $argc) {

    $retVal = [];

    for ($i = 0; $i < $argc; $i++) {
        if (preg_match('/--([\w-]+)=([\w]+)/', $argv[$i], $match)) {
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

        // If the image is hosted on the CDN, attempt to grab the original from the database
        if (strpos($url, CDN_BASE_URL) !== false) {
            _log($logHead . 'Pulling ID from CDN hosted image');
            $id = str_replace(CDN_BASE_URL, '', $url);
            $id = substr($id, 0, strpos($id, '.'));
            $id = base_convert($id, 36, 10);
            $retVal = Api\Image::getById($id);
            $retVal = $retVal->id == $id ? $retVal : null;
        }

        if (!$retVal) {
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

    }

    return $retVal;

}

/**
 * Processes all the images in a post
 */
function updatePost(Api\Post $post, Api\Post $dbPost) {

    // Check for any actual changes between the reddit version and the database version before firing off a request
    if ($post->title !== $dbPost->title || $post->keywords !== $dbPost->keywords || $post->score != $dbPost->score || $post->nsfw != $dbPost->nsfw) {
        _log('[ ' . $post->externalId . ' ] Updating...');
        $dbPost->title = $post->title;
        $dbPost->keywords = $post->keywords;
        $dbPost->score = $post->score;
        $dbPost->nsfw = $post->nsfw;
        if ($dbPost->sync()) {
            Api\PostData::updateDenormalizedPostData($dbPost->id);
        }
    }

}

function addPost(Api\Post $post) {

    $logHead = '[ ' . $post->externalId . ' ] ';

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
function processSubreddit($id, $page, $pageCount = 1) {

    // Get the source information
    $source = Api\Source::getById($id);
    if (null !== $source && $source->enabled) {

        // Get the reddit page listing
        $reddit = new Api\reddit();
        $afterId = null;

        for ($i = 0; $i < $pageCount; $i++) {

            _log('Retrieving listing page ' . ($i + 1) . ' for ' . $source->name);

            $listing = $reddit->GetPageListing($source->name . $page, null, $afterId, ITEMS_TO_LOAD);
            if ($listing && isset($listing->children) && is_array($listing->children)) {
                $posts = [];
                $externalIds = [];

                // Create post objects out of the listing and save off the reddit IDs so we can fetch them from the database
                foreach ($listing->children as $post) {
                    $obj = Api\Post::createFromRedditObject($post->data);
                    $obj->sourceId = $source->id;
                    $posts[] = $obj;
                    $externalIds[] = $obj->externalId;
                }

                // Get all database posts matching the current set
                $dbPosts = Api\Post::queryReturnAll([ 'externalId' => [ 'in' => $externalIds ] ]);
                $dbPosts = count($dbPosts) > 0 ? $dbPosts : [];

                // Process them
                foreach ($posts as $post) {
                    $dbPost = array_filter($dbPosts, function($item) use ($post) {
                        return $item->externalId == $post->externalId;
                    });

                    if (count($dbPost) > 0) {
                        updatePost($post, current($dbPost));
                    } else {
                        addPost($post);
                    }

                }

                // Don't check for removals on anything other than the first page
                if ($i === 0) {
                    checkPostRemovals($id, $listing->children);
                }

                // Save the afterId for the next iteration
                $afterId = $listing->after;

            } else {
                _log('Unable to retrieve page listing for ' . $source->name);
            }

        }

    } else {
        _log('Invalid source ID or source not enabled: ' . $id);
    }

}


$args = parseArgs($argv, $argc);
$sourceId = Lib\Url::Get(ARG_SOURCE, null, $args);
$sort = Lib\Url::Get(ARG_SORT, 'new', $args);
$pageCount = Lib\Url::GetInt(ARG_PAGE_COUNT, 1, $args);

if ($sourceId) {
    processSubreddit($sourceId, '/' . $sort, $pageCount);
} else {
    _log('Must provide a source ID');
}