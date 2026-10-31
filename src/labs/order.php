<?php
require __DIR__.'/../includes/app.php';
$sort=field(input(),'sort','name');
start_page('Product sorting','SQL Injection');
lab_form('Sort by','sort',$sort,'Sort products');
$sql="SELECT id, name, price FROM products ORDER BY $sort";
sql_result($sql);
end_page();
