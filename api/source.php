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
			'enabled' => 'source_enabled'
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
		 * Gets a record by its external ID
		 */
		public static function getById($vars) {
			
			$retVal = null;
			$sourceId = Lib\Url::GetInt('sourceId', null, $vars);
			if ($sourceId) {
				$cacheKey = 'Source_getById_' . $sourceId;
				$retVal = Lib\Cache::Get($cacheKey);
				if (false === $retVal) {
					$params = [ ':sourceId' => $sourceId ];
					$result = Lib\Db::Query('SELECT * FROM `sources` WHERE source_id = :sourceId', $params);
					if (null != $result && $result->count > 0) {
						$row = Lib\Db::Fetch($result);
						$retVal = new Source($row);
					}
					Lib\Cache::Set($cacheKey, $retVal, 86400);
				}
			}
			return $retVal;
			
		}
	
	}

}