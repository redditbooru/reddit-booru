<?php

namespace Api {
    
    class ImageStats {

        /**
         * Image ID
         */
        public $imageId;

        /**
         * Number of times an image has been downloaded
         */
        public $count;

        /**
         * Array of hits by hour
         */
        public $hours;

        /**
         * Fetches the stats records for an image
         */
        public static getStatsForImage($imageId) {

        }

        /**
         * Merges an array of times/hits into the current image stats records
         */
        public mergeImageStats($imageId, $hours) {

        }

    }

}