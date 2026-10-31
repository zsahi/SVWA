<?php
require __DIR__.'/../includes/app.php';
$data=input(); $error='';
try {
    if (isset($data['message']) && trim(field($data,'message'))!=='') {
        $statement=db()->prepare('INSERT INTO messages (author,message) VALUES (?,?)');
        $statement->execute([substr(field($data,'author','Student'),0,80),field($data,'message')]);
        if (!str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) { header('Location: /labs/guestbook.php',true,303); exit; }
    }
    $messages=db()->query('SELECT author,message,created_at FROM messages ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) { $messages=[]; $error='Guestbook is unavailable.'; }
start_page('Guestbook','Stored XSS');
?>
<form class="panel" method="<?=$_SESSION['submission']==='get'?'get':'post'?>" <?=$_SESSION['submission']==='multipart'?'enctype="multipart/form-data"':''?> data-submission="<?=h($_SESSION['submission'])?>"><label for="author">Name</label><input id="author" name="author" value="Student" maxlength="80" required><label for="message">Message</label><textarea id="message" name="message" rows="4" required></textarea><button>Post message</button></form>
<?php if ($error) echo '<div class="notice">'.h($error).'</div>'; ?>
<div class="section-heading"><h2>Messages</h2><span><?=count($messages)?> recent</span></div>
<?php if (!$messages): ?><div class="panel muted">No messages yet.</div><?php endif ?>
<?php foreach ($messages as $message): ?><article class="panel"><div class="message-meta"><strong><?=h($message['author'])?></strong><time><?=h($message['created_at'])?></time></div><div class="message-body"><?=$message['message']?></div></article><?php endforeach; end_page(); ?>
