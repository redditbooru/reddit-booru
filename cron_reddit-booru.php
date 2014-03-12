<?php

define('DISABLE_CACHE', true);
chdir('/var/www/reddit-booru');

require('lib/aal.php');

define('POST_COUNT', 100);
define('REPOST_ALLOWANCE', 60 * 24 * 60 * 60); // sixty days

function debug_log($message) {
    echo $message, PHP_EOL;
    $file = fopen('/home/matt/cron/reddit-booru/logs/reddit-booru.log', 'ab');
    fwrite($file, '[' . date('Y-m-d h:i:s') . '] ' . $message . PHP_EOL);
    fclose($file);
}

function checkForRepost($image, $sourceId) {

	$time = time();

	if ($image instanceof Api\Image) {
		// Run a repost check
		$repost = Api\Post::reverseImageSearch([
			'image' => $image,
			'sources' => $sourceId
		]);

		if (null != $repost && count($repost->results) > 0) {
			// Check the closest image to see how close it is
			foreach ($repost->results as $post) {
				if ($post->post_id != $image->postId) {
					if (round($post->distance, 4) == 0 && $post->post_date >= $time - REPOST_ALLOWANCE) {
					    // JSON encode/decode the object to strip private keys
					    $data = json_decode(json_encode($image));
					    $data->repost = $post;
						Lib\Logger::log('reposts', $data);
						break;
					}
				}
			}
		}
	}

}

function downloadImage($url, $postId, $sourceId) {
	$log = 'Downloading "' . $url . '"...';
	$image = Api\Image::createFromImage($url, $postId, $sourceId);
	if (null == $image) {
		$log .= 'FAILED';
	} else {
		$log .= 'SUCCESS';
	}

	checkForRepost($image, $sourceId);

    debug_log($log);
    return !(null == $image);
}

/**
 * Handles post identity juggling for redditbooru galleries
 */
function handleRedditbooruGallery($id, $sourceId, $redditPost, $updateTime) {
    // Validate the post
    $post = new Api\Post();
    $post->getById($id);

    if ($post && !$post->externalId) {
        // Update all the information we don't have
        $post->sourceId = $sourceId;
        $post->externalId = $redditPost->id;
        $post->link = $redditPost->url;
        $user = Api\User::getByName($redditPost->author);
        if ($user) {
            $post->userId = $user->id;
        }
        
        // We already have all the images, so additional processing is unecessary
        $post->processed = true;
        $post->visible = true;
        $post->score = $redditPost->score;
        $post->sync();

        // Update all children images
        Lib\Db::Query('UPDATE images SET source_id = :sourceId WHERE post_id = :postId', [ ':sourceId' => $post->sourceId, ':postId' => $post->id ]);

        debug_log('Redditbooru gallery synced: ' . $post->link);
    } else {
        if (!$post) {
            debug_log('Invalid Redditbooru gallery: ' . $id);
        } else {
            $post->dateUpdated = $updateTime;
            $post->visible = true;
            $post->score = $redditPost->score;
            $post->sync();
            debug_log('Redditbooru gallery ' . $id . ' updated');
        }
    }
}

