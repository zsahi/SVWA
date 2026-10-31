<?php
require __DIR__.'/../includes/app.php';
$id=field(input(),'id','1');
start_page('Availability check','SQL Injection');
lab_form('Product ID','id',$id,'Check availability');
$sql="SELECT id FROM products WHERE id = $id";
sql_result($sql,true);
end_page();
