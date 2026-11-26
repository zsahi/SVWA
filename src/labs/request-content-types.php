<?php
require __DIR__.'/../includes/app.php';
start_page('Request Content Types', 'HTTP Basics');
?>
<p class="lead">Send the same search in three formats and compare the request bodies in Burp.</p>
<p>The request's <code>Content-Type</code> header tells the server how to read its body. The product-search lab accepts the same field, <code>search</code>, as URL-encoded form data, multipart form data, or JSON.</p>
<section class="panel">
<h2>Three ways to send the same value</h2>
<p>These are simplified examples, not captured traffic. Each shows a request header, a blank line, and the body for the value <code>Desk Lamp</code>. The blank line separates headers from the body.</p>
<h3>URL-encoded POST</h3>
<p><code>application/x-www-form-urlencoded</code> represents fields as <code>name=value</code> pairs separated by <code>&amp;</code>. Spaces are commonly encoded as <code>+</code>; a literal plus sign is encoded as <code>%2B</code>.</p>
<pre><code>Content-Type: application/x-www-form-urlencoded

search=Desk+Lamp</code></pre>
<h3>Multipart POST</h3>
<p><code>multipart/form-data</code> separates fields into parts. Each part has its own headers. The boundary named in Content-Type separates the parts; the browser generates it. This format can also carry files, though this exercise sends only text.</p>
<pre><code>Content-Type: multipart/form-data; boundary=PracticeBoundary

--PracticeBoundary
Content-Disposition: form-data; name="search"

Desk Lamp
--PracticeBoundary--</code></pre>
<p>The example uses visible line breaks. Multipart delimiter and header lines use CRLF. Your captured boundary will differ from <code>PracticeBoundary</code>.</p>
<h3>JSON POST</h3>
<p><code>application/json</code> sends a JSON value. Here, the body is an object with a <code>search</code> property. Property names and string values use double quotes; the space remains inside the string.</p>
<pre><code>Content-Type: application/json

{"search":"Desk Lamp"}</code></pre>
<p>The method is POST in all three examples. Content-Type describes the body format, not the HTTP method. The response can still be HTML even when the request body is JSON.</p>
</section>
<section class="panel">
<h2>Practice in the product-search lab</h2>
<p>Open the existing lab in Burp's browser at <code>example.com/labs/union.php</code>. Turn Intercept off, or forward intercepted requests.</p>
<p><a class="open-lab" href="/labs/union.php">Open product search to practice &rarr;</a></p>
<ol>
<li>Choose <strong>URL-encoded POST</strong> in the global <strong>Submission Type</strong> selector. Enter <code>Desk Lamp</code> and click Search.</li>
<li>In <strong>Proxy &gt; HTTP history</strong>, select the POST to <code>/labs/union.php</code> on <code>example.com</code>. Inspect its Content-Type header and the <code>search</code> field in the body. The HTML response should contain <code>Desk Lamp</code>.</li>
<li>Repeat with <strong>Multipart POST</strong>, then <strong>JSON POST</strong>, using the same search value. Each request should return the same product.</li>
</ol>
<p>Use the three POST choices for this exercise. <strong>URL-encoded GET</strong> puts the search in the URL query string instead of a request body. Ignore requests to <code>/submission-settings.php</code>; those only save the selector setting.</p>
</section>
<section class="panel">
<h2>Observation task</h2>
<p>Compare the three captured searches. Find how each body represents the space in <code>Desk Lamp</code>, and identify the boundary value in the multipart request. Check that all three responses contain the same product name.</p>
</section>
<?php end_page(); ?>
