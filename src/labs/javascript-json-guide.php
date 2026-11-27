<?php
require __DIR__.'/../includes/app.php';
start_page('Student Guide: JavaScript and JSON', 'Web Development Basics');
?>
<div style="overflow-wrap:anywhere;">
<p><a href="/labs/javascript-json.php">Open the lab</a> &middot; <a href="/labs/javascript-json.php?source=1">Read the complete single-page source</a></p>
<p>Open <code>example.com:8888/labs/javascript-json.php</code>. Add a comment, filter it, edit it, and delete it. Notice that the page updates without navigating to a new document.</p>
<h2>Purpose and contents</h2>
<p>This page demonstrates the browser side of a small single-page interface: forms, events, JavaScript objects, JSON, fetch, and DOM updates. It uses the existing <code>messages</code> table and its <code>author</code> and <code>message</code> columns. It does not include the existing guestbook page or its JavaScript. All exercise PHP, HTML, and JavaScript is in this one file.</p>
<p>HTML forms work without JavaScript, as Lab 1 shows. JavaScript is used here to send a JSON body and update part of the page after receiving a response. PHP and MySQL still do the server-side work.</p>
<h2>Follow one click</h2>
<ol><li>The browser initially uses GET to load the page and its inline script.</li><li>JavaScript handles the submit event and calls <code>event.preventDefault()</code> to stop normal form navigation.</li><li>It reads the input values and builds a JavaScript object.</li><li><code>JSON.stringify</code> turns the object into JSON text. <code>fetch</code> sends that text in a POST body to the same PHP file.</li><li>PHP reads the body, performs the selected action, and responds with JSON. It exits before printing the HTML page.</li><li>JavaScript reads that JSON and redraws the comment list in the existing document.</li></ol>
<pre style="overflow-wrap:anywhere"><code>const response = await fetch('/labs/javascript-json.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(requestData)
});
const responseData = await response.json();</code></pre>
<p><code>async</code> lets a function use <code>await</code>. The first await waits for the response; the next reads and parses its JSON body. The browser can still process other events while waiting.</p>
<h2>The JSON body</h2>
<p>Click Add comment with the sample values and inspect the POST in Burp. Its body has this shape:</p>
<pre style="overflow-wrap:anywhere"><code>{"action":"create","id":"","author":"Student","message":"Learning JavaScript and JSON.","search":""}</code></pre>
<p>All actions in this teaching page use POST with JSON. The <code>action</code> field selects <code>list</code>, <code>create</code>, <code>update</code>, or <code>delete</code>; it is not the HTTP method. This intentionally differs from the HTTP Methods guestbook exercise.</p>
<pre style="overflow-wrap:anywhere"><code>$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);
$action = $requestData['action'] ?? 'list';</code></pre>
<p>JSON does not automatically populate <code>$_POST</code>. <code>php://input</code> provides the raw body. The second argument <code>true</code> makes <code>json_decode</code> return PHP arrays.</p>
<h2>Recognize the JavaScript syntax</h2>
<ul><li><code>document.getElementById</code> finds a page element. An input's <code>.value</code> gives its current value.</li><li><code>addEventListener</code> attaches a handler to an event such as submit or click.</li><li><code>const</code> declares a binding that is not reassigned; <code>let comments</code> is replaced when a new list arrives.</li><li><code>comments.find(...)</code> selects the comment to edit. Its ID goes into a hidden input, so Save changes sends an update rather than a create action.</li><li><code>for...of</code> loops through comments. Backtick template strings use <code>${...}</code> to insert values into HTML.</li><li><code>innerHTML</code> replaces or adds markup in the list. <code>textContent</code> displays plain text in status and JSON panels.</li><li><code>try/catch</code> reports a failed request. The response status is checked before the page displays a success message.</li></ul>
<h2>Practice sequence</h2>
<ol><li>Add a comment using a distinctive author name. Note its new database ID.</li><li>Filter by part of the name or message. An empty filter shows the latest 50 comments.</li><li>Click Edit, change the message, and click Save changes. The ID stays the same.</li><li>Click Delete on your comment and confirm. Its row disappears.</li></ol>
<p>Expand See the JSON sent and received to compare JavaScript's data with Burp. The response contains a notice and a comments array; creation also returns saved_id. Comments are shared with the other guestbook pages, so use your own practice rows.</p>
<p>The SQL strings are deliberately assembled directly from input for these beginner exercises. A quote in a value can cause a SQL error, which this page displays. Lab 3 explains those queries.</p>
<h2>Observation task</h2>
<p>Watch Burp while editing a comment. Find the action and ID in the JSON body, then locate the updated message in the response. Explain which lines prevent navigation and which lines update the page.</p>
</div>
<?php end_page(); ?>
