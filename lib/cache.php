<?php

namespace Lib {

	use Memcached;

	define('CACHE_LONG', 3600);
	define('CACHE_MEDIUM', 600);
	define('CACHE_SHORT', 60);

	if (!defined('DISABLE_CACHE')) {
		define('DISABLE_CACHE', false);
	}

	// memcache class
	class Cache {

		private $_memcache;
		private $_disabled = false;

		public static function getInstance() {
			static $cache;
			if (!$cache) {
				$cache = new Cache();
			}
			return $cache;
		}

		public function __construct($host = 'localhost', $port = 11211) {
			if (DISABLE_CACHE) {
				$this->setDisabled(true);
			} else {
				$this->_memcache = new Memcached();
				if (!$this->_memcache->addServer($host, $port)) {
					$this->_memcache = null;
				}

				// Since this is self-running, we don't yet have the benefit of the URL
				// parser having run. Pluck this out of the query string.
				if (isset($_SERVER['REQUEST_URI'])) {
					$requestUri = explode('?', $_SERVER['REQUEST_URI']);
					$this->setDisabled(strpos(end($requestUri), 'flushCache') !== false);
				} else {
					// In a CLI environment, don't bother with cache
					$this->setDisabled(true);
				}
			}
		}

		public function set($key, $val, $expiration = 600) {
			$retVal = false;
			if (null !== $this->_memcache && is_string($key)) {
				// Hash the key to obfuscate and to avoid the cache-key size limit
				$key = $this->_formatCacheKey($key);
				$retVal = $this->_memcache->set($key, $val, time() + $expiration);
				if (!$retVal) {
					var_dump($this->_memcache->getResultCode());
				}
			}
			return $retVal;
		}

		public function get($key, $forceCacheGet = false) {
			$retVal = false;
			$fetchFromCache = null != $this->_memcache && is_string($key) && ($forceCacheGet || !$this->_disabled);
			if ($fetchFromCache) {
				$formattedKey = $this->_formatCacheKey($key);
				$retVal = $this->_memcache->get($formattedKey);
			}
			return $retVal;
		}

		public function setDisabled($disabled) {
			$this->_disabled = $disabled;
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
		public function fetch($method, $cacheKey, $duration = CACHE_MEDIUM) {
			$retVal = $this->get($cacheKey);
			if ($retVal === false && is_callable($method)) {
				$retVal = $method();
				$this->set($cacheKey, $retVal, $duration);
			}
			return $retVal;
		}

		private function _formatCacheKey($key) {
			return CACHE_PREFIX . ':' . md5($key);
		}

	}

}
