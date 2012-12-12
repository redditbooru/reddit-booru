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
			
			$jsonOut = null;
			$urlOut = '/api/?type=json&method=post.searchPosts&getSource=true&getImages=true';
			$action = Lib\Url::Get('action', false);
			
			switch ($action) {
				case 'user':
					$user = Lib\Url::Get('user');
					$jsonOut = Api\Post::searchPosts([ 'user' => $user, 'getImages' => true, 'getSource' => true ]);
					$urlOut .= '&user=' . $user;
					break;
				case 'post':
					$jsonOut = Api\Post::searchPosts([ 'externalId' => $_GET['post'], 'getImages' => true, 'getSource' => true ]);
					break;
				default:
					$jsonOut = Api\Post::searchPosts([ 'getImages' => true, 'getSource' => true ]);
					break;
			}
			
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