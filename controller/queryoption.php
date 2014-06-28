<?php

namespace Controller {
    
    use Api;
    use Lib;

    define('QS_SOURCES', 'sources');

    class QueryOption {

        public $name;
        public $title;
        public $value;
        public $checked;

        public function __construct($obj = null) {
            if ($obj instanceof Api\Source) {
                $this->name = $obj->name;
                $this->title = str_replace('r/', '', $this->name);
                $this->value = $obj->id;
            }
        }

        /**
         * Returns any sources on the query string in an array defaulting to cookie when not present
         */
        public static function getSources() {

            // First, get all enabled sources from the database
            $sources = Lib\Cache::fetch(function() {
                return Api\Source::queryReturnAll([ 'enabled' => 1, 'type' => 'subreddit' ]);
            }, 'Controller_QueryOption_getSources', CACHE_LONG);

            $retVal = [];
            $cookieSources = isset($_COOKIE[QS_SOURCES]) ? explode(',', $_COOKIE[QS_SOURCES]) : [ 1 ];

            // Create QueryOption objects for the outgoing items and check anything that's in the user cookie
            foreach($sources as $source) {
                $obj = new QueryOption($source);
                $obj->checked = array_search($source->id, $cookieSources) !== false;
                $retVal[] = $obj;
            }

            return $retVal;
        }

    }

}