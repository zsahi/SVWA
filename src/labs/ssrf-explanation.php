<?php
require __DIR__.'/../includes/app.php';
start_page('SSRF: Explanation and Fix','SSRF');
?>
<div class="panel">
<p>Click Check stock and intercept the JavaScript POST request to <code>/labs/ssrf.php</code>. Send it to Burp Repeater and change <code>stockApi</code> to <code>http://127.0.0.1/internal/admin.php</code>. The service response contains the training flag.</p>
<p>Opening <a href="/internal/admin.php">the internal admin endpoint</a> directly through the default Docker port returns 403. The stock checker instead requests it from inside the web container, where 127.0.0.1 refers to the server itself. The endpoint sees a loopback connection and permits it.</p>
<p>The vulnerable operation passes a client-controlled URL to <code>file_get_contents()</code>. Restricting the scheme to HTTP/HTTPS, disabling redirects, limiting the response size and setting a timeout do not prevent SSRF to internal HTTP services. The endpoint returns plain text; JavaScript displays it with textContent so fetched HTML is not executed.</p>
<p>Fix this stock checker by accepting only a product ID, validating it against supported products, and constructing the URL using a fixed, trusted stock-service address and path. Do not let the caller choose the host, port or path. Internal administration also needs authentication and authorization independent of its network address.</p>
<p>For systems that must fetch arbitrary URLs, enforce destination restrictions against resolved addresses at connection time, validate every redirect if enabled, and apply network egress controls. A URL prefix check alone is insufficient.</p>
<p>This lab uses fictional data, requires no external target, and runs entirely within the existing web instance. The product page sends a URL-encoded POST through JavaScript, independently of the submission setting.</p>
</div>
<?php end_page();
