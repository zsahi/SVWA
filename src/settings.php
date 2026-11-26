<?php
require __DIR__.'/includes/auth.php';
// Retain the existing session-settings POST interface for lab clients.
if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($_POST['submission'] ?? '', ['get','post','json','multipart'], true)) {
    $_SESSION['submission']=$_POST['submission'];
}
$resetRequested = field($_POST, 'action') === 'reset-lab';
$resetReturnPath = '/settings.php';
require __DIR__.'/includes/reset-lab-handler.php';
start_page('Settings','Settings');
?>
<h2 id="reset-lab">Reset lab data</h2>
<?php require __DIR__ . '/includes/reset-lab-form.php'; ?>
<?php end_page(); ?>
