<?php

namespace Api {

    use Lib;

    class PostImages {

        /**
         * Assigns an image or array of images to a post
         * @param mixed $images Array of image objects or single image object to assign
         * @param Post $post Post object to assign images to
         * @return boolean Success of the insert operation
         */
        public static function assignImagesToPost($images, Post $post) {
            $images = is_array($images) ? $images : [ $images ];
            $values = [];
            $params = [ ':postId' => $post->id ];

            for ($i = 0, $count = count($images); $i < $count; $i++) {
                $values[] = '(:image' . $i . ', :postId)';
                $params[':image' . $i] = $images[$i]->id;
            }

            $query = 'INSERT INTO post_images VALUES ' . implode(', ', $values);
            return null !== Lib\Db::Query($query, $params);
        }

        /**
         * Returns all images associated to a post
         * @param Post $post Post object to fetch images for
         * @return array Array of image objects associated to the post
         */
        public static function getImagesForPost(Post $post) {
            return Lib\Cache::getInstance()->fetch(function() use ($post) {
                $query = 'SELECT i.* FROM `post_images` pi INNER JOIN `images` i ON i.`image_id` = pi.`image_id` WHERE pi.`post_id` = :postId';
                $result = Lib\Db::Query($query, [ ':postId' => $post->id ]);
                $retVal = [];

                if ($result && $result->count) {
                    while ($row = Lib\Db::Fetch($result)) {
                        $retVal[] = new Image($row);
                    }
                }

                return $retVal;

            }, 'Api:PostImages:getImagesForPost_' . $post->id, CACHE_LONG);
        }

        /**
         * Deletes all current associations for a post and then recreates them from the provided array of image IDs
         */
        public static function rebuildPostAssociations($images, Post $post) {
            Lib\Db::Query('DELETE FROM `post_images` WHERE `post_id` = :postId', [ ':postId' => $post->id ]);
            return self::assignImagesToPost($images, $post);
        }

    }

}