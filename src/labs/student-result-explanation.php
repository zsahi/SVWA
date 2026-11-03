<?php
require __DIR__.'/../includes/auth.php'; require_account();
start_page('Student Result IDOR: Explanation and Fix','Broken Access Control');
?>
<div class="panel"><p>While signed in as user1, open My Result and send the GET request to Burp Repeater. Change only roll_no=1001 to roll_no=1002, keeping the same session cookie. The response identifies Bilal Ahmed and his result.</p>
<p>The endpoint validates the roll number and uses a parameterized query, but does not check who owns the record. Authentication and SQL injection prevention do not provide record authorization.</p>
<p>Fix: obtain the account from require_account(), and constrain the query by both the roll number and the authenticated account ID. Return 404 if no authorized record exists. Never take owner_id from the request.</p>
<pre>$account = require_account();
$query = account_db()-&gt;prepare(
    'SELECT roll_no,student_name,subject,marks FROM student_results WHERE roll_no=? AND owner_id=?'
);
$query-&gt;execute([$roll, $account['id']]);</pre>
<p>This exercise deliberately leaves that check out only on the read-only result endpoint. Profile ownership and administrator permissions are enforced separately.</p></div>
<?php end_page();
