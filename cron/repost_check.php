<?php

require('lib/aal.php');
define('DISABLE_CACHE', true);
define('MIN_TIME_LIMIT', 7200);
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.81 Safari/537.36');

$DOMAIN_BLACKLIST = [ 'safebooru.org', 'pixiv.net', 'danbooru.us', 'gelbooru.com', 'yande.re' ];
$BLACKLIST_REGEX = '/(' . implode('|', str_replace('.', '\\.', $DOMAIN_BLACKLIST)) . ')/i';

/**
 * Mimics a browser request for a post and returns the status code
 * @param Api\Post $post The post to request
 * @return int The HTTP reponse code
 */
function mimicBrowserRequest(Api\Post $post) {

    // If this is a cdn.awwni.me link, remove any https
    if (strpos($post->link, CDN_BASE_URL) === 0) {
        $post->link = str_replace('https', 'http', $post->link);
    }

    $c = curl_init($post->link);
    curl_setopt($c, CURLOPT_USERAGENT, USER_AGENT);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_REFERER, 'https://redd.it/' . $post->externalId);
    curl_setopt($c, CURLOPT_NOBODY, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 60);

    curl_exec($c);
    $retVal = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    return (int) $retVal;
}

/**
 * Comments on a post and reports it
 */
function messageAndReport(Api\Reddit $bot, $post, $message) {
    $bot->Comment($message, $post->externalId, REDDIT_LINK);
    $bot->Report($post->externalId, REDDIT_LINK);
    sleep(5);
}

$start = time();
$bot = new Api\Reddit(RB_BOT);

// Force a login
$bot->login();

$data = json_decode($bot->data);
if (!$data) {
    $data = (object) [
        'lastCheck' => $start - 86400
    ];
}

// Get all the sources that will do a repost check
$sources = Api\Source::queryReturnAll([ 'repostCheck' => [ 'gt' => 0 ] ]);
foreach ($sources as $source) {

    // Validate that the latest wave of post URLs actually resolve correctly
    $posts = Api\Post::queryReturnAll([ 'dateCreated' => [ 'gt' => $data->lastCheck ], 'sourceId' => $source->id ]);
    if ($posts) {
        foreach ($posts as $post) {
            echo '[' . $post->externalId . '] Verifying "' . $post->title . '"...';

            // Check for hotlinking first
            if (preg_match($BLACKLIST_REGEX, $post->link)) {

                $message = 'It looks like you might be linking to a site that doesn\'t allow hotlinking. Please rehost with a service like [redditbooru](https://redditbooru.com) or [imgur](https://imgur.com) and then repost.';
                messageAndReport($bot, $post, $message);

            // Passing that, verify that the link actually loads
            } else {
                $httpCode = mimicBrowserRequest($post);
                if ($httpCode !== 200) {
                    $message = 'Uh oh! I wasn\'t able to load this link. Here\'s a few tips to help you out:' . PHP_EOL . PHP_EOL;
                    $message .= '- Make sure that your URL is correct' . PHP_EOL;
                    $message .= '- If you are linking directly to an image, please rehost with a service like [redditbooru](https://redditbooru.com) or [imgur](https://imgur.com) and then repost.' . PHP_EOL . PHP_EOL;
                    $message .= 'Response code: ' . $httpCode;
                    messageAndReport($bot, $post, $message);
                }
            }

            echo 'DONE', PHP_EOL;
        }
    }

    $reposts = [];

    // Grab all images for this source that have been created since the last check
    $images = Api\PostData::queryReturnAll([ 'dateCreated' => [ 'gt' => $data->lastCheck ], 'sourceId' => $source->id ]);
    if ($images) {
        foreach ($images as $image) {

            if (!isset($reposts[$image->externalId])) {
                $reposts[$image->externalId] = (object) [
                    'imageCount' => 0,
                    'reposts' => [],
                    'externalId' => $image->externalId
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
                    if (isset($match->identical) && $match->postId != $image->postId &&
                            $match->dateCreated + $source->repostCheck > $start &&
                            $match->dateCreated + MIN_TIME_LIMIT < $start &&
                            $match->visible) {
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
                    $message .= '- [' . $repost->title . '](https://redd.it/' . $repost->externalId . ') posted by /u/' . $repost->userName . ' ' . Lib\Util::relativeTime($repost->dateCreated) . ' ago' . PHP_EOL;
                }
                $message .= PHP_EOL . 'Be sure to use [redditbooru](https://redditbooru.com/) before posting to check for similar images.';
                messageAndReport($bot, $post, $message);

                echo 'Message sent: ', PHP_EOL;
                echo $message, PHP_EOL, PHP_EOL;
            }
        }

    }

}

$data->lastCheck = $start;
$bot->data = json_encode($data);
$bot->Save();
