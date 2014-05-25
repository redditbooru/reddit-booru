<?php

namespace Controller {

    use Api;
    use Lib;

    class BasePage implements Page {

        protected static $renderKeys;
        protected static $enabledSources;

        public static function render() {

            self::$renderKeys = [];

            // Get sources
            $sources = QueryOption::getSources();
            $enabledSources = [];

            // If there were sources passed on the query string, use those for image fetchery. Fall back on cookies
            $qsSources = Lib\Url::Get('sources', null);
            if ($qsSources) {
                $enabledSources = explode(',', $qsSources);
            } else {
                foreach ($sources as $source) {
                    if ($source->checked) {
                        $enabledSources[] = $source->value;
                    }
                }
            }
            self::$enabledSources = $enabledSources;

            self::$renderKeys['sources'] = json_encode($sources);

        }

    }

}