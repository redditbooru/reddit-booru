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

            $url = Lib\Url::Get('imageUri', null);
            $images = $url ? self::getByImage($_GET) : self::getByQuery($_GET);

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
            $title = Lib\Url::Get('keywords', null, $vars);
            $ignoreSource = Lib\Url::GetBool('ignoreSource', $vars);
            $ignoreUser = Lib\Url::GetBool('ignoreUser', $vars);
            $ignoreVisible = Lib\Url::GetBool('ignoreVisible', $vars);

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
                'ignoreVisible',
                'keywords' ], $vars);
            
            $retVal = Lib\Cache::Get($cacheKey);

            if (!$retVal) {
                
                if (is_string($sources)) {
                    $sources = strpos($sources, ',') !== false ? explode(',', $sources) : $sources;
                }

                $sources = is_numeric($sources) ? [ $sources ] : $sources;

                if (is_numeric($sources) || is_array($sources)) {

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

                    if ($title) {
                        $query['title'] = [ 'like' => '%' . str_replace(' ', '%', $title) . '%' ];
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
            $retVal = [];
            $vars['getSource'] = true;
            $vars['getUser'] = true;

            $evented = Lib\Url::GetBool('evented', $vars);

            // Register the event listeners
            if ($evented) {
                Lib\Events::beginAjaxEvent();
                Lib\Events::addEventListener(IMGEVT_DOWNLOAD_BEGIN, function($data) { self::_imageDownloadBegin($data); });
                Lib\Events::addEventListener(IMGEVT_PROCESSING, function($data) { self::_imageProcessing($data); });
            }

            $images = Api\Post::reverseImageSearch($vars);
            if (null != $images && count($images->results) > 0) {
                foreach ($images->results as $image) {
                    $retVal[] = new JsonDataObject($image);
                }
            }

            if ($evented) {
                Lib\Events::sendAjaxEvent('DATA', $retVal);
                Lib\Events::endAjaxEvent();
            }

            self::_log('getByImage', $vars, $retVal);

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
         * Handles registering extensions
         */
        public static function registerExtension($class, $module, $type) {

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