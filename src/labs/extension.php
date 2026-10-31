<?php
require __DIR__.'/../includes/app.php';
$notice=''; $link='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $file=$_FILES['file'] ?? null;
    if (!$file || $file['error']!==UPLOAD_ERR_OK) $notice='Upload failed. Maximum file size: 2 MB.';
    elseif (pathinfo($file['name'],PATHINFO_EXTENSION)==='php') $notice='PHP files are not accepted.';
    else {
        $name=bin2hex(random_bytes(6)).'-'.basename($file['name']);
        if (move_uploaded_file($file['tmp_name'],__DIR__.'/../uploads/'.$name)) { $notice='Document uploaded.'; $link='/uploads/'.rawurlencode($name); }
        else $notice='Could not save the document.';
    }
}
start_page('Document upload','File Upload');
require __DIR__.'/../includes/upload-form.php';
end_page();
