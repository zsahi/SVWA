"""Exercise the three teaching pages; remove only comments created by this test."""
import json
import os
import re
import uuid
from urllib.parse import parse_qs, urlsplit
from playwright.sync_api import sync_playwright, expect
BASE = os.environ.get('SVWA_URL', 'http://127.0.0.1:8888')
marker = 'Web basics ' + uuid.uuid4().hex[:8]
created = set()
with sync_playwright() as p:
    browser = p.chromium.launch(channel='chrome', headless=True)
    context = browser.new_context()
    page = context.new_page()
    page.on('dialog', lambda dialog: dialog.accept())
    try:
        page.goto(BASE + '/')
        group = page.locator('details').filter(has=page.locator('summary', has_text='Web Development Basics'))
        assert group.locator('.lab-link').count() == 3
        page.locator('.card').filter(has=page.get_by_role('heading', name='HTML Forms and PHP', exact=True)).click()
        expect(page.locator('#full-name')).to_have_value('Ayesha Khan')
        expect(page.locator('#received-data')).to_have_count(0)
        # These explicit lesson forms stay independent of the global selector.
        page.locator('#submission').select_option('json')
        expect(page.locator('#submission')).to_be_enabled()
        page.locator('#full-name').fill(marker)
        page.locator('input[name="interests[]"][value="html"]').uncheck()
        with page.expect_response(lambda r: '/labs/html-forms.php?' in r.url and r.request.method == 'GET') as captured:
            page.get_by_role('button', name='Submit with GET', exact=True).click()
        values = parse_qs(urlsplit(captured.value.request.url).query)
        assert values['full_name'] == [marker]
        assert values['interests[]'] == ['php']
        assert values['student_code'] == ['STUDENT-001']
        assert values['lesson'] == ['html_forms']
        assert 'disabled_example' not in values and 'unnamed-example' not in values
        expect(page.locator('#full-name')).to_have_value(marker)
        expect(page.locator('#received-data pre').nth(0)).to_contain_text(marker)
        page.locator('input[name="interests[]"][value="php"]').uncheck()
        with page.expect_response(lambda r: r.url.endswith('/labs/html-forms.php') and r.request.method == 'POST') as captured:
            page.get_by_role('button', name='Submit with POST', exact=True).click()
        request = captured.value.request
        assert request.headers['content-type'].startswith('application/x-www-form-urlencoded')
        assert 'interests[]' not in parse_qs(request.post_data)
        expect(page.locator('input[name="interests[]"]:checked')).to_have_count(0)
        expect(page.locator('#received-data pre').nth(1)).to_contain_text(marker)
        page.locator('#attachment').set_input_files({'name':'lesson.txt','mimeType':'text/plain','buffer':b'Form upload example'})
        with page.expect_response(lambda r: r.url.endswith('/labs/html-forms.php') and r.request.method == 'POST') as captured:
            page.get_by_role('button', name='Submit with multipart POST', exact=True).click()
        assert captured.value.request.headers['content-type'].startswith('multipart/form-data; boundary=')
        expect(page.locator('#received-data pre').nth(2)).to_contain_text('[name] => lesson.txt')
        expect(page.locator('#received-data pre').nth(2)).to_contain_text('[error] => 0')
        expect(page.locator('#received-data pre').nth(1)).to_contain_text(marker)
        print('PASS: prefilled HTML form, GET, URL-encoded POST, multipart file, PHP arrays, omitted controls, and repopulated values.')

        page.goto(BASE + '/labs/javascript-json.php')
        expect(page.locator('#status')).to_have_text('Comments loaded.')
        page.evaluate('window.pageStayedLoaded = true')
        def json_action(callback):
            with page.expect_response(lambda r: r.url.endswith('/labs/javascript-json.php') and r.request.method == 'POST') as captured:
                callback()
            response = captured.value
            assert response.status == 200, response.text()
            assert response.request.headers['content-type'] == 'application/json'
            assert page.evaluate('window.pageStayedLoaded') is True
            expect(page.locator('#status')).not_to_have_text('Loading...')
            return response
        page.locator('#author').fill(marker)
        page.locator('#message').fill('JSON demonstration')
        response = json_action(lambda: page.get_by_role('button', name='Add comment', exact=True).click())
        ident = int(response.json()['saved_id']); created.add(ident)
        assert json.loads(response.request.post_data)['action'] == 'create'
        page.locator('#search').fill(marker)
        response = json_action(lambda: page.get_by_role('button', name='Filter comments', exact=True).click())
        assert len(response.json()['comments']) == 1
        row = page.locator('[data-comment-id="'+str(ident)+'"]')
        row.get_by_role('button', name='Edit', exact=True).click()
        page.locator('#message').fill('JSON updated')
        response = json_action(lambda: page.get_by_role('button', name='Save changes', exact=True).click())
        assert json.loads(response.request.post_data)['action'] == 'update'
        expect(row).to_contain_text('JSON updated')
        response = json_action(lambda: row.get_by_role('button', name='Delete', exact=True).click())
        assert json.loads(response.request.post_data)['action'] == 'delete'
        assert response.json()['comments'] == []
        created.remove(ident)
        expect(page.locator('#comment-list')).to_have_text('No comments found.')
        print('PASS: standalone JSON create/search/update/delete and DOM changes without document navigation.')

        page.goto(BASE + '/labs/php-database.php')
        page.locator('#author').fill(marker)
        page.locator('#message').fill('Database demonstration')
        with page.expect_response(lambda r: r.url.endswith('/labs/php-database.php') and r.request.method == 'POST'):
            page.get_by_role('button', name='Add comment', exact=True).click()
        notice = page.locator('#database-notice').inner_text()
        ident = int(re.search(r'Created comment #(\d+)',notice).group(1)); created.add(ident)
        expect(page.locator('#sql-statements')).to_contain_text("INSERT INTO messages (author, message) VALUES ('"+marker+"', 'Database demonstration')")
        page.locator('#search').fill(marker)
        page.get_by_role('button', name='Filter comments', exact=True).click()
        expect(page.locator('#database-comments article')).to_have_count(1)
        row = page.locator('[data-comment-id="'+str(ident)+'"]')
        row.get_by_role('link', name='Edit', exact=True).click()
        expect(page.locator('#message')).to_have_value('Database demonstration')
        expect(page.locator('#sql-statements')).to_contain_text('WHERE id = '+str(ident))
        page.locator('#message').fill('Database updated')
        page.get_by_role('button', name='Save changes', exact=True).click()
        expect(page.locator('#database-notice')).to_contain_text('UPDATE affected 1 row(s).')
        expect(page.locator('#sql-statements')).to_contain_text("message = 'Database updated' WHERE id = "+str(ident))
        expect(row).to_contain_text('Database updated')
        row.get_by_role('button', name='Delete', exact=True).click()
        expect(page.locator('#database-notice')).to_contain_text('DELETE affected 1 row(s).')
        expect(page.locator('#sql-statements')).to_contain_text('DELETE FROM messages WHERE id = '+str(ident))
        created.remove(ident)
        assert context.request.get(BASE+'/labs/guestbook-comments.php?id='+str(ident)).status == 404
        print('PASS: ordinary forms execute and display SELECT, INSERT, UPDATE, DELETE against the existing messages table.')

        for slug in ['html-forms','javascript-json','php-database']:
            page.goto(BASE+'/labs/'+slug+'.php')
            page.get_by_role('link', name='Read the student guide', exact=True).click()
            assert page.url.endswith('/labs/'+slug+'-guide.php')
            expect(page.get_by_role('link', name='Open the lab', exact=True)).to_be_visible()
            page.set_viewport_size({'width':390,'height':844})
            assert page.evaluate('document.documentElement.scrollWidth <= innerWidth'), slug+' guide overflow'
            page.get_by_role('link', name='Open the lab', exact=True).click()
            assert page.evaluate('document.documentElement.scrollWidth <= innerWidth'), slug+' lab overflow'
            source = context.request.get(BASE+'/labs/'+slug+'.php?source=1')
            assert source.status == 200 and '&lt;?php' in source.text()
        print('PASS: three linked guides, source views, and mobile layout.')
    finally:
        for ident in created:
            context.request.delete(BASE+'/labs/guestbook-comments.php?id='+str(ident))
        browser.close()
