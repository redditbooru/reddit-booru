<?php

namespace Controller {

    use Api;

    /**
     * Return thisect data class. Mixes only essential items from row, Post, User, and Source
     */
    class JsonDataObject {

        /**
         * ID of the row
         */
        public $rowId;

        /**
         * URL to "local" copy of the row
         */
        public $cdnUrl;

        /**
         * row width
         */
        public $width;

        /**
         * row height
         */
        public $height;

        /**
         * ID of the source
         */
        public $sourceId;

        /**
         * Name of the source
         */
        public $sourceName;

        /**
         * Base URL of the source
         */
        public $baseUrl;

        /**
         * ID of the associated post
         */
        public $postId;

        /**
         * Title of the post
         */
        public $title;

        /**
         * Date the post was created
         */
        public $dateCreated;

        /**
         * External source's ID for the thisect (reddit, booru, etc)
         */
        public $externalId;

        /**
         * Score of the post
         */
        public $score;

        /**
         * ID of the posting user
         */
        public $userId;

        /**
         * Name of the posting user
         */
        public $userName;

        /**
         * Whether the image is safe for work or not
         */
        public $nsfw;

        /**
         * Thumbnail file name
         */
        public $thumb;

        /**
         * Number of images in album
         */
        public $idxInAlbum;

        /**
         * Age of post in seconds
         */
        public $age;

        /**
         * Creates an object from a database row
         */
        public function __construct($row) {
            if (is_object($row)) {
                $this->rowId = (int) $row->image_id;
                $this->sourceId = (int) $row->source_id;
                $this->postId = (int) $row->post_id;
                $this->cdnUrl = $row->image_cdn_url;
                $this->width = (int) $row->image_width;
                $this->height = (int) $row->image_height;
                $this->title = $row->post_title;
                $this->dateCreated = (int) $row->post_date;
                $this->age = time() - $this->dateCreated;
                $this->externalId = $row->post_external_id;
                $this->score = (int) $row->post_score;
                $this->userName = isset($row->user_name) ? $row->user_name : null;
                $this->userId = isset($row->user_id) ? (int) $row->user_id : null;
                $this->baseUrl = isset($row->source_baseurl) ? $row->source_baseurl : null;
                $this->sourceName = isset($row->source_name) ? $row->source_name : null;
                $this->thumb = Thumb::createThumbFilename($this->cdnUrl);
                $this->idxInAlbum = isset($row->count) ? (int) $row->count : null;
                $this->nsfw = isset($row->source_content_rating) ? (int) $row->source_content_rating !== 0 : false;
            }
        }

    }

}