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
			
			// Check to see if we got a specific subdomain
			if (preg_match('/([\w]+)\.redditbooru\.com/is', $_SERVER['HTTP_HOST'], $matches)) {			
				$domain = $matches[1];
				$domain = 'awwnime';
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
						Lib\Display::setTemplate('source');
					}
					
				}
			}
			
			$jsonOut = null;
			$urlOut = '/api/?type=json&method=post.searchPosts&getSource=true&getImages=true';
			$action = Lib\Url::Get('action', false);
			
			switch ($action) {
				case 'user':
					$user = Lib\Url::Get('user');
					$jsonOut = Api\Post::searchPosts([ 'user' => $user, 'getImages' => true, 'getSource' => true, 'sources' => $sources ]);
					$urlOut .= '&user=' . $user;
					break;
				case 'post':
					$jsonOut = Api\Post::searchPosts([ 'externalId' => $_GET['post'], 'getImages' => true, 'getSource' => true ]);
					break;
				default:
					if (is_array($sources)) {
						$urlOut .= '&sources=' . implode(',', $sources);
					} else {
						$urlOut .= '&sources=' . $sources;
					}
					$jsonOut = Api\Post::searchPosts([ 'getImages' => true, 'getSource' => true, 'sources' => $sources ]);
					break;
			}
			
			Lib\Display::setVariable('sources', json_encode(Api\Source::getAllEnabled()));
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
