<?php
require __DIR__.'/../includes/app.php';
$search=field(input(),'search','Travel');
start_page('Product search','SQL Injection');
lab_form('Search products','search',$search,'Search');
$sql="SELECT id, name, price FROM products WHERE name LIKE '%$search%'";
sql_result($sql);
end_page();
