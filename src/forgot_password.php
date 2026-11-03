<?php
// Extends webapp's CSRF-protected request form with actual single-use reset tokens.
require __DIR__.'/includes/auth.php';
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    account_post();
    $email=strtolower(trim(field($_POST,'email')));
    if (filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($email)<=254) {
        $q=account_db()->prepare('SELECT id FROM accounts WHERE email=?'); $q->execute([$email]); $id=$q->fetchColumn();
        if ($id) {
            $token=bin2hex(random_bytes(32));
            $q=account_db()->prepare('INSERT INTO password_resets (token_hash,account_id,expires_at) VALUES (?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),expires_at=VALUES(expires_at)');
            $q->execute([hash('sha256',$token),$id]);
            // Local mail transport: never return reset tokens through an HTTP endpoint.
            $path='/var/lib/svwa-reset/outbox.log';
            if (file_put_contents($path, json_encode(['to'=>$email,'path'=>'/reset_password.php?token='.$token]).PHP_EOL,FILE_APPEND|LOCK_EX)===false) throw new RuntimeException('Reset delivery failed');
        }
    }
    $message='If the email is registered, a reset link has been delivered to the local training outbox. It expires in 15 minutes.';
}
start_page('Forgot Password','Accounts');
echo '<p class="lead">Local training delivery: the operator reads the outbox using the command in the Docker guide.</p>';
if ($message) echo '<p class="notice">'.h($message).'</p>';
echo '<form class="panel settings" method="post">'; csrf_input(); account_input('email','Account email','','email'); echo '<button>Request reset</button></form>';
end_page();
