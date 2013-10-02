<?php

date_default_timezone_set('America/Chicago');

require('api/awwnime.php');

$file = isset($_GET['file']) ? $_GET['file'] : false;
$outWidth = isset($_GET['width']) ? $_GET['width'] : false;
$outHeight = isset($_GET['height']) ? $_GET['height'] : false;
$overflow = isset($_GET['overflow']) ? $_GET['overflow'] : 'crop';

if ($file && ($outWidth || $outHeight)) {

	// The scheme for URL naming is set so that all the parameters can be inferred from the file name. They are
	// - URL of file, base64 encoded with trailing == removed
	// - height and width of thumbnail
	$encodedUrl = base64_encode($file);
	$encodedUrl = substr($encodedUrl, 0, strlen($encodedUrl) - 2);

	$outFile = $encodedUrl . '_' . $outWidth . '_' . $outHeight . '.jpg';

	// Check to see if there's a cached thumbnail already
	if (file_exists ('cache/' . $outFile) && filemtime('cache/' . $outFile) > strtotime('-7 days') && filesize('cache/' . $outFile) > 1024) {
		header ('Location: cache/' . $outFile);
		exit;
	}

    $image = new Imagick($file);
    if ($image) {

        if ($image->getNumberImages() > 0) {
            foreach ($image as $frame) {
                $image = $frame;
                break;
            }
        }

        $image->cropThumbnailImage($outWidth, $outHeight);
        $image->setFormat('JPEG');
        $handle = fopen('cache/' . $outFile, 'wb');
        $image->writeImageFile($handle);
        fclose($handle);
        header('Location: cache/' . $outFile);
    }

}
