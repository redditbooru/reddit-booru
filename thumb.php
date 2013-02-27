<?php

date_default_timezone_set('America/Chicago');

require('api/awwnime.php');

$file = isset($_GET['file']) ? $_GET['file'] : false;
$outWidth = isset($_GET['width']) ? $_GET['width'] : false;
$outHeight = isset($_GET['height']) ? $_GET['height'] : false;
$overflow = isset($_GET['overflow']) ? $_GET['overflow'] : 'crop';

if ($file && ($outWidth || $outHeight)) {

	$outFile = md5($file . '_' . $outWidth . '_' . $outHeight) . '.jpg';

	// Check to see if there's a cached thumbnail already
	if (file_exists ('cache/' . $outFile) && filemtime('cache/' . $outFile) > filemtime($file) && filesize('cache/' . $outFile) > 1024) {
		header ('Location: cache/' . $outFile);
		exit;
	}
	
    $image = new Imagick($file);
    if ($image) {
        $image->cropThumbnailImage($outWidth, $outHeight);
        $image->setFormat('JPEG');
        $handle = fopen('cache/' . $outFile, 'wb');
        $image->writeImageFile($handle);
        fclose($handle);
        header('Location: cache/' . $outFile);
    }
	
}