<?php
require __DIR__.'/../includes/app.php';
start_page('URL Encoding', 'HTTP Basics');
?>
<div style="overflow-wrap:anywhere;">
<p>In Burp, you may see <code>%3C</code> in a request but <code>&lt;</code> in the response. That is URL decoding at work. Open the existing reflected XSS lab at <code>http://example.com:8888/labs/html.php?search=test</code> and find its GET request in Proxy HTTP history. Check that the parameter is named <code>search</code>.</p>
<p><a href="/labs/html.php?search=test">Open the reflected XSS lab to practice &rarr;</a></p>
<h2>Single URL encoding</h2>
<p>Percent encoding writes a byte as <code>%</code> followed by two hexadecimal digits. For these ASCII characters, <code>&lt;</code> becomes <code>%3C</code>. Hexadecimal letters are case-insensitive: <code>%3c</code> means the same thing. Non-ASCII text is normally encoded as UTF-8 bytes before percent encoding.</p>
<p>Encode the parameter value, not the whole request line. Keep <code>/labs/html.php?search=</code> intact. Here are the two practice payloads, with one encoded layer. The quote characters at the beginning are part of each payload; the backslashes used to escape text in chat are not.</p>
<h3>Payload 1</h3>
<p>Original value:</p>
<pre><code>'"&gt;&lt;img src=x onerror=alert()&gt;</code></pre>
<p>Single-encoded value:</p>
<pre><code>%27%22%3E%3Cimg%20src%3Dx%20onerror%3Dalert%28%29%3E</code></pre>
<h3>Payload 2</h3>
<p>Original value:</p>
<pre><code>'"&gt;&lt;script&gt;alert()&lt;/script&gt;</code></pre>
<p>Single-encoded value:</p>
<pre><code>%27%22%3E%3Cscript%3Ealert%28%29%3C%2Fscript%3E</code></pre>
<p>For payload 1, the request begins like this. This is an example, not captured traffic:</p>
<pre><code>GET /labs/html.php?search=%27%22%3E%3Cimg%20src%3Dx%20onerror%3Dalert%28%29%3E HTTP/1.1
Host: example.com:8888

</code></pre>
<p>PHP decodes the query value once before this lab reads it. The result area inserts the value as HTML, so the restored tag is interpreted by the browser. The <code>img</code> payload calls <code>alert()</code> when its image fails to load; the <code>script</code> payload calls it directly. Repeater's raw response shows the HTML; view the response in the lab browser to observe the alert.</p>
<p>The initial quotes are useful to recognize, but this lab reflects into HTML text: a quote breakout is not needed here. Encoding transports the payload; it is not HTML escaping, encryption, or an XSS defense.</p>
<h2>Characters to recognize</h2>
<p>The space examples use <code>%20</code>.</p>
<div class="table-wrap"><table><thead><tr><th>Character</th><th>Single</th></tr></thead><tbody>
<tr><td>Single quote <code>&#x27;</code></td><td><code>%27</code></td></tr><tr><td>Double quote <code>&quot;</code></td><td><code>%22</code></td></tr><tr><td>Less than <code>&lt;</code></td><td><code>%3C</code></td></tr><tr><td>Greater than <code>&gt;</code></td><td><code>%3E</code></td></tr><tr><td>Space <code>space</code></td><td><code>%20</code></td></tr><tr><td>Equals <code>=</code></td><td><code>%3D</code></td></tr><tr><td>Slash <code>/</code></td><td><code>%2F</code></td></tr><tr><td>Open parenthesis <code>(</code></td><td><code>%28</code></td></tr><tr><td>Close parenthesis <code>)</code></td><td><code>%29</code></td></tr><tr><td>Percent <code>%</code></td><td><code>%25</code></td></tr><tr><td>Plus <code>+</code></td><td><code>%2B</code></td></tr><tr><td>Ampersand <code>&amp;</code></td><td><code>%26</code></td></tr><tr><td>Question mark <code>?</code></td><td><code>%3F</code></td></tr><tr><td>Hash <code>#</code></td><td><code>%23</code></td></tr><tr><td>Backslash <code>\</code></td><td><code>%5C</code></td></tr>
</tbody></table></div>
<h2>When you send the request</h2>
<ol>
<li>Choose <strong>URL-encoded GET</strong> in the global Submission Type selector when using the form. Paste the <strong>original</strong> payload into the search box. The browser encodes form values for you.</li>
<li>For exact control, send the captured GET to Repeater. Replace only the value after <code>search=</code> in the raw request with one of the single-encoded strings above.</li>
<li>Inspect the actual outgoing request. Burp's Inspector can show decoded values, and its URL-encode-as-you-type option may transform text. Select only the value before using <strong>Ctrl+U</strong> to URL-encode it; do not encode an already encoded value again. Tools may leave some characters literal, so equivalent valid encodings can look different.</li>
<li>In PHP query parsing, both <code>+</code> and <code>%20</code> become a space. To send a literal plus, use <code>%2B</code>. A raw <code>&amp;</code> separates parameters; use <code>%26</code> when it belongs inside a value. A raw <code>#</code> starts a browser URL fragment and is not sent to the server; use <code>%23</code> for a hash in the value.</li>
<li>Do not put literal spaces in the request target. Do not confuse URL encoding (<code>%3C</code>) with HTML entities (<code>&amp;lt;</code>). The examples on this reading page are escaped for safe display; copy their visible text, not the HTML source.</li>
</ol>
<p>References: <a href="https://www.php.net/manual/en/reserved.variables.get.php">PHP query-string decoding</a>, <a href="https://www.php.net/manual/en/function.urldecode.php">PHP decoding rules</a>, and <a href="https://portswigger.net/burp/documentation/desktop/tools/message-editor/text-editor">Burp request editor shortcuts</a>.</p>
<h2>Observation task</h2>
<p>Send the encoded form of payload 1 in the search parameter. Compare the raw request with the response's Results for: area. Find %3C in the request and the restored opening angle bracket in the response. Explain why they represent the same character.</p>
</div>
<?php end_page(); ?>
