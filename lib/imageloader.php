<?php

namespace Lib {

    define('IMAGE_TYPE_JPEG', 'jpg');
    define('IMAGE_TYPE_PNG', 'png');
    define('IMAGE_TYPE_GIF', 'gif');

    class ImageLoader {

        /**
         * Based on the incoming URL, resolves against various services and provides back and array of image URLs
         */
        public static function getImagesFromUrl($url) {

            $parts = parse_url($url);
            $domain = isset($parts['host']) ? strtolower($parts['host']) : null;
            $domain = explode('.', $domain, 2);
            $domain = strpos($domain[1], '.') === false ? join('.', $domain) : end($domain);
            $path = isset($parts['path']) ? $parts['path'] : null;
            $retVal = [];

            // Imgur links
            if ('imgur.com' === $domain) {
                $retVal = self::handleImgurLink($url);

            // DeviantArt images
            } else if ('deviantart.com' === $domain || 'fave.me' === $domain) {
                $retVal[] = self::getDeviantArtImage($url);

            // Yandere image
            } else if ($domain === 'yande.re') {
                $retVal = self::getYandereImage($url);

            // Minus album
            } else if (preg_match('/([\w]+\.)?minus\.com\/([^\.])+$/i', $url, $matches)) {
                $retVal = self::getMinusAlbum($matches[2]);

            // tumblr posts
            } else if ('tumblr.com' === $domain && preg_match('/\/post\/([\d]+)\//', $path, $matches)) {
                $retVal = self::getTumblrImages($url);

            // mediacrush
            } else if ($domain === 'mediacru.sh') {
                $retVal = self::getMediacrushImages($url);

            // Everything else
            } else {
                $retVal[] = $url;
            }

            return $retVal;

        }

        /**
         * Resolves a deviantArt link to it's image
         */
        public static function getDeviantArtImage($url) {
            $retVal = null;
            $info = json_decode(Http::get('http://backend.deviantart.com/oembed?url=' . urlencode($url)));
            if (is_object($info)) {
                $retVal = $info->url;
            }
            return $retVal;
        }

        /**
         * Handles image fetching on various formats of imgur links
         */
        public static function handleImgurLink($url) {
            $parts = parse_url($url);
            $path = $parts['path'];
            $retVal = [];

            if (strpos($path, ',') !== false) {
                $path = str_replace('/', '', $path);
                $files = explode(',', $path);
                foreach ($files as $file) {
                    $retVal[] = 'http://imgur.com/' . $file . '.jpg';
                }
            } else if (strpos($path, '/a/') === 0 || strpos($path, '/gallery/') === 0) {
                $id = str_replace([ '/a/', '/gallery/' ], '', $path);
                $id = explode('#', $id);
                $id = current($id);
                $retVal = self::getImgurAlbum($id);
            } else if (strpos($path, '.') === false) {
                $retVal[] = $url .= '.jpg';
            } else {
                $retVal[] = $url;
            }

            return $retVal;
        }

        /**
         * Gets a list of image URLs from an imgur album
         */
        public static function getImgurAlbum($id) {
            $data = Http::get('http://api.imgur.com/2/album/' . $id . '.json');
            $retVal = null;
            if (strlen($data) > 0) {
                $data = json_decode($data);
                if (isset($data->album) && isset($data->album->images)) {
                    $retVal = [];
                    foreach ($data->album->images as $image) {
                        $retVal[] = $image->links->original;
                    }
                }
            }
            return $retVal;
        }

        /**
         * Returns a list of image URLs in a tumblr post
         */
        public static function getTumblrImages($url) {
            $retVal = [];

            // Parse out the ID
            $url = parse_url($url);
            if (preg_match('/\/post\/([\d]+)\//', $url['path'], $matches)) {
                $apiCall = 'http://api.tumblr.com/v2/blog/' . $url['host'] . '/posts?id=' . $matches[1] . '&api_key=' . TUMBLR_CONSUMER_KEY;
                $response = json_decode(Http::get($apiCall));
                if ($response && is_object($response->response)) {
                    foreach ($response->response->posts[0]->photos as $photo) {
                        $retVal[] = $photo->original_size->url;
                    }
                }
            }

            return $retVal;
        }

        public static function getYandereImage($url) {
            $retVal = [];
            $response = Http::get($url);
            if (preg_match('/original-file-changed\" href=\"([^\"]+)\"/', $response, $match)) {
                $retVal[] = $match[1];
            }
            return $retVal;
        }

        /**
         * Returns a list of image URLs in a mediacrush post
         */
        public static function getMediacrushImages($url) {
            $retVal = [];

            $response = json_decode(Http::get($url . '.json'));
            if (is_object($response) && is_array($response->files)) {
                $url = parse_url($url);
                if (count($response->files) === 1) {
                    $retVal[] = $url['scheme'] . '://mediacru.sh' . $response->original;
                } else {
                    foreach ($response->files as $file) {
                        $retVal[] = $url['scheme'] . '://mediacru.sh' . $file->original;
                    }
                }
            }

            return $retVal;
        }

