<?php

chdir('/var/www/reddit-booru');

require('lib/aal.php');

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

$reddit = new Api\Reddit();

foreach ($sources as $source) {
	
	echo '---- Processing ', $source->name, ' ---', PHP_EOL;
	
	$afterId = '';
	for ($i = 0; $i < 5; $i++) {
		echo '-- Getting Page', $i + 1, ' --', PHP_EOL;
		$posts = $reddit->GetPageListing($source->name . '/', $i, $afterId);
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
						$post->dateUpdated = time();
						if ($post->meta->flair != $child->data->link_flair_text) {
							$post->meta->flair = $child->data->link_flair_text;
							$post->keywords = Api\Post::generateKeywords($post->title . ' ' . $post->meta->flair);
						}
						$post->score = $child->data->score;
						echo 'Updated post: ', $post->title, PHP_EOL;
					}
					
					if (null != $post) {
						$post->sync();
					}
				}
				
			}
		}

	}
	
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