<?php
require __DIR__.'/includes/auth.php';
$token=field($_POST,'token',field($_GET,'token')); $error=''; $done=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    account_post(); $password=field($_POST,'password');
    if (strlen($password)<8 || strlen($password)>72 || $password!==field($_POST,'confirm_password')) $error='Use matching passwords of 8–72 bytes.';
    elseif (!preg_match('/\A[a-f0-9]{64}\z/',$token)) $error='Invalid or expired reset link.';
    else {
        $db=account_db(); $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT account_id FROM password_resets WHERE token_hash=? AND expires_at>UTC_TIMESTAMP() FOR UPDATE'); $q->execute([hash('sha256',$token)]); $id=$q->fetchColumn();
            if ($id) {
                $q=$db->prepare('UPDATE accounts SET password_hash=?,session_version=session_version+1 WHERE id=?'); $q->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
                $q=$db->prepare('DELETE FROM password_resets WHERE account_id=?'); $q->execute([$id]); $done=true;
            } else $error='Invalid or expired reset link.';
            $db->commit();
        } catch (Throwable $e) { $db->rollBack(); throw $e; }
    }
}
start_page('Reset Password','Accounts');
if ($done) echo '<p class="notice">Password changed. Existing sessions have been invalidated. <a href="/login.php">Login</a></p>';
else {
    if ($error) echo '<p class="notice">'.h($error).'</p>';
    echo '<form method="post" class="panel settings">'; csrf_input(); echo '<input type="hidden" name="token" value="'.h($token).'">';
    account_input('password','New password','','password'); account_input('confirm_password','Confirm password','','password'); echo '<button>Reset password</button></form>';
}
end_page();
