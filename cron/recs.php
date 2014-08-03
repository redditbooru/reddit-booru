<?php

require('lib/aal.php');

define('POSTS_PER_PAGE', 20);

$_COOKIE['RBSESS_20140718'] = 'BETA_TESTING_COOKIE';

Lib\Session::start();
$user = Api\User::getCurrentUser();

/*

$page = 0;
$posts = Api\Post::queryReturnAll([ 'sourceId' => 1 ], null, POSTS_PER_PAGE);
while (count($posts) === POSTS_PER_PAGE) {

    echo 'Page ', ($page + 1), PHP_EOL;

    Api\PostData::getVotesForPosts($posts, $user);

    $page++;
    $posts = Api\Post::queryReturnAll([ 'sourceId' => 1 ], null, POSTS_PER_PAGE, $page * POSTS_PER_PAGE);

}

*/

$voteData = $user->getVoteData();

// Strip out all upvotes
$upvotes = [];
foreach ($voteData as $id => $status) {
    if ($status->vote == 1) {
        $upvotes[] = $id;
    }
}
unset($voteData);

// Get all the upvoted posts
$posts = Api\PostData::queryReturnAll([ 'externalId' => [ 'in' => $upvotes ], 'userName' => [ 'ne' => 'dxprog' ] ]);

$users = [];
$keywordsData = [];

foreach ($posts as $post) {
    if (!isset($users[$post->userName])) {
        $users[$post->userName] = 0;
    }
    $users[$post->userName]++;

    $obj = new stdClass;
    $obj->keywords = $post->keywords;
    $obj->score = (int) $post->score;
    $keywordsData[$post->id] = $obj;

}

$count = count($posts);
$userMin = null;
foreach ($users as $user => $value) {
    $users[$user] = $value / $count;
    $userMin = $userMin === null || $users[$user] < $userMin ? $users[$user] : $userMin;
}

$keywords = Controller\Stats::getKeywordRanks([ 'data' => $posts ]);

$query = 'SELECT DISTINCT post_external_id FROM post_data WHERE user_name != "dxprog" AND post_visible = 1 AND source_id = 1 AND (';
$params = [];
$like = [];
$i = 0;

$avg = array_sum($keywords) / count($keywords);

foreach ($keywords as $keyword => $score) {
    if ($score >= $avg) {
        $params[':phrase' . $i] = '%' . $keyword . '%';
        $like[] = 'post_keywords LIKE :phrase' . $i;
        $i++;
    }
}

$query .= implode(' OR ', $like) . ') AND post_external_id NOT IN ("' . implode('","', $upvotes) . '") LIMIT 50';

$result = Lib\Db::Query($query, $params);

$recs = [];
while ($row = Lib\Db::Fetch($result)) {
    $post = Api\PostData::queryReturnAll([ 'externalId' => $row->post_external_id ])[0];
    $post->weight = 0;

    foreach ($keywords as $keyword => $score) {
        if (strpos($post->keywords, $keyword) !== false) {
            $post->weight += $score;
        }
    }

    if (isset($users[$post->userName])) {
        $post->weight *= $users[$post->userName];
    } else {
        $post->weight *= $userMin;
    }
    $recs[] = $post;
}

uasort($recs, function($a, $b) {
    return $a->weight > $b->weight ? -1 : 1;
});

foreach ($recs as $post) {
    echo $post->title, ' - ', $post->userName, ' (http://redd.it/' . $post->externalId . ')', PHP_EOL;
}