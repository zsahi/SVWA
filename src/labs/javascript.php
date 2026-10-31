<?php
require __DIR__.'/../includes/app.php';
$name=field(input(),'name','Student');
start_page('Welcome message','Reflected XSS');
lab_form('Your name','name',$name,'Preview');
?>
<div class="panel result" id="welcome"></div>
<script>const name = '<?=$name?>'; document.getElementById('welcome').textContent = 'Welcome, ' + name + '.';</script>
<?php end_page(); ?>
