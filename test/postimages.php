<?php

define('USE_MOCK_DB', true);
require('harness.php');

class PostImagesTest extends PHPUnit_Framework_TestCase {

    public function testAssignImagesToPost() {

        $img1 = new Api\Image();
        $img1->id = 1;
        $img2 = new Api\Image();
        $img2->id = 3;
        $post = new Api\Post();
        $post->id = 1;

        $this->assertTrue(Api\PostImages::assignImagesToPost([ $img1, $img2 ], $post));
        $result = Lib\Db::$lastResult;
        $this->assertEquals($result->query, 'INSERT INTO post_images VALUES (:postId, :image0), (:postId, :image1)');
        $this->assertEquals($result->params, [ ':postId' => $post->id, ':image0' => $img1->id, ':image1' => $img2->id ]);

    }

}