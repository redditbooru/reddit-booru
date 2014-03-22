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
        public static function assignImagesToPost($images, $post) {
            $images = is_array($images) ? $images : [ $images ];
            $values = [];
            $params = [ ':postId' => $post->id ];

            for ($i = 0, $count = count($images); $i < $count; $i++) {
                $values[] = '(:postId, :image' . $i . ')';
                $params[':image' . $i] = $images[$i]->id;
            }

            $query = 'INSERT INTO post_images VALUES ' . implode(', ', $values);
            return null !== Lib\Db::Query($query, $params);
        }

    }

}