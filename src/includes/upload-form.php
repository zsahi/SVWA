<form class="panel" method="post" enctype="multipart/form-data"><label for="file">Choose a file</label><input type="file" id="file" name="file" required><button>Upload file</button></form>
<?php if ($notice): ?><div class="panel result"><?=h($notice)?><?php if ($link): ?> <a href="<?=h($link)?>" target="_blank" rel="noopener">Open uploaded file ↗</a><?php endif ?></div><?php endif ?>
