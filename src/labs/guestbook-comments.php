<?php
require __DIR__.'/../includes/app.php';
require __DIR__.'/../includes/guestbook-data.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function comment_response(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
$id = null;
if (array_key_exists('id', $_GET)) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id === false) comment_response(400, ['error'=>'Use a positive integer comment ID.']);
}
$method = $_SERVER['REQUEST_METHOD'];
$allowed = $id === null ? 'GET, POST, PUT, OPTIONS' : 'GET, PUT, PATCH, DELETE, OPTIONS';
if ($method === 'OPTIONS') {
    header('Allow: '.$allowed);
    http_response_code(204);
    exit;
}
if (!in_array($method, explode(', ', $allowed), true)) {
    header('Allow: '.$allowed);
    comment_response(405, ['error'=>'Method not supported at this URL.']);
}
try {
    if ($method === 'GET') {
        if ($id !== null) {
            $comment = guestbook_comment($id);
            if (!$comment) comment_response(404, ['error'=>'Comment not found.']);
            comment_response(200, $comment);
        }
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($page === false) comment_response(400, ['error'=>'Use a positive integer page number.']);
        if (!is_string($_GET['q'] ?? '')) comment_response(400, ['error'=>'Use a text search keyword.']);
        comment_response(200, guestbook_page($page, $_GET['q'] ?? ''));
    }
    if ($method === 'DELETE') {
        $query = db()->prepare('DELETE FROM messages WHERE id = ?');
        $query->execute([$id]);
        if (!$query->rowCount()) comment_response(404, ['error'=>'Comment not found.']);
        http_response_code(204);
        exit;
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) comment_response(413, ['error'=>'Request body is too large.']);
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/x-www-form-urlencoded') {
        comment_response(415, ['error'=>'Use application/x-www-form-urlencoded.']);
    }
    $raw = file_get_contents('php://input', false, null, 0, 65537);
    if (strlen($raw) > 65536) comment_response(413, ['error'=>'Request body is too large.']);
    parse_str($raw, $data);
    if ($method === 'POST') {
        if (array_diff(array_keys($data), ['q','page'])) comment_response(400, ['error'=>'Search accepts only q and page fields.']);
        if (!is_string($data['q'] ?? '')) comment_response(400, ['error'=>'Use a text search keyword.']);
        $page = filter_var($data['page'] ?? 1, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($page === false) comment_response(400, ['error'=>'Use a positive integer page number.']);
        comment_response(200, guestbook_page($page, $data['q'] ?? ''));
    }
    $creating = $method === 'PUT' && $id === null;
    if (array_diff(array_keys($data), ['author','message'])) comment_response(400, ['error'=>'Use only author and message fields.']);
    if (!$creating && !guestbook_comment($id)) comment_response(404, ['error'=>'Comment not found.']);
    if ($method === 'PUT' && (!array_key_exists('author',$data) || !array_key_exists('message',$data))) {
        comment_response(400, ['error'=>'Both author and message are required.']);
    }
    if ($method === 'PATCH' && !$data) comment_response(400, ['error'=>'Supply author or message to update.']);
    foreach (['author'=>80,'message'=>10000] as $field=>$limit) {
        if (array_key_exists($field,$data) && (!is_string($data[$field]) || trim($data[$field]) === '' || strlen($data[$field]) > $limit)) {
            comment_response(400, ['error'=>$field.' must be non-empty text, at most '.$limit.' bytes.']);
        }
    }
    if ($creating) {
        $query = db()->prepare('INSERT INTO messages (author,message) VALUES (?,?)');
        $query->execute([$data['author'], $data['message']]);
        $id = (int)db()->lastInsertId();
        header('Location: /labs/guestbook-comments.php?id='.$id);
    } else {
        $assignments = []; $values = [];
        foreach (['author','message'] as $field) {
            if (array_key_exists($field,$data)) { $assignments[] = $field.' = ?'; $values[] = $data[$field]; }
        }
        $values[] = $id;
        $query = db()->prepare('UPDATE messages SET '.implode(', ', $assignments).' WHERE id = ?');
        $query->execute($values);
    }
    $comment = guestbook_comment($id);
    if (!$comment) comment_response(404, ['error'=>'Comment not found.']);
    comment_response($creating ? 201 : 200, $comment);
} catch (PDOException $exception) {
    error_log('SVWA guestbook operation failed: '.$exception->getMessage());
    comment_response(500, ['error'=>'Guestbook is unavailable.']);
}
