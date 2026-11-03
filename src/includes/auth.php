<?php
// Adapted from netscan/webapp/auth.php and account_page.php.
require_once __DIR__.'/app.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
function account_db(): PDO {
    static $db;
    return $db ??= new PDO('mysql:host='.(getenv('DB_HOST') ?: 'db').';dbname='.(getenv('ACCOUNT_DB_NAME') ?: 'svwa_accounts').';charset=utf8mb4', getenv('ACCOUNT_DB_USER') ?: 'svwa_accounts', getenv('ACCOUNT_DB_PASSWORD') ?: 'svwa_accounts_local', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
}
function account_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function account_redirect(string $path): void { header('Location: '.$path, true, 303); exit; }
function account_fail(int $status, string $message): void { http_response_code($status); start_page('Account access'); echo '<p class="notice">'.h($message).'</p>'; end_page(); exit; }
function account_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') account_fail(405, 'POST required.');
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(account_token(), $_POST['csrf'])) account_fail(403, 'Invalid form token.');
}
function current_account(): ?array {
    if (empty($_SESSION['account_id'])) return null;
    $q=account_db()->prepare('SELECT id,username,name,email,role,session_version FROM accounts WHERE id=?');
    $q->execute([$_SESSION['account_id']]);
    $a=$q->fetch(PDO::FETCH_ASSOC);
    if (!$a || $a['session_version'] !== ($_SESSION['account_version'] ?? null)) { unset($_SESSION['account_id'], $_SESSION['account_version']); return null; }
    return $a;
}
function require_account(): array {
    $a=current_account();
    if (!$a) account_redirect('/login.php');
    return $a;
}
function require_admin(): array {
    $a=require_account();
    if ($a['role'] !== 'admin') account_fail(403, 'Administrator access required.');
    return $a;
}
function account_sign_in(array $a): void {
    session_regenerate_id(true);
    $_SESSION['account_id']=(int)$a['id'];
    $_SESSION['account_version']=(int)$a['session_version'];
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
function csrf_input(): void { echo '<input type="hidden" name="csrf" value="'.h(account_token()).'">'; }
function account_input(string $key, string $label, string $value='', string $type='text'): void {
    echo '<label for="'.h($key).'">'.h($label).'</label><input id="'.h($key).'" name="'.h($key).'" type="'.h($type).'" value="'.h($value).'" required>';
}
