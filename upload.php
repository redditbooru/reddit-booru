<?php

require('lib/aal.php');

$url = Lib\Url::Get('url', null, $_POST);
$redirect = Lib\Url::GetBool('redirect', $_POST);

$retVal = new stdClass;
$retVal->success = false;
$retVal->uploadId = Lib\Url::Get('hdnUploadId', null, $_POST);

if (isset($_FILES['uplPicture']) && is_uploaded_file($_FILES['uplPicture']['tmp_name'])) {
        
    // Give the file a temporary filename
    $fileName = $_SERVER['DOCUMENT_ROOT'] . '/cache/' . uniqid();
    if (move_uploaded_file($_FILES['uplPicture']['tmp_name'], $fileName)) {
        $image = Api\Image::createFromImage($fileName);
        if (null != $image) {
            $retVal->success = true;
            $retVal->image = $image;
        } else {
            $retVal->message = 'Invalid image';
        }
        unlink($fileName);
    } else {
        $retVal->message = 'Unable to move uploaded file';
    }
    
    if ($redirect && $retVal->success) {
        header('Location: ' . $retVal->image->cdnUrl);
        exit;
    }
    
} else if ($url && strpos($url, 'http://') === 0) {
    
    $image = Api\Image::createFromImage($url);
    if (null != $image) {
        $retVal->success = true;
        $retVal->image = $image;
    } else {
        $retVal->message = 'Invalid image';
    }
    
    if ($redirect && $retVal->success) {
        header('Location: ' . $retVal->image->cdnUrl);
        exit;
    }
    
    // Echo what we've got and bail
    echo json_encode($retVal);
    exit;
    
} else {
    $retVal->message = 'No file uploaded';
}

?>
<script type="text/javascript">
window.parent.uploadImageCallback(<?php echo json_encode($retVal); ?>);
</script>