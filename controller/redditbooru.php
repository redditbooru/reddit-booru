<?php

namespace Controller {

    define('IMAGES_PER_PAGE', 30);

	use Api;
	use Lib;

	/**
	 * Reddit-Booru
	 * Copyright (C) 2012 Matt Hackmann
	 * GPLv3
	 */

	class RedditBooru implements Page {

		/**
		 * Determines how the page needs to be rendered and passes control off accordingly
		 */
		public static function render() {

			$sources = QueryOption::getSources();
            $enabledSources = [];
            foreach ($sources as $source) {
                if ($source->checked) {
                    $enabledSources[] = $source->value;
                }
            }

            $action = Lib\Url::Get('action', false);

			// Check to see if we got a specific subdomain
			if (preg_match('/([\w]+)\.redditbooru\.com/is', $_SERVER['HTTP_HOST'], $matches)) {
                $domain = $matches[1];
                if ($domain === 'moesaic') {
                    self::_renderMoesaic();
                } else if ($domain === 'myfirst') {
                    self::_getMyFirst();
                } else if ($domain !== 'www' && $domain !== 'beta') {
                    $domain = Api\Source::getBySubdomain([ 'domain' => $domain ]);
                    // If no sub was found, redirect to the main page
                    if (!$domain) {
                        header('Location: http://redditbooru.com/');
                        exit;
                    } else {
                        $sources = [ $domain->id ];
                        Lib\Display::setVariable('SOURCE_NAME', $domain->subdomain);
                        Lib\Display::setVariable('SOURCE_ID', $domain->id);
                    }
				}
			}

            $display = 'thumbs';
			$thumb = null;
            $jsonOut = null;
            $postTitle = null;
			$urlOut = '/images/?';

			switch ($action) {
                case 'single':
                    self::_displaySingle();
                    break;
				case 'user':
                    $user = Lib\Url::Get('user');
					$jsonOut = Images::getByQuery([ 'user' => $user, 'sources' => $enabledSources ]);
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
                    $jsonOut = Images::getByQuery([ 'sources' => $enabledSources ]);
					break;
			}

            if (count($jsonOut) > 0) {
                if ($jsonOut[0] instanceof JsonDataObject) {
                    $thumb = $jsonOut[0]->cdnUrl;
                }
            }

            $urlOut .= isset($_GET['flushCache']) ? '&flushCache' : '';

            $out = [
                'sources' => json_encode($sources),
                'images' => json_encode($jsonOut)
            ];
            Lib\Display::renderAndAddKey('body', 'index', $out);

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
            Lib\Display::setVariable('USER', $user);
            Lib\Display::setVariable('TITLE', ($user ? $user . '\'s ' : '') . 'Moesaic');
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

	}

}