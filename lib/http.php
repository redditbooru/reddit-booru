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
            
            curl_setopt($c, CURLOPT_PROGRESSFUNCTION, 'self::_progress');
            curl_setopt($c, CURLOPT_NOPROGRESS, false);

            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_TIMEOUT, 15);
            curl_setopt($c, CURLOPT_ENCODING, 'gzip');
            $retVal = curl_exec($c);
            $effectiveUrl = curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
            curl_close($c);

            self::_requestComplete($effectiveUrl);

            return $retVal;
        }

        private static function _getCacheKey() {
            $client = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local';
            return 'curl_progress_' . $client;
        }

        /**
         * Returns the cached list of active downloads
         */
        public static function getActiveDownloads() {
            $cacheKey = self::_getCacheKey();
            $urls = Cache::Get($cacheKey);
            return $urls ?: [];
        }

        private static function _setActiveDownloads($urls) {
            Cache::Set(self::_getCacheKey(), $urls, CACHE_SHORT);
        }

        /**
         * A callback function to monitor cURLs download progress for a client
         */
        private static function _progress($c, $totalBytesDown, $bytesDownloaded, $totalBytesUp, $bytesUploaded) {
            $urls = self::getActiveDownloads();
            $url = curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
            $urls[$url] = $totalBytesDown > 0 ? $bytesDownloaded / $totalBytesDown : 0;
            self::_setActiveDownloads($urls);
        }

        /**
         * Cleanup operations for when a cURL request completes
         */
        private static function _requestComplete($url) {
            $urls = self::getActiveDownloads();
            if (isset($urls[$url])) {
                unset($urls[$url]);
                self::_setActiveDownloads($urls);
            }
        }

    }

}