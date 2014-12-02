<?php

require('lib/aal.php');
define('DISABLE_CACHE', true);

$start = time();
$bot = new Api\Reddit(RB_BOT);

$data = json_decode($bot->data);
if (!$data) {
    $data = (object) [
        'lastCheck' => $start - 86400
    ];
}

// Get all the sources that will do a repost check
$sources = Api\Source::queryReturnAll([ 'repostCheck' => [ 'gt' => 0 ] ]);
foreach ($sources as $source) {

    $reposts = [];

    // Grab all images for this source that have been created since the last check
    $images = Api\PostData::queryReturnAll([ 'dateCreated' => [ 'gt' => $data->lastCheck ], 'sourceId' => $source->id ]);
    if ($images) {
        foreach ($images as $image) {

            if (!isset($reposts[$image->externalId])) {
                $reposts[$image->externalId] = (object) [
                    'imageCount' => 0,
                    'reposts' => []
                ];
            }
            $reposts[$image->externalId]->imageCount++;

            $result = Controller\Images::getByImage([ 'imageUri' => $image->cdnUrl, 'sources' => $source->id ]);
            if ($result && count($result->results)) {
                foreach ($result->results as $match) {
                    // Make sure this
                    // - this is an identical match
                    // - it isn't the image we're checking
                    // - the post falls within the blacked out timeframe
                    if (isset($match->identical) && $match->postId != $image->postId && $match->dateCreated + $source->repostCheck > $start) {
                        $reposts[$image->externalId]->reposts[] = $match;
                        break;
                    }
                }
            }
        }

        // Run through the repost array
        foreach ($reposts as $externalId => $post) {
            // If half or more of the images in this post are reposts, make a comment and report
            $count = count($post->reposts);
            if ($count > 0 && round($count > $post->imageCount / 2)) {
                $message = 'Hi! It looks like ' . ($post->imageCount === 1 ? 'this image' : 'some of these images') . ' may have been posted recently: ' . PHP_EOL . PHP_EOL;
                foreach ($post->reposts as $repost) {
                    $message .= '- [' . $repost->title . '](http://redd.it/' . $repost->externalId . ') posted by /u/' . $repost->userName . ' ' . Lib\Util::relativeTime($repost->dateCreated) . ' ago' . PHP_EOL;
                }
                $message .= PHP_EOL . 'Be sure to use [redditbooru](http://redditbooru.com/) before posting to check for similar images.';
                $bot->Comment($message, $externalId, REDDIT_LINK);
                $bot->Report($externalId, REDDIT_LINK);

                // Sleep a little bit so we don't hammer reddit too much
                sleep(5);
            }
        }

    }

}

$data->lastCheck = $start;
$bot->data = json_encode($data);
$bot->Save();