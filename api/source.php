<?php

namespace Api {

	use Lib;
	use stdClass;

	class Source extends Lib\Dal {

		/**
		 * Object property to table map
		 */
		protected $_dbMap = array(
			'id' => 'source_id',
			'name' => 'source_name',
			'baseUrl' => 'source_baseurl',
			'type' => 'source_type',
			'enabled' => 'source_enabled',
			'subdomain' => 'source_subdomain',
            'contentRating' => 'source_content_rating'
		);

		/**
		 * Database table name
		 */
		protected $_dbTable = 'sources';

		/**
		 * Table primary key
		 */
		protected $_dbPrimaryKey = 'id';

		/**
		 * ID of the source
		 */
		public $id = 0;

		/**
		 * Name of the source
		 */
		public $name;

		/**
		 * URL of the source media
		 */
		public $baseUrl;

		/**
		 * Source type
		 */
		public $type;

		/**
		 * Source type
		 */
		public $enabled;

		/**
		 * Associated subdomain
		 */
		public $subdomain;

        /**
         * Content rating
         */
        public $contentRating;

		/**
		 * Constructor
		 * @param $obj mixed Data to construct object around
		 */
		public function __construct($obj = null) {
			if ($obj instanceOf Source) {
				__copy($obj);
			} else if (is_object($obj)) {
				$this->copyFromDbRow($obj);
			}
		}

		private function __copy($obj) {
			if ($obj instanceOf Source) {
				$this->id = $obj->id;
				$this->name = $obj->name;
				$this->baseUrl = $obj->baseUrl;
				$this->type = $obj->type;
			}
		}

		/**
		 * XML serializer
		 */
		public function __serialize() {
			$retVal = '<source id="' . $this->id . '" type="' . $this->type . '">';
			$retVal .= '<name><![CDATA[' . $this->name . ']]></name>';
			$retVal .= '<baseUrl>' . $this->baseUrl . '</baseUrl>';
			$retVal .= '</source>';
			return $retVal;
		}

		/**
		 * Returns all sources
		 */
		public static function getAllEnabled() {

			$cacheKey = 'Source_getAllEnabled';
			$retVal = Lib\Cache::Get($cacheKey);
			if (false === $retVal) {
				$result = Lib\Db::Query('SELECT * FROM `sources` WHERE source_enabled = 1');
				if (null != $result && $result->count > 0) {
					$retVal = array();
					while ($row = Lib\Db::Fetch($result)) {
						$retVal[] = new Source($row);
					}
				}
				Lib\Cache::Set($cacheKey, $retVal);
			}
			return $retVal;

		}

		/**
		 * Returns a source by subdomain
		 */
		public static function getBySubdomain($vars) {

			$domain = Lib\Url::Get('domain', null, $vars);
			$cacheKey = 'Source::getBySubdomain_' . $domain;
			$retVal = Lib\Cache::Get($cacheKey);
			if (false === $retVal) {
				$result = Lib\Db::Query('SELECT * FROM `sources` WHERE source_subdomain = :domain', [ 'domain' => $domain ]);
				$retVal = null;
				if (null != $result && $result->count > 0) {
					$retVal = new Source(Lib\Db::Fetch($result));
				}
				Lib\Cache::Set($cacheKey, $retVal, 3600);
			}

			return $retVal;

		}

		/**
		 * Returns any sources on the query string in an array defaulting to cookie when not present
		 */
		public static function getSourcesFromQueryString() {
			$sources = Lib\Url::Get(QS_SOURCES, null);
			if (null !== $sources) {
				$sources = explode(',', $sources);
			} else {
				if (isset($_COOKIE[QS_SOURCES])) {
					$sources = explode(',', $_COOKIE[QS_SOURCES]);
				} else {
					$sources = [ 1 ]; // final default is awwnime
				}
			}

			return $sources;

		}

		public static function formatSourceName($name) {
			return strpos($name, 'r/') === 0 ? substr($name, 2) : $name;
		}

	}

}