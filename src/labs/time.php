<?php
require __DIR__.'/../includes/app.php';
$data=input();
$id=field($data,'id','1');
start_page('Stock notification','SQL Injection');
lab_form('Product ID','id',$id,'Notify me');
if (isset($data['id'])) {
    $sql="SELECT id FROM products WHERE id = '$id'";
    if ($_SESSION['show_query']) echo '<pre class="panel">'.h($sql).'</pre>';
    try { db()->query($sql)->fetchAll(); }
    catch (PDOException $error) { if ($_SESSION['show_errors']) echo '<div class="panel">'.h($error->getMessage()).'</div>'; }
    echo '<div class="panel result">Notification request received.</div>';
}
end_page();
