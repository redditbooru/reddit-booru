<?php

namespace Api {

    use Controller;
    use Lib;
    use stdClass;

    /**
     * Return thisect data class. Mixes only essential items from row, Post, User, and Source
     */
    class PostData extends Lib\Dal {

        protected $_dbTable = 'post_data';
        protected $_dbPrimaryKey = 'id';
        protected $_dbMap = [
            'id' => 'pd_id',
            'imageId' => 'image_id',
            'postId' => 'post_id',
            'width' => 'image_width',
            'height' => 'image_height',
            'type' => 'image_type',
            'sourceId' => 'source_id',
            'sourceName' => 'source_name',
            'title' => 'post_title',
            'keywords' => 'post_keywords',
            'nsfw' => 'post_nsfw',
            'dateCreated' => 'post_date',
            'externalId' => 'post_external_id',
            'score' => 'post_score',
            'userId' => 'user_id',
            'userName' => 'user_name'
        ];

        /**
         * ID of the row
         */
        public $id;

        /**
         * ID of the image
         */
        public $imageId;

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
         * Image type (extension)
         */
        public $type;

        /**
         * ID of the source
         */
        public $sourceId;

        /**
         * Name of the source
         */
        public $sourceName;

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
        public function __construct($row = null) {
            if (is_object($row)) {
                $this->copyFromDbRow($row);
                $this->id = (int) $this->id;
                $this->imageId = (int) $this->imageId;
                $this->postId = (int) $this->postId;
                $this->userId = (int) $this->userId;
                $this->dateCreated = (int) $this->dateCreated;
                $this->sourceId = (int) $this->sourceId;
                $this->width = (int) $this->width;
                $this->height = (int) $this->height;
                $this->nsfw = $this->nsfw === 1;
                $this->cdnUrl = Image::generateFilename($this->imageId, $this->type);
                $this->age = time() - $this->dateCreated;
                $this->thumb = Controller\Thumb::createThumbFilename($this->cdnUrl);
            }
        }

        /**
         * Given an post ID, creates a denormalized record in the database
         * @return boolean Success of insert operation
         */
        public static function denormalizeForPost($id) {

            $retVal = null;

            if (is_numeric($id)) {

                $query =  'INSERT INTO post_data (';
                $query .= 'post_id, image_id, image_width, image_height, image_type, source_id, source_name, post_title';
                $query .= ', post_keywords, post_nsfw, post_date, post_external_id, post_score, user_id, user_name) ';
                $query .= 'SELECT p.post_id, i.image_id, i.image_width, i.image_height, i.image_type, p.source_id, s.source_name';
                $query .= ', p.post_title, p.post_keywords, p.post_nsfw, p.post_date, p.post_external_id, p.post_score, p.user_id, u.user_name ';
                $query .= 'FROM post_images x INNER JOIN images i ON i.image_id = x.image_id INNER JOIN posts p ON p.post_id = x.post_id ';
                $query .= 'INNER JOIN sources s ON s.source_id = p.source_id INNER JOIN users u ON u.user_id = p.user_id WHERE x.post_id = :id';
                $retVal = null !== Lib\Db::Query($query, [ ':id' => $id ]);

            }

            return $retVal;

        }

        /**
         * Updates all denormalized posts and the parent post with new data
         * @return boolean Success of update operation
         */
        public function updateAll($post) {

            $retVal = null;

            // Update the parent first
            $post->id = $post->id ?: $this->postId;
            $post->dateUpdated = time();
            if ($post->sync()) {
                $set = [];
                $params = [ ':postId' => $this->postId ];
                foreach ($post as $property => $value) {
                    if (isset($this->_dbMap[$property]) && $property !== 'id') {
                        $set[] = '`' . $this->_dbMap[$property] . '` = :' . $property;
                        $params[':' . $property] = $value;
                    }
                }
                $retVal = null !== Lib\Db::Query('UPDATE `' . $this->_dbTable . '` SET ' . implode(', ', $set) . ' WHERE `' . $this->_dbMap['postId'] . '` = :postId', $params);
            }

            return $retVal;

        }

        /**
         * Returns the generated profile information for the user
         */
        public static function getUserProfile($user) {
            return Lib\Cache::fetch(function() use ($user) {

                $userObj = User::queryReturnAll([ 'name' => $user ]);
                if ($userObj) {

                    $userObj = $userObj[0];
                    $retVal = new stdClass;

                    // Get the subs this user has posted on
                    $result = Lib\Db::Query('SELECT DISTINCT source_id, source_name FROM post_data WHERE user_name = :userName', [ ':userName' => $userObj->name ]);
                    if ($result) {
                        $retVal->postedOn = [];
                        while ($row = Lib\Db::Fetch($result)) {
                            $retVal->postedOn[] = new Source($row);
                        }
                    }

                }

                return $retVal;

            }, 'UserProfile_' . $user, CACHE_LONG);
        }

    }

}