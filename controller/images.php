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

        private static $_showRedditControls = false;

        public static function initialize() {
            $user = Api\User::getCurrentUser();
            if ($user instanceof Api\User) {
                self::$_showRedditControls = Lib\TestBucket::get('showRedditControls', $user->id) === 'enabled';
            }
        }

        /**
         * Determines how the page needs to be rendered and passes control off accordingly
         */
        public static function render() {

            // Dumb, but instantiate an image class to get the include
            $img = new Api\Image();
            unset($img);

            // Post means we're uploading an image or otherwise creating an album
            if (count($_POST)) {
                if (count($_FILES)) {
                    $images = self::_uploadImageSearch();
                } else {
                    $images = self::_postImages();
                }
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

            $sources = Lib\Url::Get('sources', [], $vars);
            $limit = Lib\Url::GetInt('limit', 30, $vars);
            $afterId = Lib\Url::GetInt('afterId', null, $vars);
            $postId = Lib\Url::GetInt('postId', null, $vars);
            $externalId = Lib\Url::Get('externalId', null, $vars);
            $afterDate = Lib\Url::GetInt('afterDate', null, $vars);
            $userName = Lib\Url::Get('user', null, $vars);
            $keywords = Lib\Url::Get('q', null, $vars);
            $ignoreSource = Lib\Url::GetBool('ignoreSource', $vars);
            $ignoreUser = Lib\Url::GetBool('ignoreUser', $vars);
            $honorVisible = Lib\Url::GetBool('honorVisible', $vars);

            // At this point, a username search will return all source results. We'll figure the rest out later
            if (!$userName) {

                // If there were no sources, get the user's default ones
                if (is_array($sources) && count($sources) === 0) {
                    $enabledSources = QueryOption::getSources();
                    foreach ($enabledSources as $source) {
                        if ($source->checked) {
                            $sources[] = $source->value;
                        }
                    }

                } else  if (is_string($sources)) {
                    $sources = strpos($sources, ',') !== false ? explode(',', $sources) : $sources;
                }
                $sources = is_numeric($sources) ? [ $sources ] : $sources;

                // For the cache key
                $var['sources'] = $sources;

                self::_saveSources($sources);

            } else {
                $sources === null;
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
                'honorVisible',
                'keywords' ], $vars);

            $retVal = Lib\Cache::Get($cacheKey);

            if (!$retVal) {

                $query = [];

                if (is_array($sources) && count($sources) > 0) {
                    $query['sourceId'] = [ 'in' => $sources ];
                } else {
                    $query['sourceId'] = [ 'null' => false ];
                }

                if ($externalId) {
                    $query['externalId'] = $externalId;
                }

                if ($postId) {
                    $query['postId'] = $postId;

                    // Unset the sources since we're requesting a specific post
                    unset($query['sourceId']);
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

                if ($honorVisible) {
                    $query['visible'] = 1;
                }

                $retVal = Api\PostData::queryReturnAll($query, [ 'dateCreated' => 'desc' ], $limit);

                Lib\Cache::Set($cacheKey, $retVal);

            }

            self::_log('getByQuery', $vars, $retVal);

            if (self::$_showRedditControls) {
                $retVal = Api\PostData::getVotesForPosts($retVal, Api\User::getCurrentUser());
            }

            return $retVal;
        }

        /**
         * Performs a reverse image lookup
         */
        public static function getByImage($vars) {
            $retVal = new stdClass;

            // self::_saveSources();

            $retVal->results = Api\PostData::reverseImageSearch($vars);
            $retVal->original = $vars['imageUri'];
            $retVal->preview = Thumb::createThumbFilename($retVal->original);
            $retVal->view = 'search';
            if (count($retVal->results) > 0) {
                // A match is considered "identical" when the distance, rounded to the hundredths place, is 0
                $identicals = [];
                foreach ($retVal->results as $result) {
                    if ((int) ($result->distance * 100) === 0 && $result->sourceId) {
                        $identicals[$result->sourceName] = true;
                    }
                }
                $retVal->identical = count($identicals) > 0 ? array_keys($identicals) : false;
            }

            self::_log('getByImage', $vars, $retVal);

            if (self::$_showRedditControls) {
                $retVal->results = Api\PostData::getVotesForPosts($retVal->results, Api\User::getCurrentUser());
            }

            return $retVal;
        }

        public static function _uploadImageSearch() {
            // Save the image
            $file = UploadManager::uploadFromFile(true);
            if (!$file->error) {
                $_GET['imageUri'] = $file->fileName;
                return self::getByImage($_GET);
            }
            return null;
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
         * Handle gallery POST
         */
        private static function _postImages() {
            $postId = Lib\Url::GetInt('postId', null);
            if ($postId) {
                return self::_editGallery($postId);
            } else {
                return self::_createGallery();
            }
        }

        /**
         * Creates a gallery
         */
        private static function _createGallery() {
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

                $user = Api\User::getCurrentUser();
                if ($user) {
                    $post->userId = $user->id;
                }

                // TODO - come up with a way of doing all this without four round trips to the database. Stored proc, maybe?
                if ($post->sync()) {
                    $path = Api\Post::createGalleryUrl($post->id, $post->title);
                    $post->link = 'http://' . $_SERVER['HTTP_HOST'] . $path;

                    // Perform image assignment and data denormalization
                    if (Api\PostImages::assignImagesToPost($retVal->images, $post) && Api\PostData::denormalizeForPost($post->id)) {
                        // Update the link. This is technically non-critical, so we won't error if something goes wrong here
                        $post->sync();
                        $retVal->route = $path;
                        Api\PostData::invalidateCacheForGallery($post);
                    }

                }
            } else if (count($retVal->images) === 1) {
                $retVal->redirect = $retVal->images[0]->getFilename(true);
            }

            return $retVal;
        }

        /**
         * Edits a gallery
         */
        private static function _editGallery($postId) {

            $user = Api\User::getCurrentUser();
            if (!$user) {
                return self::_generateErrorResponse('You must be logged in to edit galleries.');
            }

            $post = Api\Post::getById($postId);
            if (!$post) {
                return self::_generateErrorResponse('Unable to retrieve post information.');
            }

            if ($post->userId !== $user->id) {
                // Log this attempt
                self::_log('editGallery', [ 'postId' => $postId, 'userId' => $user->id, 'userIp' => $_SERVER['REMOTE_ADDR'] ], null);
                return self::_generateErrorResponse('You can only edit galleries you have created.');
            }

            $post->title = Lib\Url::Post('albumTitle');
            $post->setKeywordsFromTitle();
            $post->dateUpdated = time();

            if (!$post->sync()) {
                return self::_generateErrorResponse('Error saving post details');
            }

            $imageIds = Lib\Url::Post('imageId');
            $captions = Lib\Url::Post('caption');
            $sources = Lib\Url::Post('source');
            $images = [];
            $result = Api\Image::query([ 'id' => [ 'in' => $imageIds ] ]);

            if ($result && $result->count) {
                while ($row = Lib\Db::Fetch($result)) {
                    $image = new Api\Image($row);
                    $index = array_search($image->id, $imageIds);
                    if (false !== $index) {
                        $image->caption = $captions[$index];
                        $image->source = $captions[$index];
                        if ($image->sync()) {
                            $images[] = $image;
                        }
                    }
                }

                Api\PostImages::rebuildPostAssociations($images, $post);
                Api\PostData::denormalizeForPost($post->id);
                Api\PostData::invalidateCacheForGallery($post);

            }

            $retVal = new stdClass;
            $retVal->route = Api\Post::createGalleryUrl($post->id, $post->title);;
            return $retVal;

        }

        private static function _getImageFromArray($imageId, $images) {
            $retVal = null;

            foreach ($images as $image) {
                if ($image->id === $imageId) {
                    $retVal = $image;
                    break;
                }
            }

            return $retVal;
        }

        private static function _generateErrorResponse($message) {
            $retVal = new stdClass;
            $retVal->error = true;
            $retVal->message = $message;
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

        private static function _saveSources($sources) {
            // If specified, save the sources off to cookie
            if (Lib\Url::GetBool('saveSources') && count($sources) > 0) {
                setcookie('sources', implode(',', $sources), strtotime('+5 years'), '/');
            }
        }

    }

    Images::initialize();

}