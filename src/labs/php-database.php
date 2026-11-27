<?php
if (isset($_GET['source'])) { highlight_file(__FILE__); exit; }
require __DIR__.'/../includes/app.php';

$database = db();
$search = $_GET['search'] ?? $_POST['search'] ?? '';
$author = 'Student';
$message = 'Learning SELECT, INSERT, UPDATE, and DELETE.';
$editId = '';
$notice = '';
$errorMessage = '';
$executedQueries = [];
$comments = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];
        if ($action === 'create') {
            $author = $_POST['author'];
            $message = $_POST['message'];
            $sql = "INSERT INTO messages (author, message) VALUES ('$author', '$message')";
            $executedQueries[] = $sql;
            $database->exec($sql);
            $notice = 'Created comment #' . $database->lastInsertId();
        }
        if ($action === 'update') {
            $commentId = $_POST['id'];
            $author = $_POST['author'];
            $message = $_POST['message'];
            $sql = "UPDATE messages SET author = '$author', message = '$message' WHERE id = $commentId";
            $executedQueries[] = $sql;
            $affectedRows = $database->exec($sql);
            $notice = 'UPDATE affected ' . $affectedRows . ' row(s).';
        }
        if ($action === 'delete') {
            $commentId = $_POST['id'];
            $sql = "DELETE FROM messages WHERE id = $commentId";
            $executedQueries[] = $sql;
            $affectedRows = $database->exec($sql);
            $notice = 'DELETE affected ' . $affectedRows . ' row(s).';
        }
    }

    if (isset($_GET['edit_id'])) {
        $editId = $_GET['edit_id'];
        $sql = "SELECT id, author, message FROM messages WHERE id = $editId";
        $executedQueries[] = $sql;
        $commentToEdit = $database->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($commentToEdit) {
            $author = $commentToEdit['author'];
            $message = $commentToEdit['message'];
        } else {
            $editId = '';
            $notice = 'Comment not found.';
        }
    }

    $sql = "SELECT id, author, message, created_at FROM messages WHERE author LIKE '%$search%' OR message LIKE '%$search%' ORDER BY id DESC LIMIT 50";
    $executedQueries[] = $sql;
    $comments = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    $errorMessage = $error->getMessage();
}

start_page('PHP and MySQL Queries', 'Web Development Basics');
?>
<p class="lead">Follow a submitted form value into a SQL statement and see its effect on the messages table.</p>
<p><a href="/labs/php-database-guide.php">Read the student guide</a> &middot; <a href="?source=1">View this page's PHP source</a></p>
<p>This exercise uses ordinary GET and POST forms and reloads the page. It shares the existing guestbook's <code>messages</code> table. The global Submission Type setting does not change these forms.</p>
<?php if ($notice): ?><p class="notice" id="database-notice"><?=$notice?></p><?php endif ?>
<?php if ($errorMessage): ?><pre class="panel" id="database-error" style="overflow-wrap:anywhere"><?=htmlspecialchars($errorMessage)?></pre><?php endif ?>
<form class="panel" id="database-form" method="post" action="/labs/php-database.php">
<h2><?=$editId === '' ? 'New comment' : 'Edit comment #' . $editId?></h2>
<input type="hidden" name="action" value="<?=$editId === '' ? 'create' : 'update'?>">
<input type="hidden" name="id" value="<?=$editId?>">
<input type="hidden" name="search" value="<?=$search?>">
<label for="author">Author</label><input id="author" name="author" value="<?=$author?>" required>
<label for="message">Comment</label><textarea id="message" name="message" rows="3" required><?=$message?></textarea>
<button><?=$editId === '' ? 'Add comment' : 'Save changes'?></button>
<a href="/labs/php-database.php">Start a new comment</a>
</form>
<form class="panel" id="database-search" method="get" action="/labs/php-database.php">
<label for="search">Search author or comment</label><div class="input-row"><input id="search" name="search" value="<?=$search?>"><button>Filter comments</button></div>
</form>
<section class="panel" id="sql-statements">
<h2>SQL statements for this request</h2>
<?php foreach ($executedQueries as $sqlQuery): ?><pre style="overflow-wrap:anywhere"><?=htmlspecialchars($sqlQuery)?></pre><?php endforeach ?>
<p>The strings above are the SQL sent to MySQL, including the values from your request.</p>
</section>
<p>The list shows the latest 50 matching comments.</p>
<div id="database-comments">
<?php if (!$comments): ?><p class="panel">No comments found.</p><?php endif ?>
<?php foreach ($comments as $comment): ?>
<article class="panel" data-comment-id="<?=$comment['id']?>">
<strong>#<?=$comment['id']?> - <?=$comment['author']?></strong>
<div><?=$comment['message']?></div>
<div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;margin-top:16px">
<a href="?edit_id=<?=$comment['id']?>">Edit</a>
<form method="post" action="/labs/php-database.php">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?=$comment['id']?>">
<input type="hidden" name="search" value="<?=$search?>">
<button>Delete</button>
</form>
</div>
</article>
<?php endforeach ?>
</div>
<p>Because this simple page responds directly to POST, refreshing after a write may repeat that request. Use a link or the search form for your next request.</p>
<?php end_page(); ?>
