<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    http_response_code(405);
    echo json_encode(['error' => 'Use POST with origin and msg fields.']);
    exit;
}

$origin = $_POST['origin'] ?? null;
$message = $_POST['msg'] ?? null;
if (!is_string($origin) || !is_string($message) || $message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'origin and a nonempty msg are required.']);
    exit;
}
if (strlen($origin) > 4096 || strlen($message) > 2 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'Report exceeds the size limit.']);
    exit;
}

try {
    // Keep the database outside the Apache document root.
    $path = getenv('XSS_DB_PATH') ?: '/var/lib/svwa-reports/reports.sqlite';
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $db->exec('CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        received_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        origin TEXT NOT NULL,
        msg TEXT NOT NULL
    )');
    $statement = $db->prepare('INSERT INTO reports (origin, msg) VALUES (:origin, :msg)');
    $statement->execute(['origin' => $origin, 'msg' => $message]);
    http_response_code(201);
    echo json_encode(['saved' => true, 'id' => (int) $db->lastInsertId()]);
} catch (Throwable $exception) {
    error_log('SVWA report storage failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to save report.']);
}
