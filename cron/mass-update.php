<?php

function fetchDbPosts($page = 0) {
    $POSTS_PER_PAGE = 100;
    return Api\Post::queryReturnAll(null, [ 'id' => 'asc' ], $POSTS_PER_PAGE, $page * $POSTS_PER_PAGE);
}

require('lib/aal.php');

$page = 0;
$posts = [];
while (count($posts = fetchDbPosts($page))) {

    // A hash map of the posts by reddit ID
    $postsById = [];

    $ids = array_map(function($item) {
        return $item->externalId;
    }, $posts);

    $data = Api\Reddit::GetPostsByIds($ids);
    if ($data) {

        $updateCount = 0;

        for ($i = 0, $count = count($data->children); $i < $count; $i++) {
            $post = $posts[$i];
            $redditPost = $data->children[$i]->data;

            if ($post->externalId == $redditPost->id && $post->score != $redditPost->score) {
                $updateCount++;
                echo '[', $post->externalId, '] Updating post score to ', $redditPost->score, ' from ', $post->score, '...';
                $post->score = (int) $redditPost->score;
                echo $post->sync() ? 'DONE' : 'FAIL', PHP_EOL;
            }

        }

        echo 'Updated ', $updateCount, ' records.', PHP_EOL;
    }

    $page++;
    echo 'Fetching page ', ($page + 1), '...', PHP_EOL;

}