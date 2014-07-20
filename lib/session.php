<?php

namespace Lib {

    use stdClass;

    define('RBSESS', 'RBSESS_20140718');
    define('SESSION_EXPIRE', 86400 * 365); // session lasts for a year

    class Session {

        private static $_sess;
        private static $_id;

        public static function start() {
            self::$_id = Url::Get(RBSESS, null, $_COOKIE);
            if (!self::$_id) {
                self::$_id = bin2hex(openssl_random_pseudo_bytes(32));
                setcookie(RBSESS, self::$_id, time() + SESSION_EXPIRE, '/', 'redditbooru.com');
            }
            self::$_sess = Cache::Get(RBSESS . '_' . self::$_id, true);
        }

        public static function get($key) {
            $retVal = null;
            if (self::$_sess instanceof stdClass && isset(self::$_sess->$key)) {
                $retVal = self::$_sess->$key;
            }
            return $retVal;
        }

        public static function set($key, $value) {
            if (!self::$_sess instanceof stdClass) {
                self::$_sess = new stdClass;
            }
            self::$_sess->$key = $value;
            Cache::Set(RBSESS . '_' . self::$_id, self::$_sess, SESSION_EXPIRE);
        }

    }

}