<?php
require __DIR__.'/../includes/app.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    session_write_close();
    $type=strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if (!in_array($type,['application/xml','text/xml'],true)) {
        http_response_code(415);
        exit('Submit the order as application/xml.');
    }
    $xml=file_get_contents('php://input',false,null,0,65537);
    if (strlen($xml)>65536) {
        http_response_code(413);
        exit('XML input must be at most 64 KiB.');
    }
    if (trim($xml)==='') {
        http_response_code(400);
        exit('Enter an XML order.');
    }
    libxml_use_internal_errors(true);
    // Deliberately vulnerable: expand external entities from file and HTTP URIs.
    // The loader limits resource use, but deliberately permits internal destinations.
    libxml_set_external_entity_loader(static function ($public,$system,$context) {
        $scheme=strtolower((string)parse_url($system,PHP_URL_SCHEME));
        if (!in_array($scheme,['file','http','https'],true)) return null;
        $options=stream_context_create(['http'=>[
            'timeout'=>3,'follow_location'=>0,'ignore_errors'=>true,
            'header'=>"Connection: close\r\n"
        ]]);
        $body=@file_get_contents($system,false,$options,0,65537);
        if ($body===false || strlen($body)>65536) return null;
        $stream=fopen('php://temp','r+');
        fwrite($stream,$body);
        rewind($stream);
        return $stream;
    });
    try {
        $document=new DOMDocument();
        $loaded=$document->loadXML($xml,LIBXML_NOENT | LIBXML_DTDLOAD);
        $errors=libxml_get_errors();
        if (!$loaded || $errors) {
            http_response_code(400);
            echo 'Unable to parse XML or load an external entity.';
        } elseif ($document->documentElement->tagName!=='order' || $document->getElementsByTagName('item')->length!==1) {
            http_response_code(400);
            echo 'Expected an <order> with one <item> element.';
        } else {
            echo "Order preview\nItem: ".$document->getElementsByTagName('item')->item(0)->textContent;
        }
    } finally {
        libxml_set_external_entity_loader(null);
        libxml_clear_errors();
    }
    exit;
}
start_page('XML order preview','XXE');
?>
<p class="lead">Import an XML order and preview its item description.</p>
<div class="panel">
<h2>Your objectives</h2>
<p>This exercise intentionally uses an unsafe XML parser. Discover how an XML external entity (XXE) can make the server read resources on your behalf.</p>
<ol>
<li>Submit the supplied order. Confirm that the preview contains <code>Canvas Backpack</code>.</li>
<li>Use an external entity to read <code>/etc/passwd</code> from the Linux web container. Success: the item includes <code>root:x:0:0:</code>.</li>
<li>Visit <a href="/internal/admin.php" target="_blank" rel="noopener">the internal admin endpoint</a> and observe its 403 response. Use an external entity to request that endpoint from inside the container. Success: the item reveals a fictional <code>SVWA{...}</code> training flag.</li>
<li>Explain why the server can access a resource your browser cannot, and which parser settings must change to prevent this.</li>
</ol>
<p>Keep experiments inside this Docker lab. File paths refer to the container, not your Windows host.</p>
<details><summary>Hint 1: Declare and reference an entity</summary><p>Add a <code>DOCTYPE order</code> declaration before the root element. Declare an entity inside its internal subset, then reference its name with <code>&amp;name;</code> inside <code>&lt;item&gt;</code>.</p></details>
<details><summary>Hint 2: Load a file</summary><p>An external entity declaration uses <code>SYSTEM</code> followed by a quoted URI. For local files, use the <code>file:///</code> scheme and the absolute container path.</p></details>
<details><summary>Hint 3: Request the internal service</summary><p>The entity URI can also use HTTP. From inside the web container, the Apache service is at <code>http://127.0.0.1</code> on port 80. Append the internal endpoint path; port 8888 is the host-facing port.</p></details>
</div>
<form id="xml-order-form" class="panel">
    <label for="xml-order">Order XML</label>
    <textarea id="xml-order" rows="10" spellcheck="false">&lt;?xml version="1.0"?&gt;
&lt;order&gt;
  &lt;item&gt;Canvas Backpack&lt;/item&gt;
&lt;/order&gt;</textarea>
    <button type="submit">Preview order</button>
    <p id="xml-response-status" role="status" aria-live="polite"></p>
    <pre id="xml-result" role="status" aria-live="polite"></pre>
    <noscript>Enable JavaScript to submit XML, or send an application/xml POST with Burp Repeater.</noscript>
</form>
<p>This form sends a raw XML POST independently of the Submission Type setting.</p>
<p><a href="/labs/xxe-explanation.php">Lab explanation and fix (spoilers)</a></p>
<script src="/assets/xxe.js" defer></script>
<?php end_page();
