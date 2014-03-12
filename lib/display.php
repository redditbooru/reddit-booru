<?php

namespace Lib {

	use Handlebars\Handlebars;
	use stdClass;

	class Display {

		private static $_tplData = [];
		private static $_theme;
		private static $_layout;
		private static $_hbEngine = null;

		public static function init() {
			self::$_hbEngine = new Handlebars([
				'loader' => new \Handlebars\Loader\FilesystemLoader(__DIR__ . '/../views/'),
				'partials_loader' => new \Handlebars\Loader\FilesystemLoader(__DIR__ . '/../views/partials/')
			]);
		}

		/**
		 * Renders the page
		 **/
		public static function render() {
			echo self::$_hbEngine->render('layouts/' . self::$_layout . '.handlebars', self::$_tplData);
		}

		// Displays an error message and halts rendering
		public static function showError($code, $message) {
			global $_title;
			/*
			$content = self::compile('<error><code>' . $code . '</code><message>' . $message . '</message></error>', 'error');
			self::setVariable('title', 'Error - ' . $_title);
			self::setVariable('content', $content);
			self::setTemplate('simple');
			self::render();
			*/
		}

		public static function setTheme($name) {
			self::$_theme = $name;
		}

		public static function setLayout($name) {
			self::$_layout = $name;
		}

		/**
		 * Renders data against a template and adds it to the output object
		 * @param string $key Key found in the layout template
		 * @param string $template Template name to render
		 * @param string $data Data to render against template
		 */
		public static function renderAndAddKey($key, $template, $data) {
			self::addKey($key, self::$_hbEngine->render($template, $data));
		}

		/**
		 * Adds a key/value to the output data
		 * @param string $key Key found in the layout template
		 * @param string $template Data to associate to the key
		 */
		public static function addKey($key, $value) {
			self::$_tplData[$key] = $value;
		}

	}

	Display::init();

}