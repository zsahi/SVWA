<?php
require __DIR__.'/../includes/auth.php';
require_account();
if ($_SERVER['REQUEST_METHOD']!=='GET') account_fail(405,'GET required.');
$roll=field($_GET,'roll_no');
if (!preg_match('/\A[0-9]{4}\z/',$roll)) account_fail(400,'A four-digit roll_no is required.');
// INTENTIONAL IDOR: authentication is required, but record ownership is not checked.
// Explicit columns ensure this endpoint only returns fictional result data.
$q=account_db()->prepare('SELECT roll_no,student_name,subject,marks FROM student_results WHERE roll_no=?');
$q->execute([$roll]); $result=$q->fetch(PDO::FETCH_ASSOC);
if (!$result) account_fail(404,'Result not found.');
start_page('University Student Result','Broken Access Control');
echo '<div class="panel"><h2>'.h($result['student_name']).'</h2><p>Roll number: <strong>'.h($result['roll_no']).'</strong></p><table><thead><tr><th>Subject</th><th>Marks / 100</th></tr></thead><tbody><tr><td>'.h($result['subject']).'</td><td>'.h($result['marks']).'</td></tr></tbody></table></div><a href="/labs/student-result.php">Back to portal</a>';
end_page();
