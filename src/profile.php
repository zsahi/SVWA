<?php
require __DIR__.'/includes/auth.php';
$a=require_account();
// A supplied selector is allowed only for administrators, and only a numeric ID.
foreach (['username','user_id','account_id','role'] as $key) if (isset($_GET[$key])) account_fail(403,'Unsupported profile selector.');
if (isset($_GET['id'])) {
    $id=field($_GET,'id');
    if (!ctype_digit($id)) account_fail(400,'Invalid account ID.');
    if ($a['role']!=='admin' && (int)$id!==(int)$a['id']) account_fail(403,'You can only view your own profile.');
    $q=account_db()->prepare('SELECT id,username,name,email,role FROM accounts WHERE id=?'); $q->execute([$id]);
    $a=$q->fetch(PDO::FETCH_ASSOC);
    if (!$a) account_fail(404,'Account not found.');
}
if ($_SERVER['REQUEST_METHOD']!=='GET') account_fail(405,'GET required.');
start_page('User Profile','Accounts');
echo '<div class="panel"><dl class="profile-details">';
foreach (['username','name','email','role'] as $key) echo '<dt>'.h(ucfirst($key)).'</dt><dd>'.h($a[$key]).'</dd>';
echo '</dl></div><p><a href="/edit_profile.php">Edit my own profile</a></p><form action="/logout.php" method="post">'; csrf_input(); echo '<button>Logout</button></form>';
end_page();
