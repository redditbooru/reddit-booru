<?php

define('DISABLE_CACHE', true);
chdir('/var/www/reddit-booru');

require('lib/aal.php');

define('POST_COUNT', 100);

function debug_log($message) {
    echo $message, PHP_EOL;
    $file = fopen('/home/matt/cron/reddit-booru/logs/reddit-booru.log', 'ab');
    fwrite($file, '[' . date('Y-m-d h:i:s') . '] ' . $message . PHP_EOL);
    fclose($file);
}

function downloadImage($url, $postId, $sourceId) {
	$log = 'Downloading "' . $url . '"...';
	$image = Api\Image::createFromImage($url, $postId, $sourceId);
	if (null == $image) {
		$log .= 'FAILED';
	} else {
		$log .= 'SUCCESS';
	}
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
    					log('New post: ' . $post->title . ' (' . $post->externalId . ')');
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

	            // Check for redditbooru hosted images
	            if (strpos($post->link, 'http://cdn.awwni.me') === 0) {                
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
	            } else if (preg_match('/imgur\.com\/a\/([\w]+)/i', $post->link, $matches)) {
					echo 'Imgur album "', $matches[1], '"', PHP_EOL;
					$images = Api\Image::getImgurAlbum($matches[1]);
					if (null != $images) {
						foreach ($images as $image) {
							$processed &= downloadImage($image, $post->id, $post->sourceId);
						}
					}
				} else {
					$processed = downloadImage($post->link, $post->id, $post->sourceId);
				}
				
				$post->processed = $processed;
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
