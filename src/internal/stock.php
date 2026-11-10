<?php
require __DIR__.'/../includes/internal-only.php';
$items=['1'=>'Canvas Backpack: 24 available','2'=>'Wireless Keyboard: 12 available'];
$item=$_GET['item'] ?? '1';
if (!is_string($item) || !isset($items[$item])) {
    http_response_code(404);
    exit("Product not found.\n");
}
echo $items[$item]."\n";
