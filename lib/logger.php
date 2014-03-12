<?php

namespace Lib {

    use stdClass;

    define('LOGGING_ENABLED', false);

    class Logger {

        private static $_document;

        public static function init() {
            if (LOGGING_ENABLED) {
                self::$_document = Mongo::getDatabase();
            }
        }

        public static function log($collection, $data) {
            if (LOGGING_ENABLED) {
                $obj = $data;
                if (!is_object($data)) {
                    $obj = new stdClass;
                    $obj->_single = true;
                    $obj->_data = $data;
                }
                $obj->_timestamp = time();

                self::$_document->$collection->insert($obj);
            }
        }

        public static function getByTimespan($collection, $start, $end = null) {
            $collection = self::$_document->$collection;
            $query = [ '$gt' => $start ];
            if (is_numeric($end)) {
                $query['$lt'] = $end;
            }
            return $collection->find([ '_timestamp' => $query ]);
        }

    }

    Logger::init();

}
