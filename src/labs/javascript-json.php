<?php
if (isset($_GET['source'])) { highlight_file(__FILE__); exit; }
require __DIR__.'/../includes/app.php';

// A normal GET shows the page. JavaScript sends JSON to this same file using POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);
    $action = $requestData['action'] ?? 'list';
    $search = $requestData['search'] ?? '';
    $database = db();
    $notice = 'Comments loaded.';
    $savedId = null;

    try {
        if ($action === 'create') {
            $author = $requestData['author'];
            $message = $requestData['message'];
            $sql = "INSERT INTO messages (author, message) VALUES ('$author', '$message')";
            $database->exec($sql);
            $savedId = $database->lastInsertId();
            $notice = 'Comment created.';
        }
        if ($action === 'update') {
            $commentId = $requestData['id'];
            $author = $requestData['author'];
            $message = $requestData['message'];
            $sql = "UPDATE messages SET author = '$author', message = '$message' WHERE id = $commentId";
            $database->exec($sql);
            $notice = 'Comment updated.';
        }
        if ($action === 'delete') {
            $commentId = $requestData['id'];
            $sql = "DELETE FROM messages WHERE id = $commentId";
            $database->exec($sql);
            $notice = 'Comment deleted.';
        }
        $sql = "SELECT id, author, message, created_at FROM messages WHERE author LIKE '%$search%' OR message LIKE '%$search%' ORDER BY id DESC LIMIT 50";
        $comments = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['notice'=>$notice, 'saved_id'=>$savedId, 'comments'=>$comments]);
    } catch (PDOException $error) {
        http_response_code(400);
        echo json_encode(['error'=>$error->getMessage()]);
    }
    exit;
}

start_page('JavaScript and JSON', 'Web Development Basics');
?>
<p class="lead">Use JavaScript to send JSON, read the response, and update comments without reloading the page.</p>
<p><a href="/labs/javascript-json-guide.php">Read the student guide</a> &middot; <a href="?source=1">View this page's PHP and JavaScript source</a></p>
<p>These are the existing shared guestbook comments. This page has its own simple logic. The global Submission Type selector does not change its JSON requests.</p>
<form id="comment-form" class="panel">
<h2 id="form-heading">New comment</h2>
<input type="hidden" id="comment-id" value="">
<label for="author">Author</label><input id="author" name="author" value="Student" required>
<label for="message">Comment</label><textarea id="message" name="message" required>Learning JavaScript and JSON.</textarea>
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:16px"><button type="submit" id="save-button">Add comment</button><button type="button" id="new-button">Start a new comment</button></div>
</form>
<form id="search-form" class="panel">
<label for="search">Search author or comment</label><div class="input-row"><input id="search" name="search" placeholder="Leave empty to show all"><button>Filter comments</button></div>
</form>
<p id="status" role="status"></p>
<p>The list shows the latest 50 matching comments.</p>
<div id="comment-list"></div>
<details class="panel"><summary>See the JSON sent and received</summary><h3>Request body</h3><pre id="json-request" style="overflow-wrap:anywhere"></pre><h3>Response body</h3><pre id="json-response" style="overflow-wrap:anywhere"></pre></details>
<script>
const commentForm = document.getElementById('comment-form');
const authorInput = document.getElementById('author');
const messageInput = document.getElementById('message');
const commentIdInput = document.getElementById('comment-id');
const searchInput = document.getElementById('search');
const statusText = document.getElementById('status');
let comments = [];

async function sendRequest(requestData) {
    document.getElementById('json-request').textContent = JSON.stringify(requestData, null, 2);
    statusText.textContent = 'Loading...';
    try {
        const response = await fetch('/labs/javascript-json.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(requestData)
        });
        const responseData = await response.json();
        document.getElementById('json-response').textContent = JSON.stringify(responseData, null, 2);
        if (!response.ok) {
            statusText.textContent = responseData.error;
            return false;
        }
        comments = responseData.comments;
        displayComments();
        statusText.textContent = responseData.notice;
        return true;
    } catch (error) {
        statusText.textContent = 'Request failed: ' + error.message;
        return false;
    }
}

function displayComments() {
    const commentList = document.getElementById('comment-list');
    commentList.innerHTML = '';
    if (comments.length === 0) commentList.innerHTML = '<p class="panel">No comments found.</p>';
    for (const comment of comments) {
        commentList.innerHTML += `
            <article class="panel" data-comment-id="${comment.id}">
                <strong>#${comment.id} - ${comment.author}</strong>
                <div>${comment.message}</div>
                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                    <button type="button" onclick="editComment(${comment.id})">Edit</button>
                    <button type="button" onclick="deleteComment(${comment.id})">Delete</button>
                </div>
            </article>`;
    }
}

function startNewComment() {
    commentIdInput.value = '';
    authorInput.value = 'Student';
    messageInput.value = 'Learning JavaScript and JSON.';
    document.getElementById('form-heading').textContent = 'New comment';
    document.getElementById('save-button').textContent = 'Add comment';
}

function editComment(commentId) {
    const comment = comments.find(comment => Number(comment.id) === commentId);
    commentIdInput.value = comment.id;
    authorInput.value = comment.author;
    messageInput.value = comment.message;
    document.getElementById('form-heading').textContent = 'Edit comment #' + comment.id;
    document.getElementById('save-button').textContent = 'Save changes';
    authorInput.focus();
}

async function deleteComment(commentId) {
    if (!confirm('Delete comment #' + commentId + '?')) return;
    const deleted = await sendRequest({action: 'delete', id: commentId, search: searchInput.value});
    if (deleted && Number(commentIdInput.value) === commentId) startNewComment();
}

commentForm.addEventListener('submit', async function(event) {
    event.preventDefault();
    const requestData = {
        action: commentIdInput.value === '' ? 'create' : 'update',
        id: commentIdInput.value,
        author: authorInput.value,
        message: messageInput.value,
        search: searchInput.value
    };
    const saved = await sendRequest(requestData);
    if (saved) startNewComment();
});

document.getElementById('search-form').addEventListener('submit', function(event) {
    event.preventDefault();
    sendRequest({action: 'list', search: searchInput.value});
});
document.getElementById('new-button').addEventListener('click', startNewComment);
sendRequest({action: 'list', search: ''});
</script>
<?php end_page(); ?>
