<?php

chdir('/var/www/reddit-booru');

require('lib/aal.php');

define('POST_COUNT', 100);

function downloadImage($url, $postId, $sourceId) {
	echo 'Downloading "', $url, '"...';
	$image = Api\Image::createFromImage($url, $postId, $sourceId);
	if (null == $image) {
		echo 'FAILED', PHP_EOL;
	} else {
		echo 'SUCCESS', PHP_EOL;
	}
}

$sources = Api\Source::getAllEnabled();

$reddit = new Api\Reddit(REDDIT_USER);

foreach ($sources as $source) {
	
	echo '---- Processing ', $source->name, ' ---', PHP_EOL;
	
	$time = time();
	$earliest = null;
	
	$afterId = '';
		
	$posts = $reddit->GetPageListing($source->name . '/new/', $i, $afterId, POST_COUNT);
	if (null != $posts) {
		$afterId = isset($posts->after) ? $posts->after : null;

		foreach ($posts->children as $child) {
			
			if (strpos($child->data->domain, 'self.') === false && strpos($child->data->domain, 'youtu') === false) {
				$post = Api\Post::getByExternalId($child->data->id, $source->id);
				if (null == $post) {
					$post = Api\Post::createFromRedditObject($child);
					$post->sourceId = $source->id;
					echo 'New post: ', $post->title, PHP_EOL;
				} else {
					if ($post->meta->flair != $child->data->link_flair_text) {
						$post->meta->flair = $child->data->link_flair_text;
						$post->keywords = Api\Post::generateKeywords($post->title . ' ' . $post->meta->flair);
					}
					$post->score = $child->data->score;
					echo 'Updated post: ', $post->title, PHP_EOL;
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
	
	// Check for deleted/removed posts
	echo '---- Checking for removed posts ----', PHP_EOL;
	$params = [ ':sourceId' => $source->id, ':dateCreated' => $earliest, ':dateUpdated' => $time ];
	$result = Lib\Db::Query('SELECT * FROM posts WHERE source_id = :sourceId AND post_date >= :dateCreated AND post_updated != :dateUpdated AND post_visible = 1', $params);
	if ($result && $result->count > 0) {
		echo '-- ', $result->count, ' posts found --', PHP_EOL;
		while ($row = Lib\Db::Fetch($result)) {
			$post = new Api\Post($row);
			$post->visible = false;
			$post->dateUpdated = $time;
			$post->sync();
			echo 'Hiding "', $post->title, '" (', $post->externalId, ')', PHP_EOL;
		}
	} else {
		echo 'None', PHP_EOL;
	}
	
	// Process images
	$posts = Api\Post::getUnprocessed($source->id);
	if (null != $posts) {
		foreach ($posts as $post) {
			
			if (preg_match('/imgur\.com\/a\/([\w]+)/i', $post->link, $matches)) {
				echo 'Imgur album "', $matches[1], '"', PHP_EOL;
				$images = Api\Image::getImgurAlbum($matches[1]);
				if (null != $images) {
					foreach ($images as $image) {
						downloadImage($image, $post->id, $post->sourceId);
					}
				}
			} else {
				downloadImage($post->link, $post->id, $post->sourceId);
			}
			
			$post->processed = true;
			$post->sync();
			
		}
	}

}