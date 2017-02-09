<?php

namespace Api {

    use Lib;
    use stdClass;

    // For posts made within the last 24 hours, cache for 3 hours
    define('VOTE_SHORT_CACHE', 3600 * 3);

    // For anything older, two days
    define('VOTE_LONG_CACHE', 64800 * 2);

    define('USER_CSRF_ENTRPOY', 8);

    class User extends Lib\Dal {

        /**
         * Database mappers
         */
        protected $_dbTable = 'users';
        protected $_dbPrimaryKey = 'id';
        protected $_dbMap = [
            'id' => 'user_id',
            'name' => 'user_name',
            'redditId' => 'user_reddit_id',
            'dateCreated' => 'user_date_created',
            'linkKarma' => 'user_link_karma',
            'commentKarma' => 'user_comment_karma',
            'hasAvatar' => 'user_avatar'
        ];

        /**
         * User's database ID
         */
        public $id;

        /**
         * User's name
         */
        public $name;

        /**
         * Reddit ID for the user
         */
        public $redditId;

        /**
         * Date the account was created
         */
        public $dateCreated;

        /**
         * Link karma
         */
        public $linkKarma;

        /**
         * Comment karma
         */
        public $commentKarma;

        /**
         * Whether the user has an avatar or not
         */
        public $hasAvatar;

        /**
         * Reddit auth token
         */
        private $token;

        /**
         * Expiration time for the auth token
         */
        private $tokenExpires;

        /**
         * Reddit refresh token
         */
        private $refreshToken;

        /**
         * Array of post votes
         */
        private $voteData = [];

        /**
         * CSRF token
         */
        public $csrfToken;

        /**
         * Attempts to retrieve the user from the database and, failing that, reddit
         * @param String $userName The user name to get
         * @param String $data Data to use to create the user if it doesn't exist. Otherwise, a call to the reddit API is used
         */
        public function getByName($userName, $data = null) {
            $cache = Lib\Cache::getInstance();
            $cacheKey = 'User::GetByName_' . $userName;
            $retVal = $cache->get($cacheKey);

            if (!$retVal) {
                $result = Lib\Db::Query('SELECT * FROM users WHERE user_name = :userName', [ ':userName' => $userName ]);
                if ($result->count > 0) {
                    $retVal = new User(Lib\Db::Fetch($result));
                } else {
                    if (!$data) {
                        $file = @file_get_contents('http://www.reddit.com/user/' . $userName . '/about.json');
                        if ($file) {
                            $user = json_decode($file);
                            if (isset($user->data)) {
                                $retVal = self::_createUserFromRedditData($user->data);
                            }

                        }
                    } else {
                        $retVal = self::_createUserFromRedditData($data);
                    }

                    if ($retVal) {
                        if (!$retVal->sync()) {
                            $retVal = null;
                        }
                    }

                }
                $cache->set($cacheKey, $retVal);
            }

            return $retVal;
        }

        /**
         * Returns the user's default sources
         */
        public static function getUserSources() {
            // Always defaults to awwnime
            $retVal = Lib\Url::Get('sources', 1, $_COOKIE);
            return explode(',', $retVal);
        }

        public static function getCurrentUser() {
            return Lib\Session::get('user');
        }

        /**
         * Returns the login URL for OAuth2 authentication
         */
        public static function getLoginUrl($redirect = '') {
            $client = self::_createOAuth2();
            return $client->getLoginUrl('permanent', [
                'identity',
                'vote',
                'read'
            ]);
        }

        /**
         * OAuth2 response handler
         */
        public static function authenticateUser($code) {
            $retVal = false;
            $client = self::_createOAuth2();

            if ($client->getToken($code)) {
                $data = $client->call('api/v1/me');
                if ($data && isset($data->name)) {
                    $user = self::getByName($data->name);
                    if ($user) {
                        $user->csrfToken = bin2hex(openssl_random_pseudo_bytes(USER_CSRF_ENTRPOY));
                        $user->saveUserSession($client);
                    }
                }
            }

            return $retVal;
        }

        private static function _createOAuth2(User $user = null) {
            $retVal = new Lib\RedditOAuth(REDDIT_TOKEN, REDDIT_SECRET, HTTP_UA, REDDIT_OAUTH_HANDLER);

            // If we have stashed tokens, set those up as well
            if ($user && $user->token && $user->refreshToken && $user->tokenExpires) {
                $retVal->setToken($user->token);
                $retVal->setRefreshToken($user->refreshToken);
                $retVal->setExpiration($user->tokenExpires);
            }

            return $retVal;
        }

        private static function _createUserFromRedditData($data) {
            $retVal = new User();
            $retVal->name = $data->name;
            $retVal->redditId = $data->id;
            $retVal->dateCreated = (int) $data->created_utc;
            $retVal->linkKarma = (int) $data->link_karma;
            $retVal->commentKarma = (int) $data->comment_karma;
            return $retVal;
        }

        /**
         * Returns whether the current user has voted on a post
         */
        public function getVoteForPost($postId) {
            $retVal = isset($this->voteData[$postId]) ? $this->voteData[$postId] : false;
            if ($retVal) {
                if ($retVal->expires < time()) {
                    $retVal = false;
                } else {
                    $retVal = $retVal->vote;
                }
            }
            return $retVal;
        }

        /**
         * Saves a user's vote for a post
         */
        public function setVoteForPost(Post $post, $vote) {
            $within24Hours = $post->dateCreated < time() - 64800;
            $obj = new stdClass;
            $obj->vote = $vote;
            $obj->expires = time() + $within24Hours ? VOTE_SHORT_CACHE : VOTE_LONG_CACHE;
            $this->voteData[$post->externalId] = $obj;
        }

        /**
         * Saves the user session or clears it
         */
        public function saveUserSession($client = null, $end = false) {
            // Check for token changes
            if ($client) {
                $this->token = $client->getToken();
                $this->tokenExpires = $client->getExpiration();
                $this->refreshToken = $client->getRefreshToken();
            }

            Lib\Session::set('user', $end ? null : $this);
        }

        public function logout() {
            $this->saveUserSession(null, true);
        }

        public function vote($postId, $dir) {
            if ($postId && is_numeric($dir)) {
                // Check for vote constraints
                if ($dir >= -1 && $dir <= 1) {
                    $client = self::_createOAuth2($this);
                    $row = Post::queryReturnAll([ 'externalId' => $postId ]);
                    if ($row) {
                        $response = $client->call('api/vote', [ 'id' => 't3_' . $postId, 'dir' => $dir ]);
                        $this->setVoteForPost($row[0], $dir);
                        $this->saveUserSession($client);
                    }
                }

            }
        }

    }

}