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
                
                $sources = strpos($sources, ',') !== false ? explode(',', $sources) : $sources;

                if (is_numeric($sources) || is_array($sources)) {

                    $params = [];
                    $joins = [];
                    
                    // Images and posts are required
                    $columns = [ 
                        'i.image_id',
                        'i.post_id',
                        'i.image_url',
                        'i.image_width',
                        'i.image_height',
                        'i.image_cdn_url',
                        'i.source_id',
                        'p.post_title',
                        'p.post_date',
                        'p.post_score',
                        'p.post_external_id',
                        'p.post_keywords'
                    ];

                    $joins = [
                        'INNER JOIN `posts` p ON p.post_id = i.post_id'
                    ];

                    $where = [ 'i.image_good = 1' ];
                    if (!$ignoreVisible) {
                        $where[] = 'p.post_visible = 1';
                    }

                    if (!$ignoreSource) {
                        $columns = array_merge($columns, [
                            's.source_name',
                            's.source_baseurl',
                            's.source_content_rating'
                        ]);
                        $joins[] = 'INNER JOIN `sources` s ON s.source_id = i.source_id';

                        // Source, sources, or no source
                        if (is_numeric($sources)) {
                            $params[':sourceId'] = $sources;
                            $where[] = 'i.source_id = :sourceId';
                        } else if (is_array($sources)) {
                            for ($i = 0, $count = count($sources); $i < $count; $i++) {
                                $params[':sourceId' . $i] = $sources[$i];
                            }
                            $where[] = 'i.source_id IN (' . implode(',', array_keys($params)) . ')';
                        } else if (null === $sources) {
                            $where[] = 'i.source_id IS NULL';
                        }

                    }

                    if (!$ignoreUser) {
                        $columns = array_merge($columns, [
                            'u.user_name', 
                            'u.user_reddit_id',
                            'u.user_date_created',
                            'u.user_id'
                        ]);
                        $joins[] = 'INNER JOIN `users` u ON u.user_id = p.user_id';

                        if ($userName) {
                            $where[] = 'u.user_name = :userName';
                            $params[':userName'] = $userName;
                        }

                    }

                    if ($externalId) {
                        $where[] = 'p.post_external_id = :externalId';
                        $params[':externalId'] = $externalId;
                    }

                    if ($postId) {
                        $where[] = 'p.post_id = :postId';
                        $params[':postId'] = $postId;
                    }

                    if ($afterId) {
                        $where[] = 'i.image_id < :afterId';
                        $params[':afterId'] = $afterId;
                    }

                    if ($afterDate) {
                        $where[] = 'p.post_date < :afterDate';
                        $params[':afterDate'] = $afterDate;
                    }

                    if ($title) {
                        $where[] = 'p.post_title LIKE :title';
                        $params[':title'] = '%' . str_replace(' ', '%', $title) . '%';
                    }

                    $query = 'SELECT ' . implode(',', $columns) . ' FROM `images` i ' . implode(' ', $joins) . ' WHERE ' . implode(' AND ', $where);
                    $query .= ' ORDER BY p.post_date DESC LIMIT ' . $limit;

                    $result = Lib\Db::Query($query, $params);

                    if (null != $result && $result->count > 0) {
                        $retVal = [];
                        while ($row = Lib\Db::Fetch($result)) {
                            $retVal[] = new JsonDataObject($row);
                        }
                    } else {
                        $retVal = null;
                    }
                    
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