<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    /**
     * Reddit-Booru
     * Copyright (C) 2012 Matt Hackmann
     * GPLv3
     */

    class Images implements Page {

        /**
         * Determines how the page needs to be rendered and passes control off accordingly
         */
        public static function render() {

            // Dumb, but instantiate an image class to get the include
            $img = new Api\Image();
            unset($img);

            // Post means we're uploading an image or otherwise creating an album
            if (count($_POST)) {
                $images = self::_postImages();
            } else {
                $url = Lib\Url::Get('imageUri', null);
                $images = $url ? self::getByImage($_GET) : self::getByQuery($_GET);
            }

            // CORS support for RES
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                header('Access-Control-Allow-Origin: *');
            }

            header('Content-Type: text/javascript; charset=utf-8');
            echo json_encode($images);
            exit;
        }


        /**
         * Multi-table lookup for images and posts
         */
        public static function getByQuery($vars) {

            $sources = Lib\Url::Get('sources', 1, $vars);
            $limit = Lib\Url::GetInt('limit', 30, $vars);
            $afterId = Lib\Url::GetInt('afterId', null, $vars);
            $postId = Lib\Url::GetInt('postId', null, $vars);
            $externalId = Lib\Url::Get('externalId', null, $vars);
            $afterDate = Lib\Url::GetInt('afterDate', null, $vars);
            $userName = Lib\Url::Get('user', null, $vars);
            $keywords = Lib\Url::Get('q', null, $vars);
            $ignoreSource = Lib\Url::GetBool('ignoreSource', $vars);
            $ignoreUser = Lib\Url::GetBool('ignoreUser', $vars);
            $ignoreVisible = Lib\Url::GetBool('ignoreVisible', $vars);

            // Normalize the sources down to an array
            if (is_string($sources)) {
                $sources = strpos($sources, ',') !== false ? explode(',', $sources) : $sources;
            }
            $sources = is_numeric($sources) ? [ $sources ] : $sources;

            // For the cache key
            $var['sources'] = $sources;

            // If specified, save the sources off to cookie
            if (Lib\Url::GetBool('saveSources')) {
                setcookie('sources', implode(',', $sources), strtotime('+5 years'), '/');
            }

            $cacheKey = Lib\Cache::createCacheKey('Images::getByQuery_', [
                'sources',
                'limit',
                'afterId',
                'postId',
                'externalId',
                'afterDate',
                'ignoreSource',
                'ignoreUser',
                'user',
                'q',
                'ignoreVisible',
                'keywords' ], $vars);

            $retVal = Lib\Cache::Get($cacheKey);

            if (!$retVal) {

                if (is_array($sources)) {

                    $query = [ 'sourceId' => [ 'in' => $sources ] ];

                    if ($externalId) {
                        $query['externalId'] = $externalId;
                    }

                    if ($postId) {
                        $query['postId'] = $postId;
                    }

                    if ($userName) {
                        $query['userName'] = $userName;
                    }

                    if ($afterId) {
                        $query['imageId'] = [ 'lt' => $afterId ];
                    }

                    if ($afterDate) {
                        $query['dateCreated'] = [ 'lt' => $afterDate ];
                    }

                    if ($keywords) {
                        $query['keywords'] = [ 'like' => '%' . str_replace(' ', '%', $keywords) . '%' ];
                    }

                    $retVal = Api\PostData::queryReturnAll($query, [ 'dateCreated' => 'desc' ], $limit);

                    Lib\Cache::Set($cacheKey, $retVal);

                }

            }

            self::_log('getByQuery', $vars, $retVal);

            return $retVal;
        }

        /**
         * Performs a reverse image lookup
         */
        public static function getByImage($vars) {
            $retVal = new stdClass;

            $evented = Lib\Url::GetBool('evented', $vars);

            // Register the event listeners
            if ($evented) {
                Lib\Events::beginAjaxEvent();
                Lib\Events::addEventListener(IMGEVT_DOWNLOAD_BEGIN, function($data) { self::_imageDownloadBegin($data); });
                Lib\Events::addEventListener(IMGEVT_PROCESSING, function($data) { self::_imageProcessing($data); });
            }

            $retVal->results = Api\PostData::reverseImageSearch($vars);
            $retVal->preview = Thumb::createThumbFilename($vars['imageUri']);
            if (count($retVal->results) > 0) {
                // A match is considered "identical" when the distance, rounded to the hundredths place, is 0
                $identicals = [];
                foreach ($retVal->results as $result) {
                    if ((int) ($result->distance * 100) === 0) {
                        $identicals[$result->sourceName] = true;
                    }
                }
                $retVal->identical = count($identicals) > 0 ? array_keys($identicals) : false;
            }

            self::_log('getByImage', $vars, $retVal);

            if ($evented) {
                Lib\Events::sendAjaxEvent('DATA', $retVal);
                Lib\Events::endAjaxEvent();
            }

            return $retVal;
        }

        /**
         * Event listeners
         */
        public static function _imageDownloadBegin($data) {
            Lib\Events::sendAjaxEvent(IMGEVT_DOWNLOAD_BEGIN, null);
        }
        public static function _imageProcessing($data) {
            Lib\Events::sendAjaxEvent(IMGEVT_PROCESSING, null);
        }

        /**
         * Creates image/post entries for uploaded images
         */
        private static function _postImages() {
            $retVal = new stdClass;
            $retVal->images = [];

            for ($i = 0, $count = count($_POST['imageId']); $i < $count; $i++) {
                $id = $_POST['imageId'][$i];

                $image = Api\Image::getById($id);
                if ($image) {
                    $image->caption = $_POST['caption'][$i];
                    $image->sourceUrl = $_POST['source'][$i];

                    if ($image->sync()) {
                        $retVal->images[] = $image;
                    }
                }
            }

            // If there's more than one image, setup an album
            if (count($retVal->images) > 1) {
                $post = new Api\Post();
                $post->title = Lib\Url::Post('albumTitle');
                $post->setKeywordsFromTitle();
                $post->dateCreated = time();
                $post->link = 'http://' . $_SERVER['HTTP_HOST'];

                // TODO - if the user is logged in, set userId here

                // TODO - come up with a way of doing all this without four round trips to the database. Stored proc, maybe?
                if ($post->sync()) {
                    $path = '/gallery/' . base_convert($post->id, 10, 36) . '/' . str_replace(' ', '-', $post->keywords);
                    $post->link = 'http://' . $_SERVER['HTTP_HOST'] . $path;

                    // Perform image assignment and data denormalization
                    if (Api\PostImages::assignImagesToPost($retVal->images, $post) && Api\PostData::denormalizeForPost($post->id)) {
                        // Update the link. This is technically non-critical, so we won't error if something goes wrong here
                        $post->sync();
                        $retVal->redirect = $path;
                    }

                }
            } else if (count($retVal->images) === 1) {
                $retVal->redirect = $retVal->images[0]->getFilename(true);
            }

            return $retVal;
        }

        /**
         * Logs the input and output of a function
         */
        private static function _log($name, $vars, $result) {
            $log = new stdClass;
            $log->name = 'Images_' . $name;
            $log->data = $vars;
            $log->result = null == $result;
            Lib\Logger::log('controller', $log);
        }

    }

}