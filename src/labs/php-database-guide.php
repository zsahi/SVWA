<?php
require __DIR__.'/../includes/app.php';
start_page('Student Guide: PHP and MySQL Queries', 'Web Development Basics');
?>
<div style="overflow-wrap:anywhere;">
<p><a href="/labs/php-database.php">Open the lab</a> &middot; <a href="/labs/php-database.php?source=1">Read the complete single-page source</a></p>
<p>Open <code>example.com:8888/labs/php-database.php</code>. This lab manages the same shared comments as the JavaScript lesson, but ordinary forms reload the page. The focus is the PHP-to-MySQL path.</p>
<h2>What you will learn</h2>
<p>Follow <code>$_GET</code> and <code>$_POST</code> values into SELECT, INSERT, UPDATE, and DELETE. See the SQL statements on the page, then compare their results with the <code>svwa.messages</code> table in DBeaver.</p>
<p>The existing table has <code>id</code> (auto-incrementing primary key), <code>author</code>, <code>message</code>, and <code>created_at</code> (a timestamp filled automatically). The lab uses <code>$database = db();</code> to get SVWA's existing PDO connection. Everything else for this exercise is in the single lab file.</p>
<h2>SELECT: read and filter</h2>
<pre style="overflow-wrap:anywhere"><code>SELECT id, author, message, created_at
FROM messages
WHERE author LIKE '%Student%' OR message LIKE '%Student%'
ORDER BY id DESC
LIMIT 50;</code></pre>
<p><code>SELECT</code> chooses columns; <code>FROM</code> chooses the table. <code>WHERE</code> restricts rows. <code>LIKE</code> compares a pattern: each <code>%</code> matches any number of characters, so this searches for Student anywhere in either field. <code>OR</code> accepts either match. <code>ORDER BY id DESC</code> shows newer IDs first and <code>LIMIT 50</code> caps the list. An empty search produces <code>LIKE '%%'</code>.</p>
<pre style="overflow-wrap:anywhere"><code>$search = $_GET['search'] ?? $_POST['search'] ?? '';
$sql = "SELECT id, author, message, created_at FROM messages WHERE author LIKE '%$search%' OR message LIKE '%$search%' ORDER BY id DESC LIMIT 50";
$comments = $database-&gt;query($sql)-&gt;fetchAll(PDO::FETCH_ASSOC);</code></pre>
<p>The search form uses GET. PHP inserts the value into its double-quoted string. <code>query</code> sends that SQL to MySQL; <code>fetchAll(PDO::FETCH_ASSOC)</code> returns rows as arrays keyed by column name. A PHP <code>foreach</code> prints those rows as HTML.</p>
<h2>INSERT: create a row</h2>
<pre style="overflow-wrap:anywhere"><code>INSERT INTO messages (author, message)
VALUES ('Student', 'Learning database queries.');</code></pre>
<p>Add comment submits POST fields <code>action=create</code>, <code>author</code>, and <code>message</code>. PHP reads them using <code>$_POST</code>, builds the INSERT string, and calls <code>$database-&gt;exec($sql)</code>. MySQL supplies the ID and timestamp. <code>lastInsertId()</code> returns the new ID, shown in the notice.</p>
<h2>UPDATE: change the chosen row</h2>
<p>Edit opens a GET URL with <code>edit_id</code>. PHP uses SELECT to load that row and fill the form. Saving sends <code>action=update</code> and a hidden <code>id</code> with the two editable values.</p>
<pre style="overflow-wrap:anywhere"><code>SELECT id, author, message FROM messages WHERE id = 42;

UPDATE messages
SET author = 'Student One', message = 'My updated comment.'
WHERE id = 42;</code></pre>
<p><code>SET</code> assigns the new values. <code>WHERE id = 42</code> chooses one row; use your own actual comment ID. The ID and creation time remain the same. The page prints the number of affected rows; saving identical values can report zero changed rows.</p>
<h2>DELETE: remove the chosen row</h2>
<pre style="overflow-wrap:anywhere"><code>DELETE FROM messages WHERE id = 42;</code></pre>
<p>The Delete button sends POST with <code>action=delete</code> and <code>id</code>. PHP runs DELETE and reloads the remaining comments. Keep the WHERE condition when practicing: without it, UPDATE or DELETE can affect every row in the table.</p>
<h2>Read the page from top to bottom</h2>
<ol><li>PHP reads the request and sets simple default variables.</li><li>If it is POST, it builds and runs the selected write query.</li><li>If edit_id is present, it reads that row into the form.</li><li>It runs the list SELECT, then prints the form, executed SQL, and results.</li></ol>
<p>Each executed query is collected in <code>$executedQueries</code>. <code>htmlspecialchars</code> is used only to display the SQL and error text legibly. It does not change the SQL passed to MySQL. The raw input is interpolated directly, with no prepared statements or tokens, as in the classroom demonstration. Text values sit inside SQL single quotes; IDs are unquoted in these examples. An apostrophe in input can break the query and display a database error.</p>
<p>This page responds directly to POST. Refreshing after an insert may submit it again; use the search form or Start a new comment link for the next request.</p>
<h2>Observation task</h2>
<p>Create your own comment and note its ID. Filter for it, edit its message, and delete it. At each step, identify the SQL statement responsible and confirm the row in DBeaver. Explain how the WHERE clause connects the submitted ID to the affected row.</p>
</div>
<?php end_page(); ?>
