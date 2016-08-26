<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    class Gallery extends BasePage {

        public static function render() {

            parent::render();

            $action = Lib\Url::Get('action');

            Lib\Display::addKey('use_min_js', USE_MIN_JS);
            Lib\Display::addKey('js_version', JS_VERSION);

            switch ($action) {
                case 'user':
                    self::_userGalleries();
                    break;
                default:
                    self::_displayGallery();
            }

        }

        /**
         * Returns a post ID from a reddit ID
         */
        private static function _getFromRedditId($id) {

            $cacheKey = 'Controller:Gallery:_getFromRedditId_' . $id;
            $retVal = Lib\Cache::Get($cacheKey);

            if (false === $retVal) {
                $post = Api\Post::query([ 'externalId' => $id ]);
                $retVal = null;
                if ($post && $post->count) {
                    $row = new Api\Post(Lib\Db::Fetch($post));
                    $retVal = $row->id;
                }
                Lib\Cache::Set($cacheKey, $retVal);
            }

            return $retVal;
        }

        private static function _displayGallery() {
            $new = Lib\Url::GetBool('new');
            $id = Lib\Url::Get('post', null);
            $from = Lib\Url::Get('from', null);
            $id = $new ? base_convert($id, 36, 10) : $id;

            // If coming from a reddit URL, get the post ID
            if ($from === 'reddit') {
                $id = self::_getFromRedditId($id);
            }

            if (is_numeric($id)) {
                $retVal = Api\PostData::getGallery($id, $from === 'reddit');
            }

            if (count($retVal)) {
                Lib\Display::addKey('title', $retVal[0]->title);
                Lib\Display::addKey('pageTitle', $retVal[0]->title . ' - redditbooru');
                self::setOgData($retVal[0]->title, $retVal[0]->cdnUrl);
            }

            self::$renderKeys['images'] = $retVal;
            Lib\Display::addKey('imagesDisplay', 'gallery');
            Lib\Display::renderAndAddKey('body', 'gallery', self::$renderKeys);
        }

        private static function _userGalleries() {
            $user = Api\User::getCurrentUser();
            if (!$user) {
                // TODO - internal redirect
                header('Redirect: /login/');
                exit;
            }

            $page = Lib\Url::GetInt('p', 1);
            $galleries = Api\Post::getUserGalleries($user->id, $page);
            self::$renderKeys['galleries'] = $galleries->results;
            $userProfile = Api\PostData::getUserProfile($user->name);

            // Build up the paging links
            $currentPage = $galleries->paging->current;
            $pages = [];
            for ($i = 0; $i < $galleries->paging->total; $i++) {
                $pages[] = [
                    'page' => $i + 1,
                    'current' => ($i + 1) === $currentPage
                ];
            }
            self::$renderKeys['paging'] = [
                'hasPrev' => $currentPage > 1,
                'hasNext' => $currentPage < $galleries->paging->total,
                'next' => $currentPage + 1,
                'prev' => $currentPage - 1,
                'pages' => $pages
            ];

            Lib\Display::addKey('title', 'My Galleries');
            Lib\Display::renderAndAddKey('supporting', 'userProfile', $userProfile);
            Lib\Display::renderAndAddKey('body', 'userGalleries', self::$renderKeys);

        }

    }

}
