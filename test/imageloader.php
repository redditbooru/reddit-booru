<?php

require('harness.php');

class ImageTest extends PHPUnit_Framework_TestCase {
    
    private function getImagesFromUrl($url) {
        return Lib\ImageLoader::getImagesFromUrl($url);
    }

    public function testImgurSingle() {
        // Test i.imgur domain
        $this->assertEquals([ 'http://i.imgur.com/2dYfYlM.jpg' ], $this->getImagesFromUrl('http://i.imgur.com/2dYfYlM'));
        $this->assertEquals([ 'http://i.imgur.com/2dYfYlM.jpg' ], $this->getImagesFromUrl('http://i.imgur.com/2dYfYlM.jpg'));

        // Test imgur with no subdomain
        $this->assertEquals([ 'http://imgur.com/2dYfYlM.jpg' ], $this->getImagesFromUrl('http://imgur.com/2dYfYlM.jpg'));
        $this->assertEquals([ 'http://imgur.com/2dYfYlM.jpg' ], $this->getImagesFromUrl('http://imgur.com/2dYfYlM'));

        // Test with www domain
        $this->assertEquals([ 'http://www.imgur.com/2dYfYlM.jpg' ], $this->getImagesFromUrl('http://www.imgur.com/2dYfYlM'));
    }

    public function testImgurAlbum() {

        // Standard album
        $result = $this->getImagesFromUrl('http://imgur.com/a/hiwIT');
        $this->assertCount(13, $result);

        // Comma delimited list
        $result = $this->getImagesFromUrl('http://imgur.com/SnDVbyQ,eYARKqe');
        $this->assertCount(2, $result);

    }

    public function testMediaCrush() {


        // Test mediacru.sh
        $this->assertEquals([ 'https://mediacru.sh/xzoyUYowDuVc.png' ], $this->getImagesFromUrl('https://mediacru.sh/xzoyUYowDuVc'));
    }

}