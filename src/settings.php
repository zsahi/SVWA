<?php
require __DIR__.'/includes/app.php';
$saved=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (in_array($_POST['submission'] ?? '', ['get','post','json','multipart'], true)) $_SESSION['submission']=$_POST['submission'];
    $_SESSION['show_query']=isset($_POST['show_query']);
    $_SESSION['show_errors']=isset($_POST['show_errors']);
    $saved=true;
}
start_page('Settings','Settings');
?>
<p class="lead">Configure requests and SQL output for this browser session.</p>
<?php if ($saved): ?><div class="notice" role="status">Settings saved.</div><?php endif ?>
<form method="post" class="panel settings"><label for="submission">Submission type</label><select id="submission" name="submission"><?php foreach (['get'=>'URL-encoded GET','post'=>'URL-encoded POST','json'=>'JSON POST','multipart'=>'Multipart POST'] as $value=>$label): ?><option value="<?=$value?>" <?=$_SESSION['submission']===$value?'selected':''?>><?=$label?></option><?php endforeach ?></select><p class="muted">Applies to SQL, reflected XSS and guestbook forms. File uploads use multipart; DOM labs use the URL.</p><label class="check"><input type="checkbox" name="show_query" <?=$_SESSION['show_query']?'checked':''?>> Display SQL queries</label><label class="check"><input type="checkbox" name="show_errors" <?=$_SESSION['show_errors']?'checked':''?>> Display database errors</label><button>Save settings</button></form>
<?php end_page(); ?>
