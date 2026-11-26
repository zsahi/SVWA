"""Read-only browser checks for the HTTP tracing exercise."""
import os
from urllib.parse import quote
from playwright.sync_api import sync_playwright, expect

BASE = os.environ.get('SVWA_URL', 'http://127.0.0.1:8888')
PATH = '/labs/trace-request.php'
with sync_playwright() as p:
    browser = p.chromium.launch(channel='chrome', headless=True)
    context = browser.new_context()
    page = context.new_page()
    page.goto(BASE + '/')
    page.locator('.card[href="' + PATH + '"]').click()
    expect(page.get_by_role('heading', name='Trace a Request and Its Response', exact=True)).to_be_visible()
    expect(page.locator('.lab-link.selected')).to_have_attribute('href', PATH)
    expect(page.locator('#search-term')).to_have_value('Lamp')
    expect(page.locator('.result')).to_have_count(0)
    # Global settings must not turn this GET exercise into a POST or JSON request.
    page.locator('#submission').select_option('json')
    expect(page.locator('#submission')).to_be_enabled()
    for term, title in [('Lamp', 'Desk Lamp'), ('Keyboard', 'Wireless Keyboard'), ('zz-no-such-product-zz', 'No products found')]:
        page.locator('#search-term').fill(term)
        with page.expect_response(lambda r: PATH + '?q=' + term in r.url) as captured:
            page.get_by_role('button', name='Search', exact=True).click()
        response = captured.value
        assert response.request.method == 'GET'
        assert response.status == 200
        assert 'text/html' in response.headers['content-type']
        expect(page.locator('.result')).to_contain_text(title)
        if term == 'Lamp':
            body = response.text()
            start = body.index('<section class="panel result"')
            end = body.index('</section>', start) + len('</section>')
            print('Captured form request: GET ' + PATH + '?q=Lamp')
            print(body[start:end])
        print('PASS form search:', term, '->', title)
    payload = '<script>window.traceInjected=1</script>"&'
    page.goto(BASE + PATH + '?q=' + quote(payload))
    expect(page.locator('.result strong')).to_have_text(payload)
    expect(page.locator('#search-term')).to_have_value(payload)
    assert page.evaluate('window.traceInjected') is None
    expect(page.locator('.result script')).to_have_count(0)
    expect(page.get_by_role('heading', name='Example only: annotated HTTP/1.1 exchange')).to_be_visible()
    assert r'\r\n' in page.locator('pre').first.inner_text()
    page.goto(BASE + PATH + '?q[]=Lamp')
    expect(page.locator('.result')).to_contain_text('No products found')
    page.goto(BASE + PATH + '?q=' + quote("' OR 1=1 -- "))
    expect(page.locator('.result')).to_contain_text('No products found')
    page.set_viewport_size({'width': 390, 'height': 844})
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    browser.close()
    print('PASS: navigation, GET despite JSON setting, database results, safe escaping, malformed query, labeled example, mobile width.')
