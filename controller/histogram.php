<?php

namespace Controller {

  use Api;
  use Lib;

  class Histogram implements Page {

    const IMAGE_WIDTH = 1280;
    const IMAGE_HEIGHT = 720;

    public static function render() {
      $image = Lib\Url::Get('image');
      if (strpos('.', $image) !== false) {
        $image = array_pop(explode('.', $image));
      }

      $imageId = base_convert($image, 36, 10);
      $image = Api\Image::getById($imageId);
      if ($image) {
        $step = self::IMAGE_WIDTH / 3;
        $imgOut = imagecreatetruecolor(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        $dataSet = [
          [
            'prop' => 'histR',
            'color' => imagecolorallocate($imgOut, 255, 0, 0)
          ],
          [
            'prop' => 'histG',
            'color' => imagecolorallocate($imgOut, 0, 255, 0)
          ],
          [
            'prop' => 'histB',
            'color' => imagecolorallocate($imgOut, 0, 0, 255)
          ]
        ];
        foreach ($dataSet as $item) {
          $prop = $item['prop'] . '1';
          $color = $item['color'];
          $lastY = round($image->$prop * self::IMAGE_HEIGHT);
          for ($i = 2; $i < 5; $i++) {
            $prop = $item['prop'] . $i;
            $y = round($image->$prop * self::IMAGE_HEIGHT);
            imageline($imgOut, ($i - 2) * $step, self::IMAGE_HEIGHT - $lastY, ($i - 1) * $step, self::IMAGE_HEIGHT - $y, $color);
            $lastY = $y;
          }
        }
        header('Content-Type: image/png');
        imagepng($imgOut);
        exit;
      } else {
        http_response_code(404);
        exit;
      }

    }

  }

}