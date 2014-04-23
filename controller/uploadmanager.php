<?php

namespace Controller {

    use Lib;
    use stdClass;

    class UploadManager implements Page {

        public static function render() {

            $imageUrl = Lib\Url::Get('imageUrl', null);
            $action = Lib\Url::Get('action', null);
            $retVal = null;

            switch ($action) {
                case 'upload':
                    if ($imageUrl) {
                        $retVal = self::_uploadFromUrl($imageUrl);
                    } else if (count($_POST) && count($_FILES)) {
                        self::_uploadFromFile();
                    }
                    break;
                case 'status':
                    $retVal = self::_checkUploadStatus();
                    break;
                default:
                    return Redditbooru::render();
                    break;
            }

            header('Content-Type: text/javascript; charset=utf-8');
            echo json_encode($retVal);
            exit;

        }

        private static function _uploadFromUrl($imageUrl) {
            $retVal = Lib\ImageLoader::fetchImage($imageUrl);
            unset($retVal->data);
            $retVal->thumb = Thumb::createThumbFilename($imageUrl);
            $retVal->uploadId = $imageUrl;
            return $retVal;
        }

        private static function _uploadFromFile() {
            $uploadId = $_POST['uploadId'];
            Lib\Display::setLayout('upload');
            $out = new stdClass;
            $out->error = true;
            if (is_numeric($uploadId)) {
                $out->uplaodId = $uploadId;
                $file = $_FILES['upload'];
                $fileName = sys_get_temp_dir() . '/image_' . $uploadId;

                if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $fileName)) {

                    // Load the image into cache
                    if (Lib\ImageLoader::fetchImage($fileName)) {
                        $out->error = false;
                        $out->thumb = Thumb::createThumbFilename($fileName);
                    } else {
                        $out->message = 'Invalid image';
                    }

                } else {
                    $out->message = 'Invalid file upload';
                }

                Lib\Display::addKey('uploadId', $uploadId);
                Lib\Display::addKey('data', null);
            }
            Lib\Display::render();
            exit;
        }

        private static function _checkUploadStatus() {
            $retVal = Lib\Http::getActiveDownloads();
            return count($retVal) > 0 ? $retVal : null;
        }

    }

}