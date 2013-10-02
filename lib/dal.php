<?php

namespace Lib {
	
    use Exception;

	class Dal {
		
        /**
         * Constructor
         */
        public function __construct($obj = null) {
        
            if (is_numeric($obj)) {
                $this->getById($obj);
            } else if (is_object($obj)) {
                $this->copyFromDbRow($obj);
            }
        
        }
        
		/**
		 * Syncs the current object to the database
		 */
		public function sync() {
			
			$retVal = 0;
			
			if (property_exists($this, '_dbTable') && property_exists($this, '_dbMap')) {
				
				$dbParams = array();
				
				// Determine if a primary key was set
				$primaryKey = property_exists($this, '_dbPrimaryKey') ? $this->_dbPrimaryKey : false;
				$primaryKeyValue = 0;
				if ($primaryKey) {
					$primaryKeyValue = (int) $this->$primaryKey;
				}
				
				// If the primary key value is non-zero, do an UPDATE
				$method = $primaryKeyValue !== 0 ? 'UPDATE' : 'INSERT';
				$parameters = [];
				
				foreach ($this->_dbMap as $property => $column) {
					// Primary only gets dropped in for UPDATEs
					if (($primaryKey === $property && 'UPDATE' === $method) || $primaryKey !== $property) {
						$paramName = ':' . $property;
						
						// Serialize objects going in as JSON
						$value = $this->$property;
						if (is_object($value)) {
							$value = json_encode($value);
						}
						$params[$paramName] = $value;
						
						if ('INSERT' === $method) {
							$parameters[] = $paramName;
						} else if ($primaryKey != $property) {
							$parameters[] = '`' . $column . '` = ' . $paramName;
						}
					}
				}
				
				// Build and execute the query
				$query = $method;
				if ('INSERT' === $method) {
					$query .= ' INTO `' . $this->_dbTable . '` (`' . implode('`,`', $this->_dbMap) . '`) VALUES (' . implode(',', $parameters) . ')';
					$query = str_replace('`' . $this->_dbMap[$primaryKey] . '`,', '', $query);
				} else {
					$query .= ' `' . $this->_dbTable . '` SET ' . implode(',', $parameters) . ' WHERE `' . $this->_dbMap[$primaryKey] . '` = :' . $primaryKey;
				}
				$retVal = Db::Query($query, $params);
				
				// Save the ID for insert
				if ($retVal > 0 && 'INSERT' === $method) {
					$this->$primaryKey = $retVal;
				}
				
			}
			
			return $retVal > 0;
		
		}
		
		/**
		 * Creates an object from the passed database row
		 */
		public function copyFromDbRow($obj) {
			if (property_exists($this, '_dbMap') && is_object($obj)) {
				foreach($this->_dbMap as $property => $column) {
					if (property_exists($obj, $column) && property_exists($this, $property)) {
						$this->$property = $obj->$column;
						if ($column === $this->_dbPrimaryKey) {
							$this->$property = (int) $this->$property;
						}
					}
				}
			}
		}


        public static function getSingleById($id) {
        	$className = get_called_class();
        	$retVal = new $className();
        	$retVal->getById($id['id']);
        	return $retVal;
        }
        
        /**
         * Gets a record from the database by the primary key
         */
        public function getById($id) {

            $retVal = null;
            if (property_exists($this, '_dbTable') && property_exists($this, '_dbMap') && property_exists($this, '_dbPrimaryKey') && is_numeric($id)) {

                $cacheKey = $this->_dbTable . '_getById_' . $id;
                $retVal = Cache::Get($cacheKey);

                if (!$retVal) {
                    $query  = 'SELECT ' . implode(',', $this->_dbMap) . ' FROM `' . $this->_dbTable . '` ';
                    $query .= 'WHERE ' . $this->_dbMap[$this->_dbPrimaryKey] . ' = :id LIMIT 1';
                    
                    $result = Db::Query($query, [ ':id' => $id ]);
                    if (null != $result && $result->count === 1) {
                        $retVal = $this->copyFromDbRow(Db::Fetch($result));
                    }
                    Cache::Set($cacheKey, $retVal);
                }

            } else {
                throw new Exception('Class must have "_dbTable", "_dbMap", and "_dbPrimaryKey" properties to use method "getById"');
            }
            return $retVal;
        }
	
	}

}