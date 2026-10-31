<?php
require __DIR__.'/../includes/app.php';
$name=field(input(),'name','Student');
start_page('Profile preview','Reflected XSS');
lab_form('Display name','name',$name,'Preview');
?>
<div class="panel result"><label for="preview">Profile name</label><input id="preview" value="<?=$name?>"></div>
<?php end_page(); ?>
