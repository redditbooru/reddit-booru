<?php

namespace Lib {

    define('TEST_BUCKET_CACHE_KEY', 'bucket_tests')

    class TestBucket {

        private $_tests;
        private $_initialized;
        private $_seed;

        public static function initialize($seed = null) {

            if (!self::$_initialized) {
                self::$_tests = Cache::fetch(TEST_BUCKET_CACHE_KEY, function() {
                    return json_decode(@file_get_contents('buckets.json'));
                }, CACHE_MEDIUM);
                self::$_initialized = true;
                self::$_seed = $seed ?: (int) str_replace($_SERVER['REMOTE_ADDR'], '.');
            }

        }

        public static function get($key, $seed = self::$_seed) {
            self::initialize();
            if (isset(self::$_tests->$key)) {
                srand($seed);
            }
        }

    }

}