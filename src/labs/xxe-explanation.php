<?php
require __DIR__.'/../includes/app.php';
start_page('XXE: Explanation and Fix','XXE');
?>
<div class="panel">
<h2>Read a local file</h2>
<p>Submit a normal order first, then paste this XML into the editor. Alternatively, intercept Preview order in Burp and send the raw application/xml POST to Repeater.</p>
<pre><?=h('<?xml version="1.0"?>
<!DOCTYPE order [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<order><item>&xxe;</item></order>')?></pre>
<p>The item now contains the Linux web container's account list, including root. The standard path is <code>/etc/passwd</code>, not <code>/etc/password</code>. This reads the container filesystem, not the Windows host; /etc/passwd does not contain password hashes.</p>
<h2>Make an internal HTTP request</h2>
<pre><?=h('<?xml version="1.0"?>
<!DOCTYPE order [
  <!ENTITY xxe SYSTEM "http://127.0.0.1/internal/admin.php">
]>
<order><item>&xxe;</item></order>')?></pre>
<p>Opening <a href="/internal/admin.php">the internal admin endpoint</a> directly returns 403 with the supplied Docker setup. Entity resolution instead makes an HTTP request from the web container. The preview displays the fictional training flag from the internal endpoint, demonstrating SSRF through XXE.</p>
<h2>Cause and fix</h2>
<p>The parser enables <code>LIBXML_NOENT | LIBXML_DTDLOAD</code> and installs an external entity loader that reads file and HTTP resources. NOENT means substitute entities; it does not mean disable them. The response uses plain text and the UI uses textContent.</p>
<p>For this order format, reject DTDs, remove external entity substitution and DTD loading, and deny external resource loading. LIBXML_NONET can add network protection but alone does not prevent local file reads. Retain input limits and validate the expected order schema. Resource size limits and HTTP timeouts in this lab do not fix XXE.</p>
<p>Use the supplied Apache/Docker stack. A single-worker development server cannot service its own internal HTTP request. Both demonstrations use local resources and need no external target.</p>
</div>
<?php end_page();