function processSubreddit($source) {

	$reddit = new Api\Reddit(REDDIT_USER);

	$time = time();
	$earliest = null;
	
	$afterId = '';

	$posts = $reddit->GetPageListing($source->name . '/new/', 0, $afterId, POST_COUNT);
	if (null != $posts) {
		$afterId = isset($posts->after) ? $posts->after : null;

		foreach ($posts->children as $child) {
			
			if (strpos($child->data->domain, 'self.') === false && strpos($child->data->domain, 'youtu') === false) {
				
                // Check for reddit booru
                if (preg_match('/redditbooru\.com\/gallery\/([\d]+)/', $child->data->url, $match)) {
                    handleRedditbooruGallery($match[1], $source->id, $child->data, $time);
                } else {               
                    $post = Api\Post::getByExternalId($child->data->id, $source->id);
    				if (null == $post) {
    					$post = Api\Post::createFromRedditObject($child);
                        
    					$post->sourceId = $source->id;
    					debug_log('New post: ' . $post->title . ' (' . $post->externalId . ')');
    				} else {
    					if ($post->meta->flair != $child->data->link_flair_text) {
    						$post->meta->flair = $child->data->link_flair_text;
    						$post->keywords = Api\Post::generateKeywords($post->title . ' ' . $post->meta->flair);
    					}
    					$post->score = $child->data->score;
                        $post->visible = true;
    					debug_log('Updated post: ' . $post->title . ' (' . $post->externalId . ')');
    				}
    				
    				if (null != $post) {
    					// Save the date if it's earlier than the previous
    					$earliest = !$earliest || $post->dateCreated < $earliest ? $post->dateCreated : $earliest;
    					$post->dateUpdated = $time;
    					$post->sync();
    				}
                }
			}
		}

	}
	
	// Check for deleted/removed posts
	debug_log('---- Checking for removed posts ----');
	$params = [ ':sourceId' => $source->id, ':dateCreated' => $earliest, ':dateUpdated' => $time ];
	$result = Lib\Db::Query('SELECT * FROM posts WHERE source_id = :sourceId AND post_date >= :dateCreated AND post_updated != :dateUpdated AND post_visible = 1', $params);
	if ($result && $result->count > 0) {
		debug_log('-- ' . $result->count . ' posts found --');
		while ($row = Lib\Db::Fetch($result)) {
			$post = new Api\Post($row);
			$post->visible = false;
			$post->dateUpdated = $time;
			$post->sync();
			debug_log('Hiding "' . $post->title . '" (' . $post->externalId, ')');
		}
	} else {
		debug_log('None');
	}
	
	// Process images
	$posts = Api\Post::getUnprocessed($source->id);
    debug_log(count($posts) . ' unprocessed posts');
	if (null != $posts) {
		foreach ($posts as $post) {
            
			$processed = true;

			// Check to see if another thread is already working on this post
			$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM images WHERE post_id = :postId', [ ':postId' => $post->id ]));
			if ($row->total == 0) {

				$url = parse_url($post->link);
				$domain = isset($url['host']) ? $url['host'] : null;
				$path = isset($url['path']) ? $url['path'] : null;
				$images = [];

	            // Check for redditbooru hosted images
	            if ($url['host'] === 'cdn.awwni.me') {
	                // Find the image entry in the database for this
	                $debug = 'Found CDN hosted image ' . $post->link . ' (' . $post->id . ')...';
	                $query = 'SELECT * FROM images WHERE (post_id IS NULL OR post_id = :postId) AND image_cdn_url = :cdnUrl';
	                $result = Lib\Db::Query($query, [ ':cdnUrl' => $post->link, ':postId' => $post->id ]);
	                if ($result && $result->count > 0) {
	                    $image = new Api\Image(Lib\Db::Fetch($result));
	                    $image->postId = $post->id;
	                    $image->sourceId = $source->id;
	                    $image->contentRating = $source->contentRating;
	                    $image->sync();
	                    $debug .= 'UPDATED';
	                } else {
	                    $debug .= 'FAILED';
	                }
	                debug_log($debug);

	            // Imgur album
	            } else if (preg_match('/imgur\.com\/(a|gallery)\/([\w]+)/i', $post->link, $matches)) {
					debug_log('Imgur album "' . $matches[2] . '"');
					$images = Api\Image::getImgurAlbum($matches[2]);

				// Comma delimited imgur album
				} else if (strtolower($url['host']) === 'imgur.com' && strpos($url['path'], ',') !== false) {
					$path = str_replace('/', '', $url['path']);
					$files = explode(',', $path);
					debug_log('imgur comma delimited album: ' . $path);
					foreach ($files as $file) {
						$images[] = 'http://imgur.com/' . $file . '.jpg';
					}

                // Yandere image
                } else if ($url['host'] === 'yande.re') {
                    debug_log('Yandere post "' . $post->link . '"');
                    $images = Api\Image::getYandereImage($post->link);

				// Minus album
                } else if (preg_match('/([\w]+\.)?minus\.com\/([^\.])+$/i', $post->link, $matches)) {
                    debug_log('Minus album "' . $matches[2] . '"');
                    $images = Api\Image::getMinusAlbum($matches[2]);

                // tumblr posts
                } else if (preg_match('/\/post\/([\d]+)\//', $url['path'], $matches)) {
                	debug_log('Tumblr post: ' . $matches[1]);
                	$images = Api\Image::getTumblrImages($post->link);

                // mediacrush
                } else if ($url['host'] === 'mediacru.sh') {
                	debug_log('Media crush');
                	$images = Api\Image::getMediacrushImages($post->link);

                // Everything else
				} else {
					$images[] = $post->link;
				}

				// If there are images to get, get all of them and assign to the current post
				if (count($images)) {
                    foreach ($images as $image) {
                        $processed &= downloadImage($image, $post->id, $post->sourceId);
                    }
				}
				
				$post->processed = $processed;

				// If there was an image format failure, mark the post as processed but hidden so we don't keep
				// coming back to broken shit
				if (Api\Image::$imageLoadError === IMGERR_INVALID_FORMAT) {
					$post->processed = true;
					$post->visible = false;
				}

				$post->sync();

			}
			
		}
	}

}

function processMumble($source) {

	$reddit = new Api\Reddit(REDDIT_USER);
	$posts = $reddit->GetPageListing('r/awwnime/new/', 0, '', POST_COUNT);
	if (null != $posts) {
	
		foreach ($posts->children as $child) {
			
			if ($child->data->author == 'NearNihil' && $child->data->domain == 'self.awwnime' && false !== strpos($child->data->title, 'Multiplayer Moe')) {
				$post = Api\Post::getByExternalId($child->data->id, $source->id);
				if (null === $post) {
					$post = Api\Post::createFromRedditObject($child);
				}
				$post->sourceId = $source->id;
				$post->dateUpdated = time();
				$post->sync();
				break;
			}
		}
	
	}

}

$sources = Api\Source::getAllEnabled();

foreach ($sources as $source) {
	
	debug_log('---- Processing ' . $source->name . ' ---');
	
	switch ($source->type) {
		case 'subreddit':
			processSubreddit($source);
			break;
		case 'mumble':
			processMumble($source);
			break;
	}

}
