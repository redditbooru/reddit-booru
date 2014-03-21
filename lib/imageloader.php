<?php

namespace Lib {

    use stdClass;
    use MongoGridFS;

    define('IMAGE_TYPE_JPEG', 'jpg');
    define('IMAGE_TYPE_PNG', 'png');
    define('IMAGE_TYPE_GIF', 'gif');

    define('IMGEVT_DOWNLOAD_BEGIN', 'IMGEVT_DOWNLOAD_BEGIN');
    define('IMGEVT_DOWNLOAD_COMPLETE', 'IMGEVT_DOWNLOAD_COMPLETE');
    define('IMGEVT_DOWNLOAD_ERROR', 'IMGEVT_DOWNLOAD_ERROR');

    define('CACHE_MAX_SIZE', 16 * 1024 * 1024);

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

        private static function _downloadImage($url) {
            
            $retVal = null;

            // Account for local files or URLs
            if ($url{0} === '/' && is_readable($url) || strpos($url, 'file://') === 0) {
                $file = file_get_contents($url);
            } else {
                $file = Http::get($url);
            }

            if (!$file) {
                Events::fire(IMGEVT_DOWNLOAD_ERROR, 'Unable to download file');
                self::_log('downloadImage_fail', $url);
            } else {
                $type = self::_getImageType($file);
                if (null !== $type) {

                    $retVal = new stdClass;
                    $retVal->type = $type;
                    $retVal->timestamp = time();
                    $retVal->data = $file;
                    self::_log('downloadImage_success', $url);

                    // Cache to mongo
                    $rb = Mongo::getDatabase();
                    if ($rb) {
                        $gridFS = $rb->getGridFS();

                        // Make an array because WHAT THE FUCK, MONGO
                        $obj = [ 'url' => $url, 'type' => $retVal->type, 'timestamp' => $retVal->timestamp ];
                        $id = $gridFS->storeBytes($file, $obj);
                        if (!$id) {
                            self::_log('downloadImage_mongo_save_fail', $url);
                        } else {
                            self::_log('downloadImage_mongo_save_success', $url);
                        }

                    }

                } else {
                    self::_log('downloadImage_invalid', $url);
                    Events::fire(IMGEVT_DOWNLOAD_ERROR, 'Invalid image type');
                }
            }

            return $retVal;

        }

        /**
         * Attempts to retrieve an image from mongocache
         */
        private static function _fetchFromMongo($url) {
            
            $retVal = null;

            $rb = Mongo::getDatabase();
            if ($rb) {
                $gridFS = $rb->getGridFS();
                $result = $gridFS->findOne([ 'url' => $url ]);

                if ($result) {
                    $retVal = new stdClass;
                    $retVal->type = $result->file['type'];
                    $retVal->timestamp = $result->file['timestamp'];
                    $retVal->data = $result->getBytes();
                    self::_log('fetchImage_mongo_hit', $url);
                }
            }

            return $retVal;
        }

        /**
         * Given a URL, downloads and saves the output. Does some special case processing depending on where the image is hosted
         * @param string $url URL to download
         * @return object Object containing the image data and type
         */
        public static function fetchImage($url) {

            $retVal = null;

            Events::fire(IMGEVT_DOWNLOAD_BEGIN);
            
            // Check mongo for a copy of the image
            $retVal = self::_fetchFromMongo($url);

            // If nothing was found in the mongo cache, go fetch it the old fashioned way
            if (null === $retVal) {
                $retVal = self::_downloadImage($url);
            }


            if (null !== $retVal) {
                self::_log('fetchImage_success', $url);
                Events::fire(IMGEVT_DOWNLOAD_COMPLETE);
            }

            return $retVal;

        }

        /**
         * Mongo logging helper function
         */
        private static function _log($name, $data) {
            $log = new stdClass;
            $log->name = 'ImageLoader_' . $name;
            $log->data = $data;
            Logger::log($log);
        }

    }

}