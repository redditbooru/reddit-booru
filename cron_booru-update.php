<?php

define('BOORU_POSTS_LIMIT', 100);

require('lib/aal.php');

function getBooruPage($page, $sourceUrl) {
    $url = $sourceUrl . 'index.php?page=dapi&s=post&q=index&limit=' . BOORU_POSTS_LIMIT . '&pid=' . ($page * BOORU_POSTS_LIMIT);
    return simplexml_load_file($url);
}

function convertPixivLink($link) {
    $retVal = $link;
    if (preg_match('/([\d]+)\.([\w]{3,4})\b/i', $link, $matches)) {
        $retVal = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $matches[1];
    }
    return $retVal;
}

$result = Lib\Db::query('SELECT source_id, source_name, source_baseurl FROM sources WHERE source_type = "booru"');
while ($row = Lib\Db::fetch($result)) {
    
    // We loop until we find an image that's already been inserted into the database
    $check = true;
    $page = 0;
    
    while ($check) {
        $listing = getBooruPage($page, $row->source_baseurl);
        if ($listing) {
            foreach ($listing->post as $post) {
            
                $attr = $post->attributes();
                echo $attr->id, ': ';
                    
                if (strlen($attr->source) > 0) {
                
                    $dbPost = Api\Post::getByExternalId($attr->id, $row->source_id);
                
                    if ($dbPost) {
                        echo 'ALREADY ADDED';
                        $check = true;
                    } else {
                        // Download and scan the image
                        $image = Api\Image::createFromImage((string) $attr->sample_url, false, $row->source_id);
                        if ($image) {
                            $dbPost = new Api\Post();
                            $dbPost->sourceId = $row->source_id;
                            $dbPost->externalId = (string) $attr->id;
                            $dbPost->dateCreated = strtotime($attr->created_at);
                            $dbPost->dateUpdated = time();
                            $dbPost->title = (string) $attr->tags;
                            $dbPost->keywords = (string) $attr->tags;
                            $dbPost->link = (string) $attr->source;
                            $dbPost->link = strpos($dbPost->link, 'pixiv.net') !== false ? convertPixivLink($dbPost->link) : $dbPost->link;
                            $dbPost->processed = true;
                            $dbPost->meta = new stdClass;
                            $dbPost->meta->tags = (string) $attr->tags;
                            $dbPost->meta->rating = (string) $attr->rating;
                            $dbPost->meta->source = (string) $attr->source;
                            
                            if ($dbPost->sync()) {
                                $image->postId = $dbPost->id;
                                $image->sync();
                                echo 'DONE';
                            } else {
                                echo 'ISSUE SYNCING POST';
                            }
                            
                        } else {
                            echo 'BAD IMAGE';
                        }
                    } 
                } else {
                    echo 'NO SOURCE';
                }
                
                echo PHP_EOL;
                
            }
        }
        $page++;
    }

}

/*

<post 
height="850" 
score="0" 
file_url="http://cdn1.gelbooru.com/images/1607/a3428d874639ad50d8669ba7fa6957bc.jpg" 
parent_id="" 
sample_url="http://cdn1.gelbooru.com/images/1607/a3428d874639ad50d8669ba7fa6957bc.jpg" 
sample_width="619" 
sample_height="850" 
preview_url="http://cdn1.gelbooru.com/thumbs/1607/thumbnail_a3428d874639ad50d8669ba7fa6957bc.jpg" 
rating="s" 
tags=" 1girl 39 aqua_eyes aqua_hair belt character_name hat hatsune_miku high_heels jewelry long_hair matoki_misa microphone musical_note necklace open_mouth outstretched_arm shoes sitting skirt socks solo twintails vocaloid " 
id="1818628" 
width="619" 
change="1362841717" 
md5="a3428d874639ad50d8669ba7fa6957bc" 
creator_id="6498" 
has_children="false" 
created_at="Sat Mar 09 09:08:36 -0600 2013" 
status="active" 
source="http://i2.pixiv.net/img34/img/matoki/34115888.jpg" 
has_notes="false" 
has_comments="false" 
preview_width="109" 
preview_height="150"/>

http://i2.pixiv.net/img20/img/sennyuuyuuji/34116483.jpg
http://www.pixiv.net/member_illust.php?mode=medium&illust_id=34115888

*/