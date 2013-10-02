<?php

require('harness.php');

class ImageTest extends PHPUnit_Framework_TestCase {
    
    private function parseUrl($url) {
        return Api\Image::parseUrl($url);
    }

    public function testParseUrl() {
        // Test i.imgur domain
        $this->assertEquals('http://i.imgur.com/2dYfYlM.jpg', $this->parseUrl('http://i.imgur.com/2dYfYlM'));
        $this->assertEquals('http://i.imgur.com/2dYfYlM.jpg', $this->parseUrl('http://i.imgur.com/2dYfYlM.jpg'));

        // Test imgur with no subdomain
        $this->assertEquals('http://imgur.com/2dYfYlM.jpg', $this->parseUrl('http://imgur.com/2dYfYlM.jpg'));
        $this->assertEquals('http://imgur.com/2dYfYlM.jpg', $this->parseUrl('http://imgur.com/2dYfYlM'));

        // Test with www domain
        $this->assertEquals('http://www.imgur.com/2dYfYlM.jpg', $this->parseUrl('http://www.imgur.com/2dYfYlM'));

        // Test mediacru.sh
        $this->assertEquals('http://mediacru.sh/xzoyUYowDuVc.png', $this->parseUrl('https://mediacru.sh/xzoyUYowDuVc'));
    }

}