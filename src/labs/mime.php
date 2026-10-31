<?php
require __DIR__.'/../includes/app.php';
$notice=''; $link='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $file=$_FILES['file'] ?? null;
    if (!$file || $file['error']!==UPLOAD_ERR_OK) $notice='Upload failed. Maximum file size: 2 MB.';
    elseif (!in_array($file['type'],['image/jpeg','image/png'],true)) $notice='Only JPEG and PNG images are accepted.';
    else {
        $name=bin2hex(random_bytes(6)).'-'.basename($file['name']);
        if (move_uploaded_file($file['tmp_name'],__DIR__.'/../uploads/'.$name)) { $notice='Image uploaded.'; $link='/uploads/'.rawurlencode($name); }
        else $notice='Could not save the image.';
    }
}
start_page('Image upload','File Upload');
require __DIR__.'/../includes/upload-form.php';
end_page();
