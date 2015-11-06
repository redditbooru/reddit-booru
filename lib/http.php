<?php

namespace Lib {

    class Http {

        private $_url;

        /**
         * Holds the error message returned by cURL for the last transaction
         */
        private static $_lastError;
        public static function getLastError() {
            return self::$_lastError;
        }

        public static function get($url) {
            return self::curl_get_contents($url);
        }

        private function _get($url) {
            $this->_url = $url;
            $c = curl_init($url);
            curl_setopt($c, CURLOPT_USERAGENT, HTTP_UA);
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);

            // Not the most ethical thing, but fake the referer as being from the host root
            $bits = parse_url($url);
            curl_setopt($c, CURLOPT_REFERER, 'http://' . $bits['host']);

            curl_setopt($c, CURLOPT_PROGRESSFUNCTION, [ $this, '_progress' ]);
            curl_setopt($c, CURLOPT_NOPROGRESS, false);

            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLINFO_HEADER_OUT, true);
            curl_setopt($c, CURLOPT_TIMEOUT, 60);
            curl_setopt($c, CURLOPT_ENCODING, 'gzip');
            $retVal = curl_exec($c);

            if (null == $retVal) {
                self::$_lastError = curl_error($c);
            }

            curl_close($c);
            $this->_requestComplete($url);

            return $retVal;
        }

        /**
         * A drop in replacement for file_get_contents with some business logic attached
         * @param string $url Url to retrieve
         * @return string Data received
         */
        private static function curl_get_contents($url) {
            $request = new Http();
            return $request->_get($url);
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

        private function _setActiveDownloads($urls) {
            Cache::Set(self::_getCacheKey(), $urls, CACHE_SHORT);
        }

        /**
         * A callback function to monitor cURL's download progress for a client
         */
        private function _progress($c, $totalBytesDown, $bytesDownloaded, $totalBytesUp, $bytesUploaded) {
            $urls = self::getActiveDownloads();
            $urls[$this->_url] = $totalBytesDown > 0 ? $bytesDownloaded / $totalBytesDown : 0;
            self::_setActiveDownloads($urls);
        }

        /**
         * Cleanup operations for when a cURL request completes
         */
        private function _requestComplete($url) {
            $urls = self::getActiveDownloads();
            if (isset($urls[$url])) {
                unset($urls[$url]);
                self::_setActiveDownloads($urls);
            }
        }

    }

}