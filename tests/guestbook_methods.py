"""Verify guestbook UI methods and Stored XSS rendering; clean up only our comments."""
import os
import uuid
from urllib.parse import urlencode
from playwright.sync_api import sync_playwright, expect
BASE = os.environ.get('SVWA_URL', 'http://127.0.0.1:8888')
API = BASE + '/labs/guestbook-comments.php'
marker = 'Guestbook test ' + uuid.uuid4().hex[:8]
created = set()
with sync_playwright() as p:
    browser = p.chromium.launch(channel='chrome', headless=True)
    context = browser.new_context()
    page = context.new_page()
    errors=[]
    page.on('pageerror', lambda error: errors.append(str(error)))
    try:
        page.goto(BASE + '/')
        page.locator('.card[href="/labs/guestbook-methods.php"]').click()
        expect(page.locator('.lab-link.selected')).to_have_attribute('href','/labs/guestbook-methods.php')
        expect(page.locator('#comment-create input[name="csrf"]')).to_have_count(0)
        # Creation uses PUT even with the global JSON setting.
        page.locator('#submission').select_option('json')
        expect(page.locator('#submission')).to_be_enabled()
        def action(method, status, callback):
            with page.expect_response(lambda r: r.url.startswith(API) and r.request.method == method) as captured:
                callback()
            response=captured.value
            assert response.status == status, response.text()
            assert 'csrf=' not in (response.request.post_data or '')
            if method in ['GET','DELETE','OPTIONS']: assert not response.request.post_data
            expect(page.locator('#guestbook-manager')).not_to_have_attribute('aria-busy','true')
            expect(page.locator('#comment-exchange')).to_have_count(0)
            return response
        page.locator('#author').fill(marker)
        page.locator('#message').fill('Read HTTP notes.')
        response=action('PUT',201,lambda: page.get_by_role('button',name='Post comment',exact=True).click())
        comment=response.json(); ident=comment['id']; created.add(ident)
        assert response.headers['location'] == '/labs/guestbook-comments.php?id='+str(ident)
        assert comment['author'] == marker and comment['message'] == 'Read HTTP notes.'
        row=page.locator('[data-comment-id="'+str(ident)+'"]')
        got=action('GET',200,lambda: row.get_by_role('button',name='View',exact=True).click())
        assert got.json()['id'] == ident
        page.locator('#comment-edit').click()
        page.locator('#edit-author').fill('Student One')
        page.locator('#edit-message').fill('Try all six methods.')
        expect(page.get_by_role('button',name='Replace comment',exact=True)).to_have_count(0)
        put=context.request.put(API+'?id='+str(ident),form={'author':'Student One','message':'Try all six methods.'})
        assert put.status==200
        replaced=put.json()
        action('GET',200,lambda: row.get_by_role('button',name='Edit',exact=True).click())
        assert replaced['author']=='Student One' and replaced['message']=='Try all six methods.'
        page.locator('#edit-author').fill('Student Two')
        patched=action('PATCH',200,lambda: page.get_by_role('button',name='Save changes',exact=True).click())
        assert patched.request.post_data == 'author=Student+Two'
        assert patched.json()['message']=='Try all six methods.'
        assert patched.json()['created_at']==comment['created_at']
        page.locator('#edit-message').fill('Message-only edit')
        patched=action('PATCH',200,lambda: page.get_by_role('button',name='Save changes',exact=True).click())
        assert patched.request.post_data == 'message=Message-only+edit'
        assert patched.json()['author']=='Student Two'
        expect(page.get_by_role('button',name='Available actions',exact=True)).to_have_count(0)
        options=context.request.fetch(API+'?id='+str(ident),method='OPTIONS')
        assert options.status==204
        assert options.headers['allow']=='GET, PUT, PATCH, DELETE, OPTIONS'
        assert options.body()==b''
        assert context.request.fetch(API,method='OPTIONS').headers['allow']=='GET, POST, PUT, OPTIONS'
        item=API+'?id='+str(ident)
        assert context.request.put(item,form={'author':'Incomplete'}).status==400
        assert context.request.patch(item,form={'message':''}).status==400
        assert context.request.get(item).json()['message']=='Message-only edit'
        assert context.request.post(item,form={'author':'x','message':'x'}).status==405
        assert context.request.delete(API).status==405
        assert context.request.get(API+'?id=abc').status==400
        # Same comment is accessible from another session and the original guestbook.
        other=browser.new_context()
        assert other.request.get(item).json()['id']==ident
        other.close()
        # Add enough owned fixtures to exercise pagination without changing existing comments.
        for n in range(11):
            fixture=context.request.put(API,form={'author':marker,'message':'Pagination '+str(n)})
            assert fixture.status==201
            created.add(fixture.json()['id'])
        page.locator('#comment-keyword').fill(marker)
        filtered=action('POST',200,lambda: page.get_by_role('button',name='Filter comments',exact=True).click()).json()
        assert filtered['total']==11 and filtered['page']==1
        assert context.request.post(API,form={'q':marker,'page':'0'}).status==400
        before=context.request.get(API).json()['total']
        assert context.request.post(API,form={'author':'Not a create','message':'Ignored'}).status==400
        assert context.request.get(API).json()['total']==before
        assert all(c['author']==marker for c in filtered['comments'])
        page2=action('POST',200,lambda: page.get_by_role('button',name='Next page',exact=True).click()).json()
        assert page2['page']==2 and page2['q']==marker and page2['total']==11
        assert page.locator('#comment-list article').count()<=10
        page.locator('#comment-keyword').fill('Message-only edit')
        filtered=action('POST',200,lambda: page.get_by_role('button',name='Filter comments',exact=True).click()).json()
        assert any(c['id']==ident for c in filtered['comments']) and filtered['page']==1
        page.locator('#comment-keyword').fill(marker+'-unmatched')
        empty=action('POST',200,lambda: page.get_by_role('button',name='Filter comments',exact=True).click()).json()
        assert empty['total']==0 and not empty['comments']
        expect(page.locator('#comment-list')).to_have_text('No comments found.')
        all_comments=action('POST',200,lambda: page.get_by_role('button',name='Clear filter',exact=True).click()).json()
        assert all_comments['q']=='' and all_comments['total']>=12
        page.locator('#comment-keyword').fill('Message-only edit')
        action('POST',200,lambda: page.get_by_role('button',name='Filter comments',exact=True).click())
        selected=page.locator('[data-comment-id="'+str(ident)+'"]')
        assert action('GET',200,lambda: selected.get_by_role('button',name='View',exact=True).click()).json()['id']==ident
        page.on('dialog',lambda dialog: dialog.accept())
        assert action('DELETE',204,lambda: page.locator('#comment-delete').click()).body()==b''
        created.remove(ident)
        gone=context.request.get(item)
        assert gone.status==404
        assert gone.json()=={'error':'Comment not found.'}
        print('PASS: normal HTTP Basics entry; PUT create, POST search, ID lookup GET, PUT replace, changed-field PATCH, OPTIONS, DELETE and GET 404; shared data, author/message filtering, filtered pagination, no matches, and clear filter.')
        # Original guestbook also creates with PUT regardless of the global setting.
        for mode in ['get','post','json','multipart']:
            context.request.post(BASE+'/settings.php',form={'submission':mode})
            legacy_author=marker+' '+mode
            fields={'author':legacy_author,'message':'<b>Stored rendering '+mode+'</b>'}
            page.goto(BASE+'/labs/guestbook.php')
            expect(page.locator('#comment-create')).not_to_have_attribute('data-submission',mode)
            page.locator('#author').fill(legacy_author)
            page.locator('#message').fill(fields['message'])
            response=action('PUT',201,lambda: page.get_by_role('button',name='Post comment',exact=True).click())
            created.add(response.json()['id'])
            expect(page.locator('#comment-list .message-body b').filter(has_text='Stored rendering '+mode)).to_be_visible()
            found=[c for c in context.request.get(API).json()['comments'] if c['author']==legacy_author]
            assert len(found)==1
            created.add(found[0]['id'])
        # Edit and delete from the original Stored XSS page too.
        target=found[0]['id']
        row=page.locator('[data-comment-id="'+str(target)+'"]')
        action('GET',200,lambda: row.get_by_role('button',name='Edit',exact=True).click())
        page.locator('#edit-author').fill('Updated name')
        action('PATCH',200,lambda: page.get_by_role('button',name='Save changes',exact=True).click())
        assert context.request.get(API+'?id='+str(target)).json()['message']=='<b>Stored rendering multipart</b>'
        page.set_viewport_size({'width':390,'height':844})
        assert page.evaluate('document.documentElement.scrollWidth <= innerWidth')
        action('DELETE',204,lambda: page.locator('#comment-delete').click())
        created.remove(target)
        page.goto(BASE+'/labs/practice-note.php')
        assert page.url.endswith('/labs/guestbook-methods.php')
        assert not errors, errors
        print('PASS: original guestbook PUT create across global modes, Stored XSS rendering, edit/delete, mobile layout, old lesson redirect.')
    finally:
        for ident in created:
            context.request.delete(API+'?id='+str(ident))
        browser.close()
