<?php

namespace Api {

    use Lib;
    use stdClass;

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
            'token' => 'user_token',
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
         * Reddit OAuth refresh token
         */
        public $token;

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
         * Attempts to retrieve the user from the database and, failing that, reddit
         */
        public function getByName($userName) {
            $cacheKey = 'User::GetByName_' . $userName;
            $retVal = Lib\Cache::Get($cacheKey);

            if (!$retVal) {
                $result = Lib\Db::Query('SELECT * FROM users WHERE user_name = :userName', [ ':userName' => $userName ]);
                if ($result->count > 0) {
                    $retVal = new User(Lib\Db::Fetch($result));
                } else {
                    $file = @file_get_contents('http://www.reddit.com/user/' . $userName . '/about.json');
                    if ($file) {

                        $user = json_decode($file);
                        if (isset($user->data)) {
                            $retVal = new User();
                            $retVal->name = $user->data->name;
                            $retVal->redditId = $user->data->id;
                            $retVal->dateCreated = (int) $user->data->created_utc;
                            $retVal->linkKarma = (int) $user->data->link_karma;
                            $retVal->commentKarma = (int) $user->data->comment_karma;
                            $retVal->sync();
                            if (!$retVal->id) {
                                $retVal = null;
                            }
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

    }

}