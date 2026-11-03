<?php
require __DIR__.'/includes/auth.php';
account_post();
$_SESSION=[];
session_destroy();
setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
account_redirect('/login.php');
