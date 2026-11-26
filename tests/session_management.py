"""Verify the session lesson against existing authentication without changing profile values."""
import os
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
BASE=os.environ.get('SVWA_URL','http://127.0.0.1:8888')
with sync_playwright() as p:
    browser=p.chromium.launch(channel='chrome',headless=True)
    context=browser.new_context()
    page=context.new_page()
    page.goto(BASE+'/')
    page.locator('.card').filter(has=page.get_by_role('heading',name='Session Management',exact=True)).click()
    expect(page.locator('.lab-link.selected')).to_have_text('Session Management')
    for path in ['login.php','profile.php','edit_profile.php']:
        assert page.locator('.content a[href="/'+path+'"]').count()==1
    page.set_viewport_size({'width':390,'height':844})
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    context.close()
    context=browser.new_context()
    page=context.new_page()
    response=page.goto(BASE+'/login.php')
    header=response.all_headers().get('set-cookie','').lower()
    def session():
        return next(c for c in context.cookies() if c['name']=='SVWASESSID')
    anonymous=session()
    assert anonymous['httpOnly'] and anonymous['sameSite']=='Lax' and not anonymous['secure']
    assert 'svwasessid=' in header
    page.locator('#username').fill(os.environ.get('SVWA_TEST_USERNAME','user1'))
    page.locator('#password').fill(os.environ.get('SVWA_TEST_PASSWORD','password1'))
    with page.expect_response(lambda r: r.url.endswith('/login.php') and r.request.method=='POST') as login:
        page.get_by_role('button',name='Login',exact=True).click()
    assert login.value.status==303 and login.value.headers['location']=='/profile.php'
    expect(page).to_have_url(BASE+'/profile.php')
    signed=session()['value']
    assert signed!=anonymous['value']
    assert 'SVWASESSID='+signed in login.value.all_headers()['set-cookie']
    with page.expect_response(lambda r: r.url.endswith('/edit_profile.php') and r.request.method=='GET') as editor:
        page.get_by_role('link',name='Edit my own profile').click()
    assert 'SVWASESSID='+signed in editor.value.request.all_headers()['cookie']
    name=page.locator('#name').input_value()
    email=page.locator('#email').input_value()
    with page.expect_response(lambda r: r.url.endswith('/edit_profile.php') and r.request.method=='POST') as saved:
        page.get_by_role('button',name='Save profile',exact=True).click()
    assert saved.value.status==200
    assert 'SVWASESSID='+signed in saved.value.request.all_headers()['cookie']
    expect(page.get_by_text('Profile saved.',exact=True)).to_be_visible()
    expect(page.locator('#name')).to_have_value(name)
    expect(page.locator('#email')).to_have_value(email)
    # Missing and invalidated anonymous sessions must redirect without touching the browser cookie.
    probe=browser.new_context()
    missing=probe.request.get(BASE+'/profile.php',max_redirects=0)
    assert missing.status==303 and missing.headers['location']=='/login.php'
    old=probe.request.get(BASE+'/profile.php',headers={'Cookie':'SVWASESSID='+anonymous['value']},max_redirects=0)
    assert old.status==303
    valid=probe.request.get(BASE+'/profile.php',headers={'Cookie':'SVWASESSID='+signed},max_redirects=0)
    assert valid.status==200
    page.goto(BASE+'/profile.php')
    with page.expect_response(lambda r: r.url.endswith('/logout.php')) as logout:
        page.get_by_role('button',name='Logout',exact=True).click()
    assert logout.value.status==303
    assert 'expires=' in logout.value.all_headers()['set-cookie'].lower()
    replay=probe.request.get(BASE+'/profile.php',headers={'Cookie':'SVWASESSID='+signed},max_redirects=0)
    assert replay.status==303 and replay.headers['location']=='/login.php'
    probe.close()
    moodle=Path('D:/Courses/Web Application Security Testing/HTTP Basics/Session Management - Moodle.html')
    page.goto(moodle.as_uri())
    assert page.locator('style,script').count()==0
    for path in ['login.php','profile.php','edit_profile.php']:
        assert page.locator('a[href="http://example.com:8888/'+path+'"]').count()==1
    before=page.locator('pre code').all_text_contents()
    assert len(before)==6
    page.set_viewport_size({'width':390,'height':844})
    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
    page.eval_on_selector_all('[style]','nodes => nodes.forEach(node => node.removeAttribute("style"))')
    assert page.locator('pre code').all_text_contents()==before
    browser.close()
print('PASS: menu, cookie issuance and rotation, profile/edit requests, unchanged save, missing-cookie redirect, logout invalidation, and Moodle/mobile rendering.')
