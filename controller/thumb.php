<?php

namespace Controller {

  use Lib;
  use Imagick;

  class Thumb implements Page {

    public static function render() {

      $file = Lib\Url::Get('file', null);

      if (!$file) {
        http_response_code(404);
        exit;
      } else {
        self::createThumbFromEncodedFilename($file);
      }

    }

    /**
     * Given a thumbnail request, derive the thumbnail attributes and create the thumbnail
     */
    public static function createThumbFromEncodedFilename($file) {
      // Throw away the extension
      $bits = explode('.', $file);
      $bits = explode('_', $bits[0]);

      // If a height and width were passed, resize and such. Otherise, just pass the data through
      if (count($bits) === 3) {
        $url = self::decodeThumbFilename($bits[0]);
        self::createThumbnail($url, (int) $bits[1], (int) $bits[2]);
      } else {
        self::passThrough(base64_decode($bits[0]));
      }
    }

    /**
     * Encodes a URL for the cache filename
     */
    public static function createThumbFilename($url) {
      return THUMBNAIL_PATH . str_replace([ '=', '/' ], [ '-', '_' ], base64_encode($url));
    }

    /**
     * Decodes an encoded thumbnail URL
     */
    public static function decodeThumbFilename($url) {
      $url = str_replace(THUMBNAIL_PATH, '', $url);
      return base64_decode(str_replace([ '-', '_' ], [ '=', '/' ], $url));
    }

    /**
     * Creates a thumbnail of the URL at the specified width and height the saves/displays it
     */
    public static function createThumbnail($url, $width, $height) {

      // Take advantage of the mongo cache by loading through the image loader
      $image = Lib\ImageLoader::fetchImage($url);
      if ($image) {

        $tmpFile = tempnam(sys_get_temp_dir(), 'thumb_');
        file_put_contents($tmpFile, $image->data);
        $image = new Imagick($tmpFile);
        unlink($tmpFile);

        if ($image) {

            $width = $width > 0 ? $width : false;
            $height = $height > 0 ? $height : false;

            // The scheme for URL naming is set so that all the parameters can be inferred from the file name. They are
            // - URL of file, base64 encoded with trailing == removed
            // - height and width of thumbnail
            $encodedUrl = self::createThumbFilename($url);
            $outFile = '.' . $encodedUrl . '_' . $width . '_' . $height . '.jpg';

            if ($image->getNumberImages() > 0) {
                foreach ($image as $frame) {
                    $image = $frame;
                    break;
                }
            }

            $image->cropThumbnailImage($width, $height);
            $image->setFormat('JPEG');
            $image->writeImage($outFile);
            header('Content-Type: image/jpeg');
            readfile($outFile);
            exit;
        }


      } else {
        // redirect to standard "error" image
      }
    }

    public static function passThrough($url) {
      $image = Lib\ImageLoader::fetchImage($url);
      if ($image) {
        $contentType = 'image/';
        switch ($image->type) {
          case IMAGE_TYPE_JPEG:
            $contentType .= 'jpeg';
            break;
          case IMAGE_TYPE_GIF:
          case IMAGE_TYPE_PNG:
            $contentType .= $image->type;
        }

        file_put_contents(self::createThumbFilename($url) . '.jpg', $image->data);

        header('Content-Type: ' . $contentType);
        echo $image->data;
        exit;
      }
    }

  }

}
