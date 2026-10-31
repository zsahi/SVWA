<?php require __DIR__.'/../includes/app.php'; start_page('Quick search','DOM XSS'); ?>
<form class="panel"><label for="query">Search query</label><div class="input-row"><input id="query" name="q" autocomplete="off"><button>Search</button></div></form><div class="panel result" id="result"></div>
<script>
const query = new URLSearchParams(location.search).get('q') || '';
document.getElementById('query').value = query;
document.getElementById('result').innerHTML = 'Results for: ' + query;
</script>
<?php end_page(); ?>
