<?php
require __DIR__.'/../includes/app.php';
require __DIR__.'/../includes/guestbook-data.php';
$error = '';
$httpLesson = false;
start_page('Guestbook','Stored XSS');
require __DIR__.'/../includes/guestbook-page.php';
end_page();
