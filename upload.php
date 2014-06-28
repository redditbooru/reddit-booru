<?php

// Legacy redirect

if (isset($_GET['url'])) {
    header('Location: /?dialog=upload&rehost=' . urlencode($_GET['url']));
} else {
    header('Location: /');
}