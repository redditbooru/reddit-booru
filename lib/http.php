<?php

namespace Lib {
    
    class Http {

        public static function get($url) {
            return self::curl_get_contents($url);
        }

        /**
         * A drop in replacement for file_get_contents with some business logic attached
         * @param string $url Url to retrieve
         * @return string Data received
         */
        private static function curl_get_contents($url) {
            $c = curl_init($url);
            curl_setopt($c, CURLOPT_USERAGENT, HTTP_UA);
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
            
            // Not the most ethical thing, but fake a referer for pixiv to get around the 403
            if (strpos($url, 'pixiv.net')) {
                curl_setopt($c, CURLOPT_REFERER, 'http://pixiv.net');
            }
            
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_TIMEOUT, 15);
            $retVal = curl_exec($c);
            curl_close($c);

            return $retVal;
        }

    }

}