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
            'caption' => 'image_caption',
            'sourceUrl' => 'image_source',
            'type' => 'image_type',
            'sourceId' => 'source_id',
            'sourceName' => 'source_name',
            'title' => 'post_title',
            'keywords' => 'post_keywords',
            'nsfw' => 'post_nsfw',
            'dateCreated' => 'post_date',
            'externalId' => 'post_external_id',
            'score' => 'post_score',
            'visible' => 'post_visible',
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
         * Image caption
         */
        public $caption;

        /**
         * Source URL
         */
        public $sourceUrl;

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
         * Whether the post has been deleted or removed
         */
        public $visible;

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
                $this->nsfw = (int) $this->nsfw === 1;
                $this->visible = (int) $this->visible === 1;
                $this->cdnUrl = Image::generateFilename($this->imageId, $this->type);
                $this->age = time() - $this->dateCreated;
                $this->sourceName = Source::formatSourceName($this->sourceName);
                // TODO - models shouldn't be talking to controllers
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
                $query .= 'post_id, image_id, image_width, image_height, image_caption, image_source, image_type, source_id, source_name, post_title';
                $query .= ', post_keywords, post_nsfw, post_date, post_external_id, post_score, post_visible, user_id, user_name) ';
                $query .= 'SELECT p.post_id, i.image_id, i.image_width, i.image_height, i.image_caption, i.image_source, i.image_type, p.source_id, s.source_name';
                $query .= ', p.post_title, p.post_keywords, p.post_nsfw, p.post_date, p.post_external_id, p.post_score, p.post_visible, p.user_id, u.user_name ';
                $query .= 'FROM post_images x INNER JOIN images i ON i.image_id = x.image_id INNER JOIN posts p ON p.post_id = x.post_id ';
                $query .= 'LEFT JOIN sources s ON s.source_id = p.source_id LEFT JOIN users u ON u.user_id = p.user_id WHERE x.post_id = :id';
                $retVal = null !== Lib\Db::Query($query, [ ':id' => $id ]);

            }

            return $retVal;

        }

        /**
         * Updates denormalized data for a post based upon source of truth tables
         */
        public static function updateDenormalizedPostData($id) {
            return null !== Lib\Db::Query('CALL `proc_UpdateDenormalizedPostData` (:id)', [ ':id' => $id ]);
        }

        /**
         * Updates all denormalized posts and the parent post with new data
         * @return boolean Success of update operation
         */
        public function updateAll($post) {

            $retVal = null;

            // Update the parent first
            $post->id = $post->id ?: $this->postId;
            if ($post->sync()) {
                $set = [];
                $params = [ ':postId' => $this->postId ];

                // Copy over post related iformation to the denormalized entry
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
         * Searches for posts by image
         */
        public static function reverseImageSearch($vars) {

            $image = Lib\Url::Get('image', null, $vars);
            $file = Lib\Url::Get('imageUri', null, $vars);
            $count = Lib\Url::GetInt('count', 5, $vars);
            $sources = Lib\Url::Get('sources', null, $vars);
            $getCount = Lib\Url::GetBool('getCount', $vars);
            $maxRating = Lib\Url::Get('maxRating', 0, $vars);

            // If an image object was passed in, serialize and hash it for the cache key
            if ($image instanceof Image) {
                $vars['image'] = md5(json_encode($image));
            }

            $cacheKey = 'PostData_reverseImageSearch_' . implode('_', $vars);
            $retVal = Lib\Cache::Get($cacheKey);

            if ((null != $file || $image instanceof Image) && false === $retVal) {

                $post = new PostData();

                if (!($image instanceof Image)) {
                    $image = Image::createFromUrl($file, false);
                }

                if (null !== $image) {

                    $query = 'SELECT pd.`' . join('`, pd.`', array_values($post->_dbMap)) . '`';

                    if ($getCount) {
                        $query .= ', (SELECT COUNT(1) FROM images WHERE post_id = p.post_id) AS count';
                    }

                    $params = [];
                    $query .= ', (';
                    for ($i = 1; $i <= HISTOGRAM_BUCKETS; $i++) {
                        $prop = 'histR' . $i;
                        $params[':red' . $i] = $image->$prop;
                        $prop = 'histG' . $i;
                        $params[':green' . $i] = $image->$prop;
                        $prop = 'histB' . $i;
                        $params[':blue' . $i] = $image->$prop;
                        $query .= 'ABS(image_hist_r' . $i . ' - :red' . $i . ') + ABS(image_hist_g' . $i . ' - :green' . $i . ') + ABS(image_hist_b' . $i . ' - :blue' . $i . ') + ';
                    }
                    $query .= ' + 0) AS distance FROM `' . $post->_dbTable . '` pd INNER JOIN `images` i ON i.`image_id` = pd.`image_id` ';

                    if ($sources) {
                        $innerQuery .= 'WHERE ';
                        $sources = !is_array($sources) ? explode(',', $sources) : $sources;
                        $tmpList = [];
                        $i = 0;
                        foreach ($sources as $source) {
                            $params[':source' . $i] = $source;
                            $tmpList[] = ':source' . $i;
                            $i++;
                        }
                        $query .= ' source_id IN (' . implode(',', $tmpList) . ') ';
                    }
                    $query .= 'ORDER BY distance LIMIT ' . ($count * 2);

                    $result = Lib\Db::Query($query, $params);

                    $time = time();
                    if ($result && $result->count) {
                        $retVal = [];
                        while($row = Lib\Db::Fetch($result)) {
                            $obj = new PostData($row);
                            $obj->distance = (float) $row->distance;
                            $retVal[] = $obj;
                        }
                    }

                }

                Lib\Cache::Set($cacheKey, $retVal);

            }

            return $retVal;

        }

        /**
         * Returns the generated profile information for the user
         */
        public static function getUserProfile($user) {
            return Lib\Cache::fetch(function() use ($user) {

                $retVal = User::queryReturnAll([ 'name' => $user ]);
                if ($retVal) {

                    $retVal = $retVal[0];
                    $retVal->postedOn = self::_getUserPostedSources($retVal->id);
                    $retVal->stats = self::_getUserCounts($retVal->id);
                    $retVal->age = time() - $retVal->stats->firstPostDate;
                    $retVal->avatar = self::_getAvatarImage($retVal->id);

                    $retVal->favorites = [];
                    $keywords = Controller\Stats::getKeywordRanks([ 'user' => $user ]);
                    $i = 0;
                    foreach ($keywords as $keyword => $count) {

                        $result = PostData::queryReturnAll([
                            'userId' => $retVal->id,
                            'keywords' => [ 'like' => '%' . str_replace(' ', '%', $keyword) . '%' ]
                        ] , [
                            'score' => 'desc'
                        ], 1);

                        $obj = new stdClass;
                        $obj->title = $keyword;
                        $obj->count = $count;
                        $obj->thumb = $result[0]->thumb;
                        $retVal->favorites[] = $obj;

                        $i++;
                        if ($i > 4) {
                            break;
                        }
                    }

                }

                return $retVal;

            }, 'UserProfile_' . $user, CACHE_LONG);
        }

        /**
         * Returns a list of all the subs a user has posted on
         */
        private static function _getUserPostedSources($userId) {
            $retVal = null;
            $result = Lib\Db::Query('SELECT DISTINCT source_id, source_name FROM post_data WHERE user_id = :userId', [ ':userId' => $userId ]);
            if ($result) {
                $retVal = [];
                while ($row = Lib\Db::Fetch($result)) {
                    $source = new Source($row);
                    $source->name = str_replace('r/', '', $source->name);
                    $retVal[] = $source;
                }
            }
            return $retVal;
        }

        /**
         * Returns various numerical statistics for a user
         */
        private static function _getUserCounts($userId) {
            $retVal = null;

            $queries = [
                'totalPosts' => 'SELECT COUNT(DISTINCT post_id) FROM post_data WHERE user_id = :userId',
                'totalImages' => 'SELECT COUNT(1) FROM post_data WHERE user_id = :userId',
                'totalScore' => 'SELECT SUM(post_score) FROM post_data WHERE user_id = :userId',
                'firstPostDate' => 'SELECT MIN(post_date) FROM post_data WHERE user_id = :userId'
            ];

            $query = [];
            foreach ($queries as $key => $val) {
                $query[] = '(' . $val . ') AS ' . $key;
            }
            $result = Lib\Db::Query('SELECT ' . join(',', $query), [ ':userId' => $userId ]);

            if ($result && $result->count) {
                $retVal = Lib\Db::Fetch($result);
            }

            return $retVal;
        }

        /**
         * Returns the user's avatar (an image randomly selected from their highest rated post)
         */
        private static function _getAvatarImage($userId) {
            $retVal = null;

            $result = Lib\Db::Query('SELECT * FROM post_data WHERE user_id = :userId ORDER BY post_score DESC, RAND() LIMIT 1', [ ':userId' => $userId ]);
            if ($result && $result->count) {
                $row = Lib\Db::Fetch($result);
                $retVal = new PostData($row);
                $retVal = $retVal->thumb;
            }

            return $retVal;
        }


        /**
         *
         */
        public static function getGallery($id) {

            $cacheKey = 'Api:PostData:getGallery_' . $id;
            $retVal = Lib\Cache::Get($cacheKey);

            if (false === $retVal) {

                // Get the post record
                $post = Post::getById($id);
                $retVal = [];
                if ($post) {

                    // If this post is linking to a different gallery, do a redirect to the original
                    if (preg_match('/redditbooru\.com\/gallery\/([\w]+)\/?([\w]+)?/', $post->link, $match)) {
                        $urlId = $match[1];
                        $new = isset($match[2]);
                        $urlId = $new ? base_convert($urlId, 36, 10) : $urlId;

                        if ($id !== $urlId) {
                            header('Location: ' . $post->link);
                            exit;
                        }
                    }

                    // Get the post images
                    $images = PostData::query([ 'postId' => $id ]);
                    if ($images && $images->count) {
                        while ($row = Lib\Db::Fetch($images)) {
                            $retVal[] = new PostData($row);
                        }
                    }

                }

                Lib\Cache::Set($cacheKey, $retVal);

            }

            return $retVal;

        }

    }

}