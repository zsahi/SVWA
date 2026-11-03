<?php
// Reuses webapp's shared login/register/profile flow with SVWA layout and username login.
require_once __DIR__.'/auth.php';
$isProfile=$accountPage==='profile'; $isRegister=$accountPage==='register';
$a=$isProfile ? require_account() : null;
$name=$a['name'] ?? ''; $email=$a['email'] ?? ''; $username=$a['username'] ?? ''; $error=''; $saved=false;
if ($isProfile) {
    // No request selector or role field is accepted by the self-service editor.
    foreach (['id','user_id','account_id','username','role'] as $key) {
        if (array_key_exists($key,$_GET) || array_key_exists($key,$_POST)) account_fail(403,'Profile target and role cannot be changed.');
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    account_post();
    $name=trim(field($_POST,'name')); $email=strtolower(trim(field($_POST,'email')));
    $password=field($_POST,'password');
    if (!$isProfile) $username=trim(field($_POST,'username'));
    if (($isProfile || $isRegister) && ($name==='' || strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254)) $error='Enter a name (up to 100 bytes) and a valid email.';
    elseif ($isRegister && (!preg_match('/\A[a-zA-Z0-9_]{3,60}\z/',$username) || strlen($password)<8 || strlen($password)>72 || $password!==field($_POST,'confirm_password'))) $error='Use a username of 3–60 letters, digits or underscores and matching passwords of 8–72 bytes.';
    else try {
        if ($isProfile) {
            $q=account_db()->prepare('UPDATE accounts SET name=?,email=? WHERE id=?');
            $q->execute([$name,$email,$a['id']]); $saved=true;
        } elseif ($isRegister) {
            $q=account_db()->prepare("INSERT INTO accounts (username,name,email,password_hash,role) VALUES (?,?,?,?,'user')");
            $q->execute([$username,$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
            account_sign_in(['id'=>account_db()->lastInsertId(),'session_version'=>1]);
            account_redirect('/profile.php');
        } else {
            $q=account_db()->prepare('SELECT id,password_hash,session_version FROM accounts WHERE username=?'); $q->execute([$username]); $login=$q->fetch(PDO::FETCH_ASSOC);
            if ($login && strlen($password)<=72 && password_verify($password,$login['password_hash'])) { account_sign_in($login); account_redirect('/profile.php'); }
            $error='Invalid username or password.';
        }
    } catch (PDOException $e) { $error=($e->errorInfo[1] ?? null)===1062 ? 'Username or email already registered.' : 'Account service unavailable.'; }
}
start_page($isProfile ? 'Edit My Profile' : ($isRegister ? 'Register' : 'Login'),'Accounts');
if ($error) echo '<p class="notice">'.h($error).'</p>';
if ($saved) echo '<p class="notice">Profile saved.</p>';
echo '<form method="post" class="panel settings">'; csrf_input();
if (!$isProfile) account_input('username','Username',$username);
if ($isProfile || $isRegister) { account_input('name','Name',$name); account_input('email','Email',$email,'email'); }
if (!$isProfile) account_input('password','Password','','password');
if ($isRegister) account_input('confirm_password','Confirm password','','password');
echo '<button>'.($isProfile ? 'Save profile' : ($isRegister ? 'Create account' : 'Login')).'</button></form>';
if ($accountPage==='login') {
    echo '<section class="panel settings"><h2>Default training accounts</h2><div class="table-wrap"><table><thead><tr><th>Username</th><th>Password</th><th>Role</th></tr></thead><tbody>';
    foreach ([['admin','password','Administrator'],['user1','password1','Ordinary user'],['user2','password2','Ordinary user']] as [$defaultUsername,$defaultPassword,$defaultRole]) {
        echo '<tr><td><code>'.h($defaultUsername).'</code></td><td><code>'.h($defaultPassword).'</code></td><td>'.h($defaultRole).'</td></tr>';
    }
    echo '</tbody></table></div><p class="muted">These credentials are seeded during setup/reset. If a password has been changed, use the updated password.</p></section>';
}
echo '<p><a href="/login.php">Login</a> · <a href="/register.php">Register</a> · <a href="/forgot_password.php">Forgot password?</a> · <a href="/profile.php">My Profile</a></p>';
end_page();
