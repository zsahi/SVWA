<?php
require __DIR__.'/includes/app.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error'=>'Use POST.']);
    exit;
}
if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['display_settings_token'], $_POST['token'])) {
    http_response_code(403);
    echo json_encode(['error'=>'Refresh the page and try again.']);
    exit;
}
foreach (['show_query','show_errors'] as $key) {
    if (!in_array($_POST[$key] ?? null, ['0','1'], true)) {
        http_response_code(400);
        echo json_encode(['error'=>'Invalid display setting.']);
        exit;
    }
}
$_SESSION['show_query'] = $_POST['show_query'] === '1';
$_SESSION['show_errors'] = $_POST['show_errors'] === '1';
echo json_encode(['show_query'=>$_SESSION['show_query'], 'show_errors'=>$_SESSION['show_errors']]);
