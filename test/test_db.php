<?php

// Test database lib

namespace Lib {
    
    use stdClass;

    class Db {

        public static $lastResult;

        public static function Connect() {

        }

        public static function Query($query, $params = null) {
            
            $retVal = new stdClass;
            $retVal->query = $query;
            $retVal->params = $params;
            self::$lastResult = $retVal;

            // Action specific returns
            switch (strtolower(current(explode(' ', $query)))) {
                case 'insert':
                    $retVal = 1;
                    break;
                case 'update':
                case 'delete':
                    $retVal = 1;
                    break;
            }

            return $retVal;
        }

        public static function Fetch($resource) {
            return $resource;
        }

    }

}