<?php require __DIR__.'/../includes/app.php'; start_page('Message preview','DOM XSS'); ?>
<form class="panel" id="message-form"><label for="message">Message</label><div class="input-row"><input id="message" autocomplete="off"><button>Preview</button></div></form>
<div class="panel result"><script>
let message = '';
try { message = decodeURIComponent(location.hash.slice(1)); } catch (error) { message = location.hash.slice(1); }
document.write(message);
document.getElementById('message').value = message;
document.getElementById('message-form').addEventListener('submit', event => {
    event.preventDefault();
    location.hash = encodeURIComponent(document.getElementById('message').value);
    location.reload();
});
</script></div>
<?php end_page(); ?>
