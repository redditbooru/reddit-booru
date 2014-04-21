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

    // Dummy
    public static function registerExtension($name, $class, $type) {}

    /**
     * Given a thumbnail request, derive the thumbnail attributes and create the thumbnail
     */
    public static function createThumbFromEncodedFilename($file) {
      // Throw away the extension
      $bits = explode('.', $file);
      $bits = $bits[0];
      $bits = explode('_', $file);
      if (count($bits) === 3) {
        $url = $bits[0];
        if (substr($url, strlen($url) - 2, 2) === 'EE') {
          $url = substr($url, 0, strlen($url) - 2) . '==';
        }
        $url = base64_decode($url);
        self::createThumbnail($url, (int) $bits[1], (int) $bits[2]);
      }
    }

    /**
     * Encodes the URL for the cache filename
     */
    public static function createThumbFilename($url) {
      $url = base64_encode($url);;
      return str_replace('==', 'EE', $url);
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
        if ($image) {

            $width = $width > 0 ? $width : false;
            $height = $height > 0 ? $height : false;

            // The scheme for URL naming is set so that all the parameters can be inferred from the file name. They are
            // - URL of file, base64 encoded with trailing == removed
            // - height and width of thumbnail
            $encodedUrl = self::createThumbFilename($url);
            $outFile = $encodedUrl . '_' . $width . '_' . $height . '.jpg';

            if ($image->getNumberImages() > 0) {
                foreach ($image as $frame) {
                    $image = $frame;
                    break;
                }
            }

            $image->cropThumbnailImage($width, $height);
            $image->setFormat('JPEG');
            $image->writeImage('cache/' . $outFile);
            header('Content-Type: image/jpeg');
            readfile('cache/' . $outFile);
            exit;
        }

        unlink($tmpFile);

      } else {
        // redirect to standard "error" image
      }
    }

  }

}
