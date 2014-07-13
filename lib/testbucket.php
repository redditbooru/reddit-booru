<?php

namespace Lib {

    define('TEST_BUCKET_CACHE_KEY', 'bucket_tests');
    define('DEFAULT_TEST_VALUE', 'control');

    class TestBucket {

        private static $_tests;
        private static $_initialized = false;
        private static $_seed = false;

        /**
         * Loads the test buckets and sets the bucket seed
         */
        public static function initialize($seed = null) {

            if (!self::$_initialized) {
                self::$_tests = Cache::fetch(function() {
                    return json_decode(@file_get_contents('buckets.json'));
                }, TEST_BUCKET_CACHE_KEY);
                self::$_initialized = true;

                // Add template helpers
                Display::addHelper('inTestBucket', function($template, $context, $args, $source) {
                    $args = explode(' ', $args);
                    $argc = count($args);

                    if ($argc === 2) {
                        for ($i = 0; $i < $argc; $i++) {

                            // Check for literal value vs template variable
                            if (strpos($args[$i], '"') === 0) {
                                $args[$i] = str_replace('"', '', $args[$i]);
                            } else {
                                $args[$i] = $context->get($args[$i]);
                            }

                        }

                        if (self::get($args[0]) == $args[1]) {
                            return $template->render($context);
                        }

                    }

                });

            }

            if ($seed || !self::$_seed) {
                self::$_seed = $seed ?: (int) str_replace('.', '', $_SERVER['REMOTE_ADDR']);
            }

        }

        /**
         * Gets a test bucket value for the given seed
         */
        public static function get($key, $seed = null) {

            self::initialize();

            $seed = $seed ?: self::$_seed;
            $cacheKey = 'test_' . $key . '_' . $seed;

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
            }, $cacheKey);
        }

    }

}