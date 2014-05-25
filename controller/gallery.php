<?php

namespace Controller {

    use Api;
    use Lib;

    class Gallery extends BasePage {

        public static function render() {

            $new = Lib\Url::GetBool('new');
            $id = Lib\Url::Get('post', null);
            $from = Lib\Url::Get('from', null);
            $id = $new ? base_convert($id, 36, 10) : $id;

            // If coming from a reddit URL, get the post ID
            if ($from === 'reddit') {
                $id = self::_getFromRedditId($id);
            }

            if (is_numeric($id)) {
                $retVal = Api\PostData::getGallery($id);
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

    }

}