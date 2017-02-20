<?php

require('harness.php');

class PostTest extends PHPUnit_Framework_TestCase {

    public function testGetPostIdFromUrl() {
        $base10Id = 1337;
        $base36Id = base_convert($base10Id, 10, 36);

        $id = Api\Post::getPostIdFromUrl('http://redditbooru.com/gallery/' . $base36Id . '/my-gallery');
        $this->assertEquals($base10Id, $id, 'HTTP with perma slug');

        $id = Api\Post::getPostIdFromUrl('https://redditbooru.com/gallery/' . $base36Id . '/my-gallery');
        $this->assertEquals($base10Id, $id, 'HTTPS with perma slug');

        $id = Api\Post::getPostIdFromUrl('https://redditbooru.com/gallery/' . $base36Id);
        $this->assertEquals($base10Id, $id, 'No gallery slug');

        $id = Api\Post::getPostIdFromUrl('https://awwnime.redditbooru.com/gallery/' . $base36Id);
        $this->assertEquals($base10Id, $id, 'Source subdomain');
    }

}