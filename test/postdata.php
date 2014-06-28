<?php

define('USE_MOCK_DB', true);
require('harness.php');

class PostDataTest extends PHPUnit_Framework_TestCase {

    public function testUpdateAll() {

        $post = new Api\Post();
        $post->id = 1;
        $post->score = 500;
        $post->keywords = 'blah';
        $postData = new Api\PostData();
        $postData->postId = $post->id;

        $this->assertTrue($postData->updateAll($post));
        $result = Lib\Db::$lastResult;

        $this->assertEquals($result->query, 'UPDATE `post_data` SET `source_id` = :sourceId, `post_external_id` = :externalId, `post_date` = :dateCreated, `post_title` = :title, `user_id` = :userId, `post_keywords` = :keywords, `post_score` = :score, `post_nsfw` = :nsfw WHERE `post_id` = :postId');
        $this->assertEquals($result->params, [
            ':postId' => $post->id,
            ':userId' => $post->userId,
            ':sourceId' => $post->sourceId,
            ':externalId' => $post->externalId,
            ':dateCreated' => $post->dateCreated,
            ':title' => $post->title,
            ':keywords' => $post->keywords,
            ':score' => $post->score,
            ':nsfw' => $post->nsfw
        ]);

    }

}