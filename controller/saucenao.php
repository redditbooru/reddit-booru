<?php

namespace Controller {

    use Lib;

    class SauceNAO implements Page {

        const MIN_SIMILARITY = 90;
        const PIXIV_LINK = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id={{ID}}';

        public static function render() {
            self::getSource(Lib\Url::Get('url'));
            exit;
        }

        public static function getSource($url) {
            $data = file_get_contents('http://localhost:' . SAUCENAO_PORT . '/' . urlencode($url));

            $data = $data ? json_decode($data) : null;
            if ($data && isset($data->results) && count($data->results)) {
                $result = $data->results[0];
                if ($result->header->similarity >= self::MIN_SIMILARITY) {
                    $data = [
                        'title' => $result->data->title . ' drawn by ' . $result->data->member_name,
                        'url' => str_replace('{{ID}}', $result->data->pixiv_id, self::PIXIV_LINK)
                    ];
                }
            } else {
                $data = null;
            }

            echo json_encode($data);
        }

    }

}