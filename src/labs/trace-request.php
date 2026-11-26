<?php
require __DIR__.'/../includes/app.php';
$searched = array_key_exists('q', $_GET);
$term = field($_GET, 'q', $searched ? '' : 'Lamp');
$products = [];
$error = '';
if ($searched && trim($term) !== '') {
    try {
        $query = db()->prepare("SELECT name FROM products WHERE name LIKE :term ESCAPE '!' ORDER BY id");
        $pattern = strtr(trim($term), ['!' => '!!', '%' => '!%', '_' => '!_']);
        $query->execute(['term' => '%'.$pattern.'%']);
        $products = $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        error_log('SVWA request trace search failed: '.$exception->getMessage());
        http_response_code(500);
        $error = 'Product search is unavailable. Please try again later.';
    }
}
$exampleBody = '<p>Search term: <strong>Lamp</strong></p><ul><li>Desk Lamp</li></ul>';
start_page('Trace a Request and Its Response', 'HTTP Basics');
?>
<p class="lead">Follow a search from your browser to the server, then change it in Burp Repeater.</p>
<form class="panel lab-form" action="/labs/trace-request.php" method="get">
<label for="search-term">Search products</label>
<div class="input-row"><input id="search-term" name="q" value="<?=h($term)?>"><button type="submit">Search</button></div>
<p>Worked example: <code>Lamp</code>. Student task: <code>Keyboard</code>. This exercise always uses GET, regardless of the global Submission Type.</p>
</form>
<?php if ($searched): ?>
<section class="panel result" aria-label="Search result">
<h2>Search result</h2>
<p>Search term: <strong><?=h($term)?></strong></p>
<?php if ($error): ?>
<p role="alert"><?=h($error)?></p>
<?php elseif (!$products): ?>
<p>No products found</p>
<?php else: ?>
<ul>
<?php foreach ($products as $product): ?>
<li><?=h($product['name'])?></li>
<?php endforeach ?>
</ul>
<?php endif ?>
</section>
<?php endif ?>
<section class="panel">
<h2>Trace the exchange in Burp</h2>
<ol>
<li>Open <code>example.com/labs/trace-request.php</code> in Burp's browser (or a browser configured to use Burp Proxy). Turn Intercept off, or forward intercepted requests.</li>
<li>Search for <code>Lamp</code>. In <strong>Proxy &gt; HTTP history</strong>, find <code>GET /labs/trace-request.php?q=Lamp</code> with <code>example.com</code> in the Host column.</li>
<li>Select the entry. Inspect the request query string and the HTML response containing <code>Desk Lamp</code>.</li>
<li>Right-click the entry and choose <strong>Send to Repeater</strong>. For your student task, change <code>q=Lamp</code> to <code>q=Keyboard</code> in Repeater, then click <strong>Send</strong>. Find <code>Wireless Keyboard</code> in the HTML response body. You can also search for <code>Keyboard</code> using the form.</li>
</ol>
<p>This read-only search matches part of a product name in SVWA's existing products table. The search term and product names are safely escaped for HTML. An unmatched search displays <strong>No products found</strong>.</p>
</section>
<section class="panel" aria-labelledby="http-example">
<h2 id="http-example">Example only: annotated HTTP/1.1 exchange</h2>
<p>This simplified example is not your captured traffic. The hostname is <code>example.com</code>. Other headers, the protocol version, and the HTML body may differ. The visible <code>\r\n</code> markers stand for CRLF (carriage return followed by line feed); annotations in brackets are not transmitted.</p>
<h3>Example request</h3>
<pre><code>GET /labs/trace-request.php?q=Lamp HTTP/1.1\r\n  [request line]
Host: example.com\r\n  [header]
\r\n  [blank line ends the headers; this request has no body]</code></pre>
<h3>Example response</h3>
<pre><code>HTTP/1.1 200 OK\r\n  [status line]
Content-Type: text/html; charset=utf-8\r\n  [header]
Content-Length: <?=strlen($exampleBody)?>\r\n  [body length in bytes]
\r\n  [blank line separates headers from the body]
<?=h($exampleBody)?></code></pre>
<p>Each start line and header line ends with CRLF. The extra CRLF creates the empty line between headers and the optional message body. The example body above is <?=strlen($exampleBody)?> ASCII bytes with no trailing newline.</p>
</section>
<?php end_page(); ?>
