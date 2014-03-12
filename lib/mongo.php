<?php

namespace Lib {
    
    use MongoClient;

    class Mongo {

        private static $_conn;
        private static $_db;

        public static function connect($database = null) {
            self::$_conn = new MongoClient();
            if (null !== $database) {
                self::getDatabase($database);
            }
        }

        public static function getDatabase($database = null) {
            $retVal = self::$_db;
            if (!$retVal && self::$_conn instanceof MongoClient) {
                self::$_db = self::$_conn->$database;
                $retVal = self::$_db;
            }
            return $retVal;
        }

    }

    Mongo::connect(MONGO_DATABASE);

}