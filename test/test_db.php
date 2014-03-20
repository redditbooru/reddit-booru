<?php

// Test database lib

namespace Lib {
    
    use stdClass;

    class Db {

        public static function Connect() {

        }

        public static function Query($query, $params = null) {
            $retVal = new stdClass;
            $retVal->query = $query;
            $retVal->params = $params;
            return $retVal;
        }

        public static function Fetch($resource) {
            return $resource;
        }

    }

}