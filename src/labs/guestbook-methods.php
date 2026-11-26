<?php
require __DIR__.'/../includes/app.php';
require __DIR__.'/../includes/guestbook-data.php';
$httpLesson = true;
$error = '';
start_page('HTTP Methods','HTTP Basics');
require __DIR__.'/../includes/guestbook-page.php';
end_page();
