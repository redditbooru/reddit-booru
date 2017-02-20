<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    class BasePage implements Page {

        protected static $renderKeys = [];
        protected static $enabledSources;
        protected static $tests;

        public static function render() {

            self::$tests = new stdClass;

            $user = Api\User::getCurrentUser();
            Lib\Display::addKey('user', $user);

            if ($user instanceof Api\User) {
                Lib\TestBucket::initialize($user->id);
                Lib\Display::addKey('csrfToken', $user->csrfToken);
            }

            Lib\Display::addKey('phpSessionUpload', ini_get("session.upload_progress.name"));

            // Get sources
            $sources = QueryOption::getSources();
            $enabledSources = [];

            // If there are no sources, kill everything now and show a friendly "no sources" page
            if (!$sources || !count($sources)) {
                Lib\Display::setLayout('no_sources');
                Lib\Display::render();
                exit;
            }

            // nsfw display flag
            Lib\Display::addKey('showNsfw', Lib\Url::GetBool('showNsfw', $_COOKIE));

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

            self::addTestToOutput('enableMobile');
            self::addTestToOutput('showRedditControls');
            self::addTestToOutput('sourceFinder');

            Lib\Display::addKey('sources', $sources);
            Lib\Display::addKey('tests', self::$tests);

        }

        protected static function setOgData($title, $image) {
            $ogData = new stdClass;
            $ogData->title = $title;
            $ogData->image = $image;
            $ogData->url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            Lib\Display::addKey('ogData', $ogData);
        }

        protected static function addTestToOutput($key) {
            self::$tests->$key = Lib\TestBucket::get($key);
        }

        protected static function getPageSubdomain() {
            $retVal = false;
            if (preg_match('/([\w]+)\.(redditbooru|awwni)\.[\w]{2,3}/is', $_SERVER['HTTP_HOST'], $matches)) {
                $retVal = $matches[1];
            }
            return $retVal;
        }

    }

}