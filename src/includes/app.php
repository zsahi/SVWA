<?php
session_start(['use_strict_mode'=>true, 'cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
$_SESSION += ['submission'=>'post','show_query'=>false,'show_errors'=>false];
$_SESSION['display_settings_token'] ??= bin2hex(random_bytes(24));
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function db(): PDO {
    static $db;
    return $db ??= new PDO('mysql:host='.(getenv('DB_HOST') ?: 'db').';dbname='.(getenv('DB_NAME') ?: 'svwa').';charset=utf8mb4', getenv('DB_USER') ?: 'svwa', getenv('DB_PASSWORD') ?: 'svwa_lab', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
function input(): array {
    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
    return $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
}
function field(array $data, string $key, string $default=''): string {
    return isset($data[$key]) && is_scalar($data[$key]) ? (string)$data[$key] : $default;
}
function start_page(string $title, string $category='Overview'): void {
    $GLOBALS['page_title']=$title;
    require __DIR__.'/header.php';
}
function end_page(): void { require __DIR__.'/footer.php'; }
function lab_form(string $label, string $name, string $value='', string $button='Submit'): void {
    $method = $_SESSION['submission']==='get' ? 'get' : 'post';
    $encoding = $_SESSION['submission']==='multipart' ? ' enctype="multipart/form-data"' : '';
    echo '<form class="panel lab-form" method="'.$method.'"'.$encoding.' data-submission="'.h($_SESSION['submission']).'"><label for="entry">'.h($label).'</label><div class="input-row"><input id="entry" name="'.h($name).'" value="'.h($value).'" autocomplete="off"><button>'.h($button).'</button></div></form>';
}
function sql_result(string $sql, bool $blind=false): void {
    if ($_SESSION['show_query']) echo '<pre class="panel">'.h($sql).'</pre>';
    try {
        $rows=db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if ($blind) { echo '<div class="panel result">'.($rows ? 'Product available.' : 'Product unavailable.').'</div>'; return; }
        echo '<div class="panel result">';
        if (!$rows) echo '<p>No results found.</p>';
        else {
            echo '<div class="table-wrap"><table><thead><tr>';
            foreach (array_keys($rows[0]) as $key) echo '<th>'.h($key).'</th>';
            echo '</tr></thead><tbody>';
            foreach ($rows as $row) { echo '<tr>'; foreach ($row as $value) echo '<td>'.h($value).'</td>'; echo '</tr>'; }
            echo '</tbody></table></div>';
        }
        echo '</div>';
    } catch (PDOException $error) { echo '<div class="panel result">'.($_SESSION['show_errors'] ? h($error->getMessage()) : 'Request could not be completed.').'</div>'; }
}
