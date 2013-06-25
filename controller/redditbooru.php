<?php

namespace Controller {
	
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
			
			$sources = Lib\Url::Get('sources', 1, $_COOKIE);
			Lib\Display::setVariable('SOURCE_NAME', 'default');
            
            $action = Lib\Url::Get('action', false);
            
			// Check to see if we got a specific subdomain
			if (preg_match('/([\w]+)\.redditbooru\.com/is', $_SERVER['HTTP_HOST'], $matches)) {
				$domain = $matches[1];
				if ($domain != 'www' && $domain != 'beta') {
                    $domain = Api\Source::getBySubdomain([ 'domain' => $domain ]);
                    // If no sub was found, redirect to the main page
                    if (!$domain) {
                        header('Location: http://redditbooru.com/');
                        exit;
                    } else {
                        $sources = $domain->id;
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
				case 'user':
					$user = Lib\Url::Get('user');
					$jsonOut = Images::getByQuery([ 'user' => $user, 'sources' => $sources ]);
					$urlOut = '/images/?sources=';
                    $urlOut .= is_array($sources) ? implode(',', $sources) : $sources;
                    $urlOut .= '&user=' . $user;
                    
                    if (count($jsonOut) > 0) {
                        $postTitle = 'Posts by ' . $user;
                    }
                    
					break;
                case 'gallery':
                    $postId = Lib\Url::Get('post', null);
                    $jsonOut = Images::getByQuery([ 'postId' => $postId, 'ignoreSource' => true, 'ignoreUser' => true ]);
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
					$urlOut = '/images/?sources=';
					$urlOut .= is_array($sources) ? implode(',', $sources) : $sources;
                    $jsonOut = Images::getByQuery([ 'sources' => $sources ]);
					break;
			}
			
            if (count($jsonOut) > 0) {
                if ($jsonOut[0] instanceof JsonDataObject) {
                    $thumb = $jsonOut[0]->cdnUrl;
                }
            }
            
            $urlOut .= isset($_GET['flushCache']) ? '&flushCache' : '';

            Lib\Display::setVariable('display', $display);
            Lib\Display::setVariable('thumb', addslashes($thumb));
            Lib\Display::setVariable('post_title', addslashes($postTitle));
			
            // Filter out only subreddit sources
            $sources = Api\Source::getAllEnabled();
            $sources = array_values(array_filter($sources, function($item) { return $item->type === 'subreddit'; }));
            
            Lib\Display::setVariable('sources', json_encode($sources));
			Lib\Display::setVariable('start_up', json_encode($jsonOut));
			Lib\Display::setVariable('next_url', $urlOut);
			
		}
		
		/**
		 * Handles registering extensions
		 */
		public static function registerExtension($class, $module, $type) {
		
		}

	}
	
}