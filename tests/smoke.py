import http.cookiejar
import json
import re
import time
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:8888'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
checks = 0

def request(path, data=None, content_type=None):
    headers = {'Content-Type': content_type} if content_type else {}
    with client.open(urllib.request.Request(BASE + path, data=data, headers=headers), timeout=15) as response:
        assert response.status == 200
        return response.read().decode()

def check(condition, name):
    global checks
    assert condition, name
    checks += 1
    print('PASS', name)

def get(slug, **params):
    return request('/labs/' + slug + '.php?' + urllib.parse.urlencode(params))

def upload(slug, filename, mime, payload):
    boundary = 'SVWATestBoundary'
    body = (f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n{payload}\r\n--{boundary}--\r\n').encode()
    return request('/labs/' + slug + '.php', body, 'multipart/form-data; boundary=' + boundary)

home = request('/')
links = set(re.findall(r'href="(/labs/[^\"]+)"', home))
check(len(links) == 17, '17 distinct lab URLs')
for path in sorted(links):
    check('Fatal error' not in request(path), path)
check('Canvas Backpack' in get('numeric', id='1'), 'numeric baseline')
check('Wireless Keyboard' in get('numeric', id='0 OR 1=1'), 'numeric injection')
check('admin' in get('union', search="' UNION SELECT id,username,password FROM users -- "), 'UNION injection')
check('Product available.' in get('boolean', id='1 AND 1=1'), 'boolean true')
check('Product unavailable.' in get('boolean', id='1 AND 1=2'), 'boolean false')
start = time.monotonic()
get('time', id="1' AND SLEEP(1) -- ")
check(time.monotonic() - start >= .9, 'time delay')
check('Notification request received.' in get('time', id='999'), 'uniform notification response')
check('Canvas Backpack' in get('order', sort='IF(1=1,price,id) DESC'), 'ORDER BY expression')
check('<b>reflection</b>' in get('html', search='<b>reflection</b>'), 'HTML reflection')
check('value="x" autofocus onfocus="window.svwa=1"' in get('attribute', name='x" autofocus onfocus="window.svwa=1'), 'attribute breakout')
check("const name = '';window.svwa=1;//'" in get('javascript', name="';window.svwa=1;//"), 'JavaScript breakout')
for mode in ['get','post','json','multipart']:
    request('/settings.php', urllib.parse.urlencode({'submission': mode}).encode())
    check(f'data-submission="{mode}"' in request('/labs/guestbook.php'), 'guestbook setting ' + mode)
    fields = {'author': 'Smoke test', 'message': '<b>stored-' + mode + '</b>'}
    if mode == 'get':
        page = get('guestbook', **fields)
    elif mode == 'json':
        page = request('/labs/guestbook.php', json.dumps(fields).encode(), 'application/json')
    elif mode == 'multipart':
        boundary = 'SVWATestFields'
        body = ''.join(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n' for key,value in fields.items()) + f'--{boundary}--\r\n'
        page = request('/labs/guestbook.php', body.encode(), 'multipart/form-data; boundary=' + boundary)
    else:
        page = request('/labs/guestbook.php', urllib.parse.urlencode(fields).encode())
    check(fields['message'] in page and fields['message'] in request('/labs/guestbook.php'), 'stored rendering ' + mode)
for slug,filename,mime in [('unrestricted','marker.php','application/octet-stream'),('mime','marker.php','image/png'),('extension','marker.phtml','application/octet-stream')]:
    page = upload(slug,filename,mime,'<?php echo "SVWA_UPLOAD_OK"; ?>')
    link = re.search(r'href="(/uploads/[^\"]+)"',page)
    check(link is not None, slug + ' accepts test upload')
    check(request(link.group(1)).strip() == 'SVWA_UPLOAD_OK', slug + ' uploaded PHP executes')
check('Only JPEG and PNG' in upload('mime','marker.txt','text/plain','sample'), 'MIME rejection')
check('PHP files are not accepted' in upload('extension','marker.php','application/octet-stream','sample'), 'extension rejection')
request('/settings.php',b'submission=post')
print(f'{checks} checks passed.')
