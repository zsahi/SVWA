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
if (!in_array($_POST['submission'] ?? '', ['get','post','json','multipart'], true)) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid submission type.']);
    exit;
}
$_SESSION['submission'] = $_POST['submission'];
echo json_encode(['submission'=>$_SESSION['submission']]);
