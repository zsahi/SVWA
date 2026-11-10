<?php
require __DIR__.'/../includes/app.php';
$data=input();
if (array_key_exists('stockApi',$data)) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    $url=field($data,'stockApi');
    // Intentionally vulnerable: the client controls the server's destination.
    // Restrict protocols, redirects, time and size without blocking loopback SSRF.
    $scheme=strtolower((string)parse_url($url,PHP_URL_SCHEME));
    if (!in_array($scheme,['http','https'],true) || filter_var($url,FILTER_VALIDATE_URL)===false) {
        http_response_code(400);
        echo 'Enter a valid HTTP or HTTPS URL.';
    } else {
        $context=stream_context_create(['http'=>[
            'method'=>'GET','timeout'=>3,'follow_location'=>0,
            'ignore_errors'=>true,'header'=>"Connection: close\r\n"
        ]]);
        $body=@file_get_contents($url,false,$context,0,65536);
        if ($body===false) {
            http_response_code(502);
            echo 'The stock service could not be reached.';
        } else {
            echo $body;
        }
    }
    exit;
}
start_page('Stock checker','SSRF');
?>
<p class="lead">Everyday essentials, ready for your next trip.</p>
<div class="panel">
    <span class="pill">TRAVEL / BACKPACKS</span>
    <h2>Canvas Backpack</h2>
    <p>A durable everyday backpack with a padded laptop sleeve, adjustable straps, and room for your daily essentials.</p>
    <p><strong>$49.00</strong></p>
    <button id="check-stock" type="button">Check stock</button>
    <pre id="stock-result" role="status" aria-live="polite"></pre>
    <noscript><p>Enable JavaScript to check stock availability.</p></noscript>
</div>
<p><a href="/labs/ssrf-explanation.php">Lab explanation and fix (spoilers)</a></p>
<script src="/assets/ssrf.js" defer></script>
<?php end_page();
