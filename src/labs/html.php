<?php
require __DIR__.'/../includes/app.php';
$data=input(); $search=field($data,'search');
start_page('Search results','Reflected XSS');
lab_form('Search keyword','search',$search,'Search');
if (isset($data['search'])) echo '<div class="panel result">Results for: '.$search.'</div>';
end_page();
