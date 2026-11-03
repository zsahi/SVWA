<?php
require __DIR__.'/../includes/auth.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']!=='GET') account_fail(405,'GET required.');
start_page('User Directory','Broken Access Control');
echo '<div class="panel table-wrap"><table><thead><tr><th>Username</th><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>';
foreach (account_db()->query('SELECT id,username,name,email,role FROM accounts ORDER BY id') as $a) {
    echo '<tr><td><a href="/profile.php?id='.(int)$a['id'].'">'.h($a['username']).'</a></td><td>'.h($a['name']).'</td><td>'.h($a['email']).'</td><td>'.h($a['role']).'</td></tr>';
}
echo '</tbody></table></div>'; end_page();
