<?php

require('lib/aal.php');

$url = Lib\Url::get('url', null);
if ($url) {
    readfile($url);
    exit;
}