        /**
         * Scrapes a minus album page and gets the URLs for all images
         */
        public static function getMinusAlbum($id) {

            $retVal = null;

            $page = Http::get('http://minus.com/' . $id);
            if ($page) {

                // Get the image data json
                $dataBeginToken = 'var gallerydata = ';
                $dataEndToken = '};';
                $start = strpos($page, $dataBeginToken);
                if (false !== $start) {
                    $end = strpos($page, $dataEndToken, $start) + 1;
                    $start += strlen($dataBeginToken);
                    $jsonData = json_decode(substr($page, $start, $end - $start));
                    if (is_object($jsonData) && is_array($jsonData->items)) {
                        $retVal = [];
                        foreach ($jsonData->items as $item) {
                            $ext = explode('.', $item->name);
                            $ext = end($ext);
                            $retVal[] = 'http://i.minus.com/i' . $item->id . '.' . $ext;
                        }
                    }
                }

            }

            return $retVal;

        }

        /**
         * Given a file, returns the image mime type
         */
        public static function getImageType($fileName) {
            $retVal = null;
            $handle = fopen($fileName, 'rb');
            if ($handle) {
                $head = fread($handle, 10);
                $retVal = self::_getImageType($head);
                fclose($handle);
            }
            return $retVal;
        }
        
        /**
         * Determines the image type of the incoming data
         * @param string $data Data of the image file to determine
         * @return string Mime type of the image, null if not recognized
         */
        private static function _getImageType($data) {
        
            $retVal = null;
            if (ord($data{0}) == 0xff && ord($data{1}) == 0xd8) {
                $retVal = 'jpg';
            } else if (ord($data{0}) == 0x89 && substr($data, 1, 3) == 'PNG') {
                $retVal = 'image/png';
            } else if (substr($data, 0, 6) == 'GIF89a' || substr($data, 0, 6) == 'GIF87a') {
                $retVal = 'image/gif';
            }
            
            return $retVal;
        
        }
        
        /**
         * Loads a file, determines the image type by scanning the header, and returns a GD object
         * @param string $file Path to the file to load
         * @return object Object containing the GD image and the mimeType, null on failure
         */
        public static function loadImage($file) {

            $retVal = null;
            
            $type = self::getImageType($file);
            
            if (false !== $type) {
                $retVal = new stdClass;
                $retVal->mimeType = $type;
                switch ($type) {
                    case IMAGE_TYPE_JPEG:
                        $retVal->image = @imagecreatefromjpeg($file);
                        break;
                    case IMAGE_TYPE_PNG:
                        $retVal->image = @imagecreatefrompng($file);
                        break;
                    case IMAGE_TYPE_GIF:
                        $retVal->image = @imagecreatefromgif($file);
                        break;
                    default:
                        $retVal = null;
                }
                
                if (null != $retVal && null == $retVal->image) {
                    $retVal = null;
                }
                
            }
            
            return $retVal;
            
        }

        /**
         * Given a URL, downloads and saves the output. Does some special case processing depending on where the image is hosted
         * @param string $url URL to download
         * @param string $fileName File path to save to
         * @return bool Whether the image was downloaded successfully
         */
        public static function downloadImage($url, $fileName) {

            $retVal = false;

            Lib\Events::fire(IMGEVT_DOWNLOAD_BEGIN);
            
            $url = self::parseUrl($url);
            if ($url) {
                $file = null;
                
                // Account for local files or URLs
                if ($url{0} === '/' && is_readable($url)) {
                    $file = file_get_contents($url);
                } else {
                    $file = Http::get($url);
                }

                if (!$file) {
                    Lib\Events::fire(IMGEVT_DOWNLOAD_ERROR, 'Unable to download file');
                    self::_log('downloadImage_fail', $url);
                    self::$imageLoadError = IMGERR_DOWNLOAD_FAILURE;
                } else if (null != self::_getImageType($file)) {
                    $handle = fopen($fileName, 'wb');
                    if ($handle) {
                        fwrite($handle, $file);
                        fclose($handle);
                        $retVal = true;
                        self::_log('downloadImage_success', $url);
                        Lib\Events::fire(IMGEVT_DOWNLOAD_COMPLETE);
                    }
                } else {
                    self::_log('downloadImage_invalid', $url);
                    Lib\Events::fire(IMGEVT_DOWNLOAD_ERROR, 'Invalid image type');
                    self::$imageLoadError = IMGERR_INVALID_FORMAT;
                }
            }

            return $retVal;

        }

    }

}