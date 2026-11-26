"""Read-only checks for encoding lessons and the existing reflected XSS exercise."""
import os
from pathlib import Path
from urllib.parse import quote
from playwright.sync_api import sync_playwright, expect
BASE=os.environ.get('SVWA_URL','http://127.0.0.1:8888')
payloads=["'\"><img src=x onerror=alert()>", "'\"><script>alert()</script>"]
with sync_playwright() as p:
    browser=p.chromium.launch(channel='chrome',headless=True)
    page=browser.new_page()
    dialogs=[]
    def dismiss(dialog):
        dialogs.append(dialog.type)
        dialog.dismiss()
    page.on('dialog',dismiss)
    page.goto(BASE+'/')
    page.locator('.card').filter(has=page.get_by_role('heading',name='URL Encoding',exact=True)).click()
    expect(page.locator('.lab-link.selected')).to_have_text('URL Encoding')
    assert page.locator('.content img, .content script').count()==0
    assert page.locator('pre').count()==5
    for payload in payloads:
        assert payload in page.locator('pre').all_text_contents()
        assert quote(payload,safe='') in page.locator('pre').all_text_contents()
    page.set_viewport_size({'width':390,'height':844})
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    page.get_by_role('link',name='Open the reflected XSS lab to practice').click()
    assert page.url==BASE+'/labs/html.php?search=test'
    for payload in payloads:
        single=quote(payload,safe='')
        response=page.request.get(BASE+'/labs/html.php?search='+single)
        assert 'Results for: '+payload+' </div>' in response.text()
        with page.expect_event('dialog'):
            page.goto(BASE+'/labs/html.php?search='+single)
    moodle=Path('D:/Courses/Web Application Security Testing/HTTP Basics/URL Encoding - Moodle.html')
    page.goto(moodle.as_uri())
    blocks=page.locator('pre code').all_text_contents()
    assert len(blocks)==5
    assert page.locator('script,style,img').count()==0
    assert 'Double URL encoding' not in page.inner_text('body')
    assert page.locator('th').count()==2
    assert page.get_by_role('link',name='Open the reflected XSS lab to practice').get_attribute('href')=='http://example.com:8888/labs/html.php?search=test'
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    page.eval_on_selector_all('[style]','nodes => nodes.forEach(node => node.removeAttribute("style"))')
    assert page.locator('pre code').all_text_contents()==blocks
    assert len(dialogs)==2
    browser.close()
print('PASS: menu, practice link, literal encoding examples, both single-encoded alerts, simplified Moodle and mobile layout.')
