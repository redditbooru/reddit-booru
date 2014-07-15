<?php

namespace Api {

    use Lib;
    use stdClass;
    use OAuth2;

    // Ten minute expiry for vote information
    define('VOTE_CACHE_EXPIRY', 60 * 10);

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
         * Attempts to retrieve the user from the database and, failing that, reddit
         * @param String $userName The user name to get
         * @param String $data Data to use to create the user if it doesn't exist. Otherwise, a call to the reddit API is used
         */
        public function getByName($userName, $data = null) {
            $cacheKey = 'User::GetByName_' . $userName;
            $retVal = Lib\Cache::Get($cacheKey);

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
                Lib\Cache::Set($cacheKey, $retVal);
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
            $auth = new OAuth2\Strategy\AuthCode($client);
            return $auth->authorizeUrl([
                'scope' => 'identity,vote,read',
                'state' => md5(rand()),
                'duration' => 'permanent',
                'redirect_uri' => REDDIT_OAUTH_HANDLER
            ]);
        }

        /**
         * OAuth2 response handler
         */
        public static function authenticateUser($code) {
            $retVal = false;
            $client = self::_createOAuth2();
            $auth = new OAuth2\Strategy\AuthCode($client);

            try {
                $token = $auth->getToken($code, [ 'redirect_uri' => REDDIT_OAUTH_HANDLER ]);
                if ($token) {
                    $response = $token->get('https://oauth.reddit.com/api/v1/me.json');
                    if ($response) {
                        $data = json_decode($response->body());

                        if (isset($data->name)) {
                            $user = self::getByName($data->name);
                            if ($user) {
                                $user->refreshToken = $token->getRefreshToken();
                                $user->token = $token;
                                // subtract a minute to safely account for any latency
                                $user->tokenExpires = time() + $token->getExpiresIn();
                                $user->saveUserSession();
                            }
                        }

                    }
                }
            } catch (Exception $e) {

            }

            return $retVal;
        }

        /**
         * Returns an auth token refreshing when needed
         */
        public function getAuthToken() {
            $retVal = null;
            if (time() < $this->tokenExpires) {
                $retVal = $this->token;
            } else {
                try {
                    $client = self::_createOAuth2();
                    $token = $client->getToken([
                        'client_id' => REDDIT_TOKEN,
                        'client_secret' => REDDIT_SECRET,
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $this->refreshToken,
                        'scope' => 'identity,vote,read',
                        'state' => md5(rand()),
                        'duration' => 'permanent',
                        'redirect_uri' => REDDIT_OAUTH_HANDLER
                    ]);

                    if ($token) {
                        $retVal = $token;
                        $this->token = $token;
                        $this->tokenExpires = time() + $token->getExpiresIn();
                        $this->saveUserSession();
                    }

                } catch (Exception $e) {
                    // do nothing for now
                }
            }
            return $retVal;
        }

        private static function _createOAuth2() {
            return new OAuth2\Client(REDDIT_TOKEN, REDDIT_SECRET, [
                'site' => 'https://ssl.reddit.com/api/v1',
                'authorize_url' => '/authorize',
                'token_url' => '/access_token'
            ]);
        }

        private static function _createUserFromRedditData($data) {
            $retVal = new User();
            $retVal->name = $data->name;
            $retVal->redditId = $data->id;
            $retVal->dateCreated = (int) $data->created_utc;
            $retVal->linkKarma = (int) $data->link_karma;
            $retVal->commentKarma = (int) $data->comment_karma;
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
        public function setVoteForPost($postId, $vote) {
            $obj = new stdClass;
            $obj->vote = $vote;
            $obj->expires = time() + VOTE_CACHE_EXPIRY;
            $this->voteData[$postId] = $obj;
        }

        /**
         * Writes the vote data to session
         */
        public function saveUserSession() {
            Lib\Session::set('user', $this);
        }

        public function vote($id, $dir) {
            if ($id && $dir) {

                // Check for vote constraints
                if ($dir >= -1 && $dir <= 1) {
                    $token = $this->getAuthToken();
                    if ($token) {
                        $response = $token->post('https://oauth.reddit.com/api/vote', [ 'body' => implode('&', [ 'id' => 't3_' . $id, 'dir' => $dir ]) ]);
                        $this->setVoteForPost($postId, $dir);
                    }
                }

            }
        }

    }

}