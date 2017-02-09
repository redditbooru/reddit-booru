<?php

namespace Controller {

    define('IMAGES_PER_PAGE', 30);

    use Api;
    use Lib;
    use stdClass;

    /**
     * Reddit-Booru
     * Copyright (C) 2012 Matt Hackmann
     * GPLv3
     */

    class RedditBooru extends BasePage {

        private static $_allowedFilters = [ 'q', 'sources', 'user', 'imageUri' ];

        /**
         * Determines how the page needs to be rendered and passes control off accordingly
         */
        public static function render() {

            parent::render();

            $action = Lib\Url::Get('action', false);

            Lib\Display::addClientData('upload_name', ini_get('session.upload_progress.name'));

            // Check to see if we got a specific subdomain
            $domain = static::getPageSubdomain();
            if ($domain) {
                if ($domain === 'moesaic') {
                    self::_renderMoesaic();
                } else if ($domain === 'myfirst') {
                    self::_getMyFirst();
                } else if ($domain === 'mumble') {
                    self::_multiplayerMoe();
                } else if ($domain !== 'www' && $domain !== 'beta') {
                    $domain = Api\Source::getBySubdomain([ 'domain' => $domain ]);
                    // If no sub was found, redirect to the main page
                    if (!$domain) {
                        header('Location: http://redditbooru.com/');
                        exit;
                    } else {
                        self::$enabledSources = [ $domain->id ];
                    }
                }
            }

            $display = 'thumbs';
            $jsonOut = null;
            $postTitle = null;
            $urlOut = '/images/?';

            $filters = new stdClass;

            switch ($action) {
                case 'single':
                    self::_displaySingle();
                    break;
                case 'user':
                    $user = Lib\Url::Get('user');
                    $jsonOut = Images::getByQuery([ 'user' => $user ]);
                    $filters->user = $user;
                    $userData = Api\PostData::getUserProfile($user);
                    Lib\Display::renderAndAddKey('supporting', 'userProfile', $userData);

                    if (count($jsonOut) > 0) {
                        $postTitle = 'Posts by ' . $user;
                    }

                    break;
                case 'gallery':
                    $postId = Lib\Url::Get('post', null);
                    $jsonOut = Images::getByQuery([ 'postId' => $postId ]);
                    if (count($jsonOut) > 0) {
                        $postTitle = $jsonOut[0]->title;
                    }
                    $display = 'images';
                    break;
                case 'post':
                    $postId = Lib\Url::Get('post', null);
                    $jsonOut = Images::getByQuery([ 'externalId' => $postId ]);
                    if (count($jsonOut) > 0) {
                        $postTitle = $jsonOut[0]->title;
                    }
                    $display = 'images';
                    break;
                default:
                    $_GET['sources'] = isset($_GET['sources']) ? $_GET['sources'] : self::$enabledSources;
                    $filters = self::_filtersFromArray($_GET);
                    $jsonOut = isset($_GET['imageUri']) ? Images::getByImage($_GET) : Images::getByQuery($_GET);
                    break;
            }

            self::$renderKeys['images'] = $jsonOut;
            Lib\Display::addKey('filters', $filters);

            Lib\Display::addKey('use_min_js', USE_MIN_JS);
            Lib\Display::addKey('js_version', JS_VERSION);

            Lib\Display::renderAndAddKey('body', 'index', self::$renderKeys);

        }

        /**
         * Handles registering extensions
         */
        public static function registerExtension($class, $module, $type) {

        }

        /**
         * Renders a moesaic for the user
         */
        private static function _renderMoesaic() {
            $user = str_replace('/', '', $_SERVER['REQUEST_URI']);
            Lib\Display::addKey('USER', $user);
            Lib\Display::addKey('TITLE', ($user ? $user . '\'s ' : '') . 'Moesaic');
            Lib\Display::setTemplate('moesaic');
            Lib\Display::render();
            exit;
        }

        private static function _getMyFirst() {
            if (count($_POST) > 0 && isset($_POST['username'])) {
                $row = Lib\Db::Fetch(Lib\Db::Query('SELECT post_external_id FROM posts WHERE source_id = 1 AND user_id = (SELECT user_id FROM users WHERE user_name = :username) ORDER BY post_date ASC LIMIT 1', [ ':username' => $_POST['username'] ]));
                if ($row) {
                    header('Location: http://redd.it/' . $row->post_external_id);
                    exit;
                }
            }
            Lib\Display::setTemplate('myfirst');
            Lib\Display::render();
            exit;
        }

        private static function _displaySingle() {
            $file = Lib\Url::Get('file', null);
            $url = 'http://cdn.awwni.me/' . str_replace('_', '.', $file);
            Lib\Display::setTemplate('upload');
            Lib\Display::setVariable('URL', $url);
            Lib\Display::render();
            exit;
        }

        private static function _filtersFromArray($filters) {
            $retVal = new stdClass;
            foreach (self::$_allowedFilters as $key) {
                if (isset($filters[$key])) {
                    $retVal->$key = $filters[$key];
                }
            }
            return $retVal;
        }

        private static function _multiplayerMoe() {
            $row = Lib\Cache::getInstance()->fetch(function() {
                $result = Lib\Db::Query('SELECT post_external_id FROM posts WHERE post_title LIKE "%multiplayer moe%" AND user_id = 8 ORDER BY post_date DESC LIMIT 1');
                return $result && $result->count ? Lib\Db::Fetch($result) : null;
            }, 'MumblePage', CACHE_LONG);
            if ($row) {
                header('Location: http://redd.it/' . $row->post_external_id);
                exit;
            }
        }

    }

}