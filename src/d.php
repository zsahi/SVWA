<?php
declare(strict_types=1);
require_once __DIR__.'/includes/session.php';
if (!isset($_SESSION['report_delete_token'])) {
    $_SESSION['report_delete_token'] = bin2hex(random_bytes(32));
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; style-src 'self' 'unsafe-inline'; frame-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
function escape_report(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function delete_report_form(int $id, int $page): void {
    echo '<form class="delete-report" method="post" action="/d.php?page=' . $page . '">';
    echo '<input type="hidden" name="token" value="' . escape_report($_SESSION['report_delete_token']) . '">';
    echo '<input type="hidden" name="delete_id" value="' . $id . '">';
    echo '<button type="submit" aria-label="Delete report ' . $id . '">Delete</button></form>';
}
$deleteId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['report_delete_token'], $token)) {
        http_response_code(403);
        exit('Invalid form token. Reload the dashboard and try again.');
    }
    $deleteId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$deleteId) {
        http_response_code(400);
        exit('Invalid report ID.');
    }
}
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$rows = []; $report = null; $total = 0; $pages = 1; $error = '';
try {
    $path = getenv('XSS_DB_PATH') ?: '/var/lib/svwa-reports/reports.sqlite';
    if (is_file($path)) {
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        if ($db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'reports'")->fetchColumn()) {
            if ($deleteId !== null) {
                $delete = $db->prepare('DELETE FROM reports WHERE id = :id');
                $delete->execute(['id' => $deleteId]);
                $_SESSION['report_notice'] = $delete->rowCount() ? 'Report deleted.' : 'Report already removed or not found.';
                header('Location: /d.php?page=' . $page, true, 303);
                exit;
            }
            $total = (int) $db->query('SELECT COUNT(*) FROM reports')->fetchColumn();
            $pages = max(1, (int) ceil($total / 25));
            $page = min($page, $pages);
            $list = $db->prepare('SELECT id, received_at, origin FROM reports ORDER BY id DESC LIMIT 25 OFFSET :offset');
            $list->bindValue(':offset', ($page - 1) * 25, PDO::PARAM_INT);
            $list->execute();
            $rows = $list->fetchAll(PDO::FETCH_ASSOC);
            if ($id !== null) {
                $detail = $db->prepare('SELECT id, received_at, origin, msg FROM reports WHERE id = :id');
                $detail->execute(['id' => $id]);
                $report = $detail->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }
    }
    if ($deleteId !== null) {
        $_SESSION['report_notice'] = 'Report already removed or not found.';
        header('Location: /d.php', true, 303);
        exit;
    }
    if ($id !== null && $report === null) {
        http_response_code(404);
        $error = 'Report not found.';
    }
} catch (Throwable $exception) {
    error_log('SVWA dashboard failed: ' . $exception->getMessage());
    http_response_code(500);
    $error = 'Unable to access reports.';
}
$html = null;
if ($report !== null) {
    $parts = preg_split('/\r?\n\r?\nFull DOM\r?\n/', $report['msg'], 2);
    if (count($parts) === 2 && trim($parts[1]) !== '') {
        $html = $parts[1];
    }
}
if (isset($_GET['render'])) {
    header("Content-Security-Policy: sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
    header('Referrer-Policy: no-referrer');
    if ($html === null) {
        if ($error === '') http_response_code(404);
        echo '<!doctype html><p>No captured HTML is available for this report.</p>';
    } else {
        echo $html;
    }
    exit;
}
$notice = $_SESSION['report_notice'] ?? '';
unset($_SESSION['report_notice']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>XSS Reports · SVWA</title>
<link rel="stylesheet" href="/assets/style.css">
<style>
main.dashboard{margin:0 auto;padding:36px 20px;max-width:1200px}
.dashboard header,.dashboard nav{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.dashboard h1{margin-bottom:8px}.dashboard .table-wrap{overflow-x:auto;background:white;border:1px solid var(--border);border-radius:10px}
.dashboard table{width:100%;border-collapse:collapse;text-align:left}.dashboard th,.dashboard td{padding:14px 16px;border-bottom:1px solid var(--border)}
.dashboard td.origin{overflow-wrap:anywhere;min-width:180px}.dashboard time{white-space:nowrap}.dashboard nav{margin-top:20px}
.dashboard pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#fff;padding:20px;border:1px solid var(--border);border-radius:10px;max-height:70vh;overflow:auto}
.dashboard .report-origin{overflow-wrap:anywhere}.dashboard .notice{padding:16px}
.dashboard .delete-report{display:inline-block;margin:6px 0 6px 12px}.dashboard .delete-report button{background:#a52c36;padding:6px 12px;font-size:13px}
.dashboard .html-preview{margin:20px 0}.dashboard .html-preview summary{display:list-item;list-style:disclosure-closed;padding:12px;cursor:pointer}.dashboard .html-preview[open] summary{list-style:disclosure-open}
.dashboard .html-preview iframe{display:block;width:100%;height:65vh;min-height:320px;background:white;border:1px solid var(--border);border-radius:8px}
.dashboard .report-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.dashboard .close-report{display:inline-block;padding:8px 14px;border:1px solid var(--border);border-radius:7px;background:white;font-weight:600}
</style>
</head>
<body><main class="dashboard">
<header><div><div class="eyebrow">SVWA / Classroom collector</div><h1>XSS Reports</h1><span class="muted"><?= $total ?> saved reports · Times in UTC</span></div><a href="/d.php?page=<?= $page ?>">Refresh reports</a></header>
<?php if ($error !== ''): ?><p class="notice" role="alert"><?= escape_report($error) ?></p><?php endif ?>
<?php if ($notice !== ''): ?><p class="notice" role="status"><?= escape_report($notice) ?></p><?php endif ?>
<?php if ($report !== null): ?>
<section aria-labelledby="report-title">
<div class="report-heading"><h2 id="report-title">Report #<?= (int) $report['id'] ?></h2><a class="close-report" href="/d.php?page=<?= $page ?>">Close report</a></div>
<p class="report-origin"><strong>Origin:</strong> <?= escape_report($report['origin']) ?><br><strong>Received:</strong> <?= escape_report($report['received_at']) ?> UTC</p>
<?php if ($html !== null): ?>
<details class="html-preview"><summary>Render HTML</summary>
<p class="muted">Static preview of the captured DOM. Scripts, forms, and external resources are blocked; styling may differ from the original page.</p>
<iframe title="Captured HTML for report <?= (int) $report['id'] ?>" sandbox="" referrerpolicy="no-referrer" loading="lazy" src="/d.php?id=<?= (int) $report['id'] ?>&amp;render=1"></iframe>
</details>
<?php else: ?><p class="muted">No Full DOM section found in this report.</p><?php endif ?>
<pre><?= escape_report($report['msg']) ?></pre>
<?php delete_report_form((int) $report['id'], $page); ?>
<p><a href="/d.php?page=<?= $page ?>">Close report</a></p>
</section>
<?php endif ?>
<?php if ($total === 0 && $error === ''): ?>
<div class="panel"><h2>No reports yet</h2><p>Reports posted to <code>/b.php</code> will appear here. Refresh after submitting a report.</p></div>
<?php elseif ($rows): ?>
<div class="table-wrap"><table><thead><tr><th scope="col">ID</th><th scope="col">Received (UTC)</th><th scope="col">Origin</th><th scope="col">Report</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr><td>#<?= (int) $row['id'] ?></td><td><time><?= escape_report($row['received_at']) ?></time></td><td class="origin"><?= escape_report($row['origin']) ?></td><td><a href="/d.php?id=<?= (int) $row['id'] ?>&amp;page=<?= $page ?>">View #<?= (int) $row['id'] ?></a><?php delete_report_form((int) $row['id'], $page); ?></td></tr>
<?php endforeach ?>
</tbody></table></div>
<nav aria-label="Report pages"><span>Page <?= $page ?> of <?= $pages ?></span><div>
<?php if ($page > 1): ?><a href="/d.php?page=<?= $page - 1 ?>">Previous</a> &nbsp;<?php endif ?>
<?php if ($page < $pages): ?><a href="/d.php?page=<?= $page + 1 ?>">Next</a><?php endif ?>
</div></nav>
<?php endif ?>
</main></body></html>
