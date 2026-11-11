<?php
require __DIR__.'/includes/app.php';
// Retain the existing session-settings POST interface for lab clients.
if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($_POST['submission'] ?? '', ['get','post','json','multipart'], true)) {
    $_SESSION['submission']=$_POST['submission'];
}
start_page('Settings','Settings');
?>
<p class="lead">Additional settings will be available here when needed.</p>
<?php end_page(); ?>
