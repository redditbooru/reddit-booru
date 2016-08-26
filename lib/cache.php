<?php

namespace Lib {

	use Memcache;
	use Predis;

	define('CACHE_LONG', 3600);
	define('CACHE_MEDIUM', 600);
	define('CACHE_SHORT', 60);

	if (!defined('DISABLE_CACHE')) {
		define('DISABLE_CACHE', false);
	}

	if (!DISABLE_CACHE) {
		Cache::Connect();
	}

	// memcache class
	class Cache {

		private static $_memcache;
		private static $_redis;
		private static $_disabled = false;

		public static function Connect($host = 'localhost', $port = 11211) {
			self::$_memcache = new Memcache();
			if (!self::$_memcache->pconnect($host, $port)) {
				self::$_memcache = null;
			}

			// Since this is self-running, we don't yet have the benefit of the URL
			// parser having run. Pluck this out of the query string.
			if (isset($_SERVER['REQUEST_URI'])) {
				$requestUri = explode('?', $_SERVER['REQUEST_URI']);
				self::setDisabled(strpos(end($requestUri), 'flushCache') !== false);
			} else {
				// In a CLI environment, don't bother with cache
				self::setDisabled(true);
			}
		}

		public static function Set($key, $val, $expiration = 600) {
			$retVal = false;
			if (null != self::$_memcache && is_string($key)) {
				$retVal = self::$_memcache->set(CACHE_PREFIX . ':' . $key, $val, null, time() + $expiration);
			}
			return $retVal;
		}

		public static function Get($key, $forceCacheGet = false) {
			$retVal = false;
			$fetchFromCache = null != self::$_memcache && is_string($key) && ($forceCacheGet || !self::$_disabled);
			if ($fetchFromCache) {
				$retVal = self::$_memcache->get(CACHE_PREFIX . ':' . $key);
			}
			return $retVal;
		}

		public static function setDisabled($disabled) {
			self::$_disabled = $disabled;
		}

		/**
		 * Creates a cache key using selected values from an array of values (usually _GET)
		 */
		public static function createCacheKey($prefix, $params, $values) {
			$retVal = [ $prefix ];
			foreach ($params as $param) {
				$value = Url::Get($param, 'null', $values);
				if (is_array($value)) {
					$value = implode(',', $value);
				}
				$retVal[] = $value;
			}
			return implode('_', $retVal);
		}

		/**
		 * Attempts to get data from cache. On miss, executes the callback function, caches that value, and returns it
		 */
		public static function fetch($method, $cacheKey, $duration = CACHE_MEDIUM) {
			$retVal = self::Get($cacheKey);
			if ($retVal === false && is_callable($method)) {
				$retVal = $method();
				self::Set($cacheKey, $retVal, $duration);
			}
			return $retVal;
		}

	}

}
