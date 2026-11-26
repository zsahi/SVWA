<?php
$keyword = trim(field($_GET, 'q'));
try { $listing = guestbook_page(max(1, (int)field($_GET,'page','1')), $keyword); }
catch (PDOException $exception) { $listing=['comments'=>[], 'page'=>1, 'pages'=>1, 'total'=>0]; $error='Guestbook is unavailable.'; }
?>
<link rel="stylesheet" href="/assets/guestbook.css">
<p class="lead">Leave a comment, search by name or message, and manage it from the shared guestbook.</p>
<?php if ($httpLesson): ?>
<p>This is the same guestbook used in the <a href="/labs/guestbook.php">Stored XSS lab</a>. Comments are saved in the shared database, not limited to one note or browser session. Use your own practice comments for edits and deletion.</p>
<details class="panel"><summary>Practice with Burp</summary>
<ol>
<li>Open <code>example.com/labs/guestbook-methods.php</code> in Burp's browser. Post a comment with name <code>Student</code> and message <code>Read HTTP notes.</code>. In Proxy HTTP history, find the PUT to <code>/labs/guestbook-comments.php</code>. Its 201 response and Location header give your new comment ID. Send it to Repeater.</li>
<li>Filter comments by a keyword in the name or message (POST with q in the body), then click View on your comment. This sends GET to <code>/labs/guestbook-comments.php?id=YOUR_ID</code>. Use your actual numeric ID in Repeater.</li>
<li>In Repeater, send PUT to your comment URL with form-encoded fields <code>author=Student+One&amp;message=Try+all+six+methods.</code>. PUT replaces both editable fields; the create action also uses PUT, but at the collection URL without an ID. Click View again to load the updated comment, then click Edit.</li>
<li>Change only the name to <code>Student Two</code> and click Save changes. This sends PATCH with only <code>author</code>. Compare the response: <code>message</code> is still <code>Try all six methods.</code>. Save changes sends whichever fields you actually edited.</li>
<li>In Repeater, change the captured comment request method to OPTIONS, remove its body, and send it to the same comment URL. OPTIONS returns <code>Allow: GET, PUT, PATCH, DELETE, OPTIONS</code>. For the collection URL without an ID, Allow is <code>GET, POST, PUT, OPTIONS</code>.</li>
<li>Click Delete and confirm the selected ID. DELETE returns 204 with no body. In Repeater, resend GET for that same comment URL: GET now returns 404 with <code>{"error":"Comment not found."}</code>.</li>
</ol>
<p>These management actions use their actual HTTP methods. POST, PUT, and PATCH use form-encoded fields; GET, DELETE, and OPTIONS have no body. No CSRF token or login is required. Both guestbook forms create with PUT and search with POST, regardless of the global Submission Type. This lab uses a custom collection PUT action that creates a new ID on each successful request; do not treat repeated creation requests as retries without checking the list. In Repeater, keep automatic Content-Length updates enabled.</p>
</details>
<?php endif ?>
<div id="guestbook-manager" data-page="<?=h($listing['page'])?>" data-pages="<?=h($listing['pages'])?>" data-keyword="<?=h($keyword)?>">
<form id="comment-create" class="panel lab-form" method="post" action="/labs/guestbook-comments.php" data-comment-create>
<h2>Leave a comment</h2>
<label for="author">Name</label><input id="author" name="author" value="Student" maxlength="80" required>
<label for="message">Message</label><textarea id="message" name="message" rows="4" maxlength="10000" required></textarea>
<button type="submit">Post comment</button>
</form>
<?php if ($error): ?><p class="notice" role="alert"><?=h($error)?></p><?php endif ?>
<p id="comment-status" role="status" aria-live="polite"></p>
<form id="comment-filter" class="panel lab-form" method="post" action="/labs/guestbook-comments.php">
<label for="comment-keyword">Search comments</label>
<div class="input-row"><input id="comment-keyword" name="q" value="<?=h($keyword)?>" placeholder="Keyword in name or message"><button>Filter comments</button><button type="button" id="comments-clear">Clear filter</button></div>
<p>Find comments containing your keyword in the author's name or message.</p>
</form>
<section id="comment-detail" class="panel" hidden>
<h2 id="comment-heading">Selected comment</h2>
<p>Resource: <a id="comment-resource"></a></p>
<div id="comment-preview"></div>
<div class="comment-actions">
<button type="button" id="comment-edit">Edit comment</button>
<button type="button" id="comment-delete">Delete</button>
</div>
<form id="comment-editor" hidden>
<h3>Edit comment</h3>
<label for="edit-author">Name</label><input id="edit-author" name="author" required maxlength="80">
<label for="edit-message">Message</label><textarea id="edit-message" name="message" rows="4" required maxlength="10000"></textarea>
<div class="comment-actions"><button type="submit">Save changes</button><button type="button" id="comment-cancel">Cancel</button></div>
<p>Save changes updates only edited fields (PATCH).</p>
</form>
</section>
<div class="section-heading"><h2>Comments</h2><span id="comment-total"><?=h($listing['total'])?> <?=$keyword !== '' ? 'matching' : 'total'?></span></div>
<div id="comment-list">
<?php if (!$listing['comments']): ?><p class="panel muted"><?=$keyword !== '' ? 'No comments found.' : 'No comments yet.'?></p><?php endif ?>
<?php foreach ($listing['comments'] as $comment): ?>
<article class="panel" data-comment-id="<?=h($comment['id'])?>">
<div class="message-meta"><strong>#<?=h($comment['id'])?> - <?=h($comment['author'])?></strong><time><?=h($comment['created_at'])?></time></div>
<div class="message-body"><?=$comment['message']?></div>
<div class="comment-actions"><button type="button" data-action="view" data-id="<?=h($comment['id'])?>">View</button><button type="button" data-action="edit" data-id="<?=h($comment['id'])?>">Edit</button><button type="button" data-action="delete" data-id="<?=h($comment['id'])?>">Delete</button></div>
</article>
<?php endforeach ?>
</div>
<nav class="comment-actions" aria-label="Comment pages">
<button type="button" id="comments-previous" <?=$listing['page']<=1?'disabled':''?>>Previous page</button>
<span id="comments-page">Page <?=h($listing['page'])?> of <?=h($listing['pages'])?></span>
<button type="button" id="comments-next" <?=$listing['page']>=$listing['pages']?'disabled':''?>>Next page</button>
<button type="button" id="comments-refresh">Refresh comments</button>
</nav>
</div>
<script src="/assets/guestbook.js" defer></script>
