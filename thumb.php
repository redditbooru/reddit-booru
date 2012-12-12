<?php

require('api/awwnime.php');

$file = isset($_GET['file']) ? $_GET['file'] : false;
$outWidth = isset($_GET['width']) ? $_GET['width'] : false;
$outHeight = isset($_GET['height']) ? $_GET['height'] : false;
$overflow = isset($_GET['overflow']) ? $_GET['overflow'] : 'crop';

if ($file && ($outWidth || $outHeight)) {

	$outFile = md5($file . '_' . $outWidth . '_' . $outHeight);

	// Check to see if there's a cached thumbnail already
	if (file_exists ('cache/' . $outFile . '.png') && filemtime('cache/' . $outFile . '.png') > filemtime($file) && filesize('cache/' . $outFile . '.png') > 1024) {
		header ('Location: cache/' . $outFile . '.png');
		exit;
	}
	
	$tmpFile = 'cache/' . uniqid();
	if (strpos($file, 'http') === false) {
		$tmpFile = $file;
	} else {
		if (!Lib\Awwnime::downloadImage($file, $tmpFile)) {
			$tmpFile = false;
		}
	}
	
	if ($tmpFile) {
	
		$img = Lib\Awwnime::loadImage($tmpFile);
		if ($img) {
			
			// Get the dimensions of the image and figure out the appropriate, rescaled size
			$img = $img->image;
			$imgWidth = imagesx($img);
			$imgHeight = imagesy($img);
			$scaleWidth = $outWidth;
			$scaleHeight = $outHeight;
			$x = $y = 0;

			// Scale depending on what dimensions were passed
			if (!$outWidth && $outHeight) {
				$outWidth = $scaleWidth = floor($imgWidth / $imgHeight * $outHeight);
			} else if (!$outHeight && $outWidth) {
				$outHeight = $scaleHeight = floor($imgHeight / $imgWidth * $outWidth);
			} else {
				
				switch ($overflow) {
					case 'center':
						break;
					case 'squash':
						break;
					default:
						if ($imgWidth > $imgHeight) {
							$scaleHeight = $outHeight;
							$scaleWidth = floor($imgWidth / $imgHeight * $outHeight);
						} else {
							$scaleWidth = $outWidth;
							$scaleHeight = floor($imgHeight / $imgWidth * $outWidth);
						}
						break;
				}
				
			}
			
			// Create the new image and copy the resized one over
			$out = imagecreatetruecolor ($outWidth, $outHeight);
			imagecopyresampled ($out, $img, $x, $y, 0, 0, $scaleWidth, $scaleHeight, $imgWidth, $imgHeight);
			
			//unlink($tmpFile);
			
			// Save out the file and do a redirect
			imagepng ($out, './cache/' . $outFile . '.png');
			header ('Expires: ' . date('r', strtotime('+1 year')));
			header ('Location: ./cache/' . $outFile . '.png');
			
		}
	}
	
}