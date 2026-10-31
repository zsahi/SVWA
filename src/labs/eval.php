<?php require __DIR__.'/../includes/app.php'; start_page('Calculator','DOM XSS'); ?>
<form class="panel"><label for="expression">Expression</label><div class="input-row"><input id="expression" name="expression" value="2 + 2" autocomplete="off"><button>Calculate</button></div></form><div class="panel result" id="answer"></div>
<script>
const expression = new URLSearchParams(location.search).get('expression') || '2 + 2';
document.getElementById('expression').value = expression;
try { document.getElementById('answer').textContent = eval(expression); }
catch (error) { document.getElementById('answer').textContent = 'Invalid expression.'; }
</script>
<?php end_page(); ?>
