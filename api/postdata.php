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
         * Post keywords
         */
        public $keywords;

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
            $experimental = Lib\Url::GetBool('experimental', $vars);

            // If an image object was passed in, serialize and hash it for the cache key
            if ($image instanceof Image) {
                $vars['image'] = md5(json_encode($image));
            }

            $cache = Lib\Cache::getInstance();
            $cacheKey = Lib\Cache::createCacheKey('Api::PostData::reverseImageSearch', [ 'image', 'imageUri', 'count', 'sources', 'getCount', 'maxRating', 'experimental' ], $vars);
            $retVal = $cache->get($cacheKey);

            if ((null != $file || $image instanceof Image) && false === $retVal) {

                $post = new PostData();

                if (!($image instanceof Image)) {
                    $image = Image::createFromUrl($file, false);
                }

                if (null !== $image) {

                    if ($sources) {
                        $sources = !is_array($sources) ? explode(',', $sources) : $sources;
                    }
                    $matchingImages = ImageLookup::reverseLookup($image, $sources, $count);

                    if ($matchingImages) {
                        // Save the distance to an ID for redecoration later
                        $imageDistances = [];
                        $imageIds = [];
                        foreach ($matchingImages as $imageMatch) {
                            $imageDistances[$imageMatch->imageId] = $imageMatch->distance;
                            $imageIds[] = $imageMatch->imageId;
                        }

                        $retVal = PostData::queryReturnAll([ 'imageId' => [ 'in' => $imageIds ], 'sourceId' => [ 'in' => $sources ] ], null);
                        foreach ($retVal as $post) {
                            $post->distance = $imageDistances[$post->imageId];
                        }
                        usort($retVal, function ($a, $b) {
                            return $a->distance < $b->distance ? -1 : 1;
                        });
                    }
                }

                $cache->set($cacheKey, $retVal);

            }

            return $retVal;

        }

        /**
         * Searches for posts by image
         */
        public static function reverseImageSearchLegacy($vars) {

            $image = Lib\Url::Get('image', null, $vars);
            $file = Lib\Url::Get('imageUri', null, $vars);
            $count = Lib\Url::GetInt('count', 5, $vars);
            $sources = Lib\Url::Get('sources', null, $vars);
            $getCount = Lib\Url::GetBool('getCount', $vars);
            $maxRating = Lib\Url::Get('maxRating', 0, $vars);
            $experimental = Lib\Url::GetBool('experimental', $vars);

            // If an image object was passed in, serialize and hash it for the cache key
            if ($image instanceof Image) {
                $vars['image'] = md5(json_encode($image));
            }

            $cache = Lib\Cache::getInstance();
            $cacheKey = Lib\Cache::createCacheKey('Api::PostData::reverseImageSearchLegacy', [
                'image',
                'imageUri',
                'count',
                'sources',
                'getCount',
                'maxRating',
                'experimental'
            ], $vars);
            $retVal = $cache->get($cacheKey);

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

                    if ($experimental) {
                        // Do the dHash version
                        $params = [ ':dHashR' => $image->dHashR, ':dHashG' => $image->dHashG, ':dHashB' => $image->dHashB ];
                        $query .= ', BIT_COUNT(image_dhashr ^ :dHashR) + BIT_COUNT(image_dhashg ^ :dHashG) + BIT_COUNT(image_dhashb ^ :dHashB) AS distance';
                    } else {
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
                        $query .= ' + 0) AS distance';
                    }

                    $query .= ' FROM `' . $post->_dbTable . '` pd INNER JOIN `images` i ON i.`image_id` = pd.`image_id` ';

                    $where = [];
                    if ($experimental) {
                        $where[] .= 'image_dhashr IS NOT NULL AND image_dhashg IS NOT NULL AND image_dhashb IS NOT NULL';
                    }

                    if ($sources) {
                        $sources = !is_array($sources) ? explode(',', $sources) : $sources;
                        $tmpList = [];
                        $i = 0;
                        foreach ($sources as $source) {
                            $params[':source' . $i] = $source;
                            $tmpList[] = ':source' . $i;
                            $i++;
                        }
                        $where[] = 'source_id IN (' . implode(',', $tmpList) . ')';
                    }

                    if ($where) {
                        $query .= 'WHERE ' . implode(' AND ', $where) . ' ';
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

                $cache->set($cacheKey, $retVal);

            }

            return $retVal;

        }

        /**
         * Returns the generated profile information for the user
         */
        public static function getUserProfile($user) {
            return Lib\Cache::getInstance()->fetch(function() use ($user) {

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
        public static function getGallery($id, $noRedirect = false) {
            $cache = Lib\Cache::getInstance();
            $cacheKey = 'Api:PostData:getGallery_' . $id;
            $retVal = $cache->get($cacheKey);

            if (false === $retVal) {

                // Get the post record
                $post = Post::getById($id);
                $retVal = [];
                if ($post) {

                    // If this post is linking to a different gallery, do a redirect to the original
                    $urlId = Post::getPostIdFromUrl($post->link);
                    if ($id != $urlId && !$noRedirect) {
                        header('Location: ' . $post->link);
                        exit;
                    }

                    // Get the post images
                    $images = PostData::query([ 'postId' => $id ]);
                    if ($images && $images->count) {
                        while ($row = Lib\Db::Fetch($images)) {
                            $retVal[] = new PostData($row);
                        }
                    }

                }

                $cache->set($cacheKey, $retVal);

            }

            return $retVal;

        }

        public static function invalidateCacheForGallery(Post $post) {
            $cache = Lib\Cache::getInstance();
            $cache->set('Api:PostData:getGallery_' . $post->id, false);
            $cache->set('Api:PostData:getUserGalleries_' . $post->userId, false);
        }

        /**
         * Decorates an array of PostData items with user voting data
         */
        public static function getVotesForPosts(Array $posts, User $user) {

            if (REDDIT_IS_DOWN) {
                return $posts;
            }

            $token = $user->getAuthToken();

            if ($token) {

                // Make a list of vote data that needs to be retrieved from reddit
                $ids = [];
                $postCache = [];
                foreach ($posts as $post) {
                    if ($post->externalId) {
                        $vote = $user->getVoteForPost($post->externalId);
                        if ($vote !== false) {
                            $post->userVote = $vote;
                        } else {
                            $ids[$post->externalId] = true;
                            if (!isset($postCache[$post->externalId])) {
                                $postCache[$post->externalId] = [];
                            }
                            $postCache[$post->externalId][] = $post;
                        }
                    }
                }

                // Get the missing vote data
                if (count($ids) > 0) {
                    $url = 'https://oauth.reddit.com/by_id/t3_' . implode(',t3_', array_keys($ids)) . '/.json?limit=' . count($ids);
                    try {
                        $response = $token->get($url);
                        if ($response) {
                            $data = json_decode($response->body());
                            if (isset($data->data) && is_array($data->data->children)) {
                                foreach ($data->data->children as $post) {
                                    $vote = 0;
                                    $post = $post->data;

                                    if (isset($post->likes))  {
                                        if ($post->likes === null) {
                                            $vote = 0; // no vote
                                        } else if ($post->likes === false) {
                                            $vote = -1; // down vote
                                        } else {
                                            $vote = 1; // up vote
                                        }
                                    }

                                    // Update every post image for this reddit id
                                    foreach ($postCache[$post->id] as $postData) {
                                        $postData->userVote = $vote;
                                    }

                                    $post = Post::createFromRedditObject($post);
                                    $user->setVoteForPost($post, $vote);
                                }
                            }
                        }

                        $user->saveUserSession();

                    } catch (Exception $e) {
                        // do nothing
                    }
                }


            }

            return $posts;

        }

    }

}
