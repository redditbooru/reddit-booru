<?php

// Legacy redirect

if (isset($_GET['url'])) {
    header('Location: /?dialog=upload&imageUri' = urlencode($_GET['url']));
} else {
    header('Location: /');
}