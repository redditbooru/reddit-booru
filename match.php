<script type="text/javascript">
window.parent.imageSearchCallback(<?php

require('lib/aal.php');

$out = null;

if (isset($_FILES['uplImage']) && is_uploaded_file($_FILES['uplImage']['tmp_name'])) {

	$tmpFile = 'cache/' . uniqid();
	if (move_uploaded_file($_FILES['uplImage']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/' . $tmpFile)) {
        $getSources = Lib\Url::Get('sources', '1');
        $postSources = Lib\Url::Post('sources');
        $sources = $postSources !== null ? $postSources : $getSources;
		$out = Controller\Images::getByImage([ 'imageUri' => $_SERVER['DOCUMENT_ROOT'] . '/' . $tmpFile, 'count' => 6, 'getSource' => true, 'sources' => $sources ]);
	}

}

echo json_encode($out);

?>);
</script>