<p class="lead">Clear saved lab payloads or restore the original SQL exercise records.</p>
<?php if ($notice): ?><div class="notice" role="status"><?=h($notice)?></div><?php endif ?>
<?php if ($error): ?><div class="notice" role="alert"><?=h($error)?></div><?php endif ?>
<form class="panel lab-form" method="post">
<?php csrf_input(); ?>
<input type="hidden" name="action" value="reset-lab">
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
