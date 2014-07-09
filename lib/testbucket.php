<?php

namespace Lib {

    define('TEST_BUCKET_CACHE_KEY', 'bucket_tests');
    define('DEFAULT_TEST_VALUE', 'control');

    class TestBucket {

        private static $_tests;
        private static $_initialized = false;
        private static $_seed;

        /**
         * Loads the test buckets and sets the bucket seed
         */
        public static function initialize($seed = null) {

            if (!self::$_initialized) {
                self::$_tests = Cache::fetch(function() {
                    return json_decode(@file_get_contents('buckets.json'));
                }, TEST_BUCKET_CACHE_KEY);
                self::$_initialized = true;
            }

            self::$_seed = $seed ?: (int) str_replace('.', '', $_SERVER['REMOTE_ADDR']);

        }

        /**
         * Gets a test bucket value for the given seed
         */
        public static function get($key, $seed = null) {

            $seed = $seed ?: self::$_seed;

            self::initialize();
            return Cache::fetch(function() use ($key, $seed) {
                $retVal = DEFAULT_TEST_VALUE;
                $found = false;

                if (isset(self::$_tests->$key)) {
                    $test = self::$_tests->$key;
                    if (isset($test->whiteLists)) {
                        foreach ($test->whiteLists as $whiteList) {
                            if (in_array($seed, $whiteList->ids)) {
                                $retVal = $whiteList->value;
                                $found = true;
                                break;
                            }
                        }
                    }

                    if (!$found) {
                        srand($seed);
                        $rand = rand() % 100;
                        $percentTotal = 0;
                        foreach ($test->ramps as $ramp) {
                            $percentTotal += $ramp->percent;
                            if ($rand <= $percentTotal) {
                                $retVal = $ramp->value;
                                break;
                            }
                        }
                    }
                }

                return $retVal;
            }, 'test_' . $key . '_' . $seed);
        }

    }

}