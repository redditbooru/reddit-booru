<?php

$retVal = new stdClass;
$retVal->success = false;

require('lib/aal.php');

if (count($_POST) > 0) {

    $title = Lib\Url::Get('title', null, $_POST);
    $images = Lib\Url::Get('images', null, $_POST);
    
    if ($title && $images) {
        
        // Create the post entry
        $post = new Api\Post();
        $post->title = $title;
        $post->link = 'http://redditbooru.com/';
        $post->processed = 1;
        $post->dateCreated = time();
        $post->dateUpdated = time();
        $post->visible = true;
        $post->keywords = Api\Post::generateKeywords($post->title);
        if ($post->sync() && $post->id) {
            
            // Add all the images
            $images = explode(',', $images);
            $params = [ ':postId' => $post->id ];
            for ($i = 0, $count = count($images); $i < $count; $i++) {
                $params[':image' . $i] = $images[$i];
                $images[$i] = ':image' . $i;
            }
            
            $query = 'UPDATE images SET post_id = :postId WHERE post_id IS NULL AND image_id IN (' . implode(',', $images) . ')';
            if (Lib\Db::Query($query, $params)) {
                $retVal->success = true;
                $retVal->post = $post;
            }
            
        }
    
    }

}

echo json_encode($retVal);