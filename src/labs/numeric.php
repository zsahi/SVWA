<?php
require __DIR__.'/../includes/app.php';
$id=field(input(),'id','1');
start_page('Numeric lookup','SQL Injection');
lab_form('Product ID','id',$id,'Find product');
$sql="SELECT id, name, price FROM products WHERE id = $id";
sql_result($sql);
end_page();
