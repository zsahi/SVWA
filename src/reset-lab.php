<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/reset-lab.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    account_post();
    $scope = field($_POST, 'scope');
    if (!in_array($scope, ['guestbook', 'labs'], true) || field($_POST, 'confirm') !== 'yes') {
        http_response_code(400);
        $error = 'Choose a reset option and confirm that its data can be deleted.';
    } else {
        try {
            reset_lab_data(db(), $scope);
            $_SESSION['reset_notice'] = $scope === 'guestbook'
                ? 'Stored XSS guestbook messages cleared.'
                : 'Lab data reset. SQL exercise records restored and Stored XSS messages cleared.';
            account_redirect('/reset-lab.php');
        } catch (Throwable $exception) {
            error_log('SVWA lab reset failed: '.$exception->getMessage());
            http_response_code(500);
            $error = 'The reset failed. No changes were saved. Check the database connection and server logs.';
        }
    }
}
$notice = $_SESSION['reset_notice'] ?? '';
unset($_SESSION['reset_notice']);
start_page('Reset lab data', 'Reset');
?>
<p class="lead">Clear saved lab payloads or restore the original SQL exercise records.</p>
<?php if ($notice): ?><div class="notice" role="status"><?=h($notice)?></div><?php endif ?>
<?php if ($error): ?><div class="notice" role="alert"><?=h($error)?></div><?php endif ?>
<form class="panel lab-form" method="post">
<?php csrf_input(); ?>
<label for="scope">What would you like to reset?</label>
<select id="scope" name="scope">
<option value="guestbook">Clear Stored XSS messages</option>
<option value="labs">Reset lab data (SQL exercises and Stored XSS)</option>
</select>
<p><strong>Clear Stored XSS messages:</strong> permanently deletes every guestbook message and saved payload.</p>
<p><strong>Reset lab data:</strong> also restores the original products and SQL lab users.</p>
<p>Login accounts, student results, uploads, and saved reports are preserved. This does not rebuild missing database tables.</p>
<label class="check"><input type="checkbox" name="confirm" value="yes" required> I understand the selected lab data will be permanently deleted.</label>
<button type="submit">Reset selected data</button>
</form>
<a href="/">Back to dashboard</a>
<?php end_page(); ?>
