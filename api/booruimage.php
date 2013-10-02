<?php

namespace Api {

    use Lib;

    class BooruImage extends Image {

        protected $_dbTable = 'booru_images';
        protected $_dbPrimaryKey = 'id';
        protected $_dbMap = [
            'id' => 'bi_id',
            'externalId' => 'bi_external_id',
            'dateCreated' => 'bi_date',
            'tags' => 'bi_tags',
            'source' => 'bi_source',
            'imageUrl' => 'bi_image_url',
            'rating' => 'bi_rating',
            'histR1' => 'bi_hist_r1',
            'histR2' => 'bi_hist_r2',
            'histR3' => 'bi_hist_r3',
            'histR4' => 'bi_hist_r4',
            'histG1' => 'bi_hist_g1',
            'histG2' => 'bi_hist_g2',
            'histG3' => 'bi_hist_g3',
            'histG4' => 'bi_hist_g4',
            'histB1' => 'bi_hist_b1',
            'histB2' => 'bi_hist_b2',
            'histB3' => 'bi_hist_b3',
            'histB4' => 'bi_hist_b4'
        ];

        public $id;
        public $externalId;
        public $dateCreated;
        public $tags;
        public $source;
        public $imageUrl;
        public $rating;
        public $histR1;
        public $histR2;
        public $histR3;
        public $histR4;
        public $histG1;
        public $histG2;
        public $histG3;
        public $histG4;
        public $histB1;
        public $histB2;
        public $histB3;
        public $histB4;

        /**
         * Creates a BooruImage from an image
         */
        public static function createFromImage($imageUrl) {
            $retVal = null;
            $image = Image::createFromImage($imageUrl);
            if ($image) {
                $retVal = new BooruImage();
                
            }
            return $retVal;
        }

    }

}