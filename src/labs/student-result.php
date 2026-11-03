<?php
require __DIR__.'/../includes/auth.php';
$a=require_account();
$q=account_db()->prepare('SELECT roll_no FROM student_results WHERE owner_id=?'); $q->execute([$a['id']]); $roll=$q->fetchColumn();
start_page('Broken Access Control – Student Result IDOR','Broken Access Control');
echo '<p class="lead">University Result Portal</p><div class="panel"><h2>Learner objective</h2><p>While signed in as user1, retrieve the result belonging to roll number 1002.</p></div>';
if ($roll) echo '<p><a class="card" href="/labs/result.php?roll_no='.h($roll).'">My Result</a></p>';
else echo '<p class="notice">No result is assigned to this account. Use a seeded student account for this exercise.</p>';
echo '<p><a href="/labs/student-result-explanation.php">Lab explanation and fix (spoilers)</a></p>';
end_page();
