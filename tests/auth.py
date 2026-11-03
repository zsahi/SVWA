"""HTTP integration checks. Uses Docker only for setup, outbox access and test cleanup."""
import http.cookiejar
import json
import os
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE = os.environ.get('SVWA_TEST_URL', 'http://127.0.0.1:8888')
checks = 0

def check(condition, label):
    global checks
    assert condition, label
    checks += 1
    print('PASS', label)

def docker(*args):
    return subprocess.check_output(['docker', 'compose', *args], text=True)

def sql(statement):
    return docker('exec', '-T', 'db', 'sh', '-c',
                  'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -B -e "$1"', 'sh', statement).strip()

class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def request(self, path, data=None, cookie=None):
        headers = {'Cookie': cookie} if cookie else {}
        encoded = urllib.parse.urlencode(data).encode() if data is not None else None
        try:
            response = self.opener.open(urllib.request.Request(BASE+path, data=encoded, headers=headers), timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.read().decode(), response.url

    def post(self, path, data):
        _, page, _ = self.request(path)
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', page).group(1)
        return self.request(path, dict(data, csrf=token))

    def login(self, username, password):
        status, page, url = self.post('/login.php', dict(username=username, password=password))
        check(status == 200 and url.endswith('/profile.php') and f'<dd>{username}</dd>' in page, username+' login')


# Idempotent migration on an existing database preserves accounts and results.
before = sql('SELECT COUNT(*) FROM svwa_accounts.accounts; SELECT COUNT(*) FROM svwa_accounts.student_results;')
for _ in range(2):
    docker('exec', '-T', 'db', 'sh', '-c', 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot < /docker-entrypoint-initdb.d/accounts.sql')
check(sql('SELECT COUNT(*) FROM svwa_accounts.accounts; SELECT COUNT(*) FROM svwa_accounts.student_results;') == before, 'migration applied twice without duplicates')
check(sql('SELECT COUNT(*) FROM svwa_accounts.student_results;') == '2', 'exactly two seeded results')

anonymous = Client()
for roll in ('1001', '1002'):
    status, page, url = anonymous.request('/labs/result.php?roll_no='+roll)
    check(url.endswith('/login.php') and 'Marks / 100' not in page, 'anonymous result '+roll+' denied')

admin, user1, user2 = Client(), Client(), Client()
admin.login('admin', 'password')
user1.login('user1', 'password1')
user2.login('user2', 'password2')
directory = admin.request('/admin/users.php')[1]
check(all(f'>{name}</a>' in directory for name in ('admin', 'user1', 'user2')), 'admin lists all seeded users')
ids = dict(re.findall(r'profile.php\?id=(\d+)">([^<]+)</a>', directory))
ids = {name: account_id for account_id, name in ids.items()}
snapshots = {name: admin.request('/profile.php?id='+ids[name])[1] for name in ('admin', 'user2')}
check('user2@example.test' in snapshots['user2'], 'admin views another profile')
check(user1.request('/admin/users.php')[0] == 403, 'ordinary user denied directory')
check(user1.request('/admin/users.php', {'role': 'admin'})[0] == 403, 'tampered admin POST denied')
check(user1.request('/edit_profile.php', {'name': 'No CSRF', 'email': 'bad@example.test'})[0] == 403, 'profile CSRF enforced')
check(user1.post('/edit_profile.php', {'name': 'Ayesha Edited', 'email': 'user1@example.test'})[0] == 200, 'self profile edit accepted')
check('Ayesha Edited' in user1.request('/profile.php')[1], 'self edit persisted')
for target in ('user2', 'admin'):
    check(user1.request('/profile.php?id='+ids[target])[0] == 403, 'read '+target+' denied')
    for key, value in [('id', ids[target]), ('user_id', ids[target]), ('account_id', ids[target]), ('username', target), ('role', 'admin')]:
        check(user1.request('/edit_profile.php?'+urllib.parse.urlencode({key:value}))[0] == 403, 'GET editor selector denied '+key)
        check(user1.post('/edit_profile.php', {'name':'Tampered', 'email':'tampered@example.test', key:value})[0] == 403, 'POST editor selector denied '+key)
    check(user1.request('/profile.php?username='+target)[0] == 403, 'username profile selector denied')
    check(admin.request('/profile.php?id='+ids[target])[1] == snapshots[target], target+' profile unchanged')
check('<dd>user</dd>' in user1.request('/profile.php')[1], 'user1 role remains ordinary')
user1.post('/edit_profile.php', {'name':'Ayesha Khan', 'email':'user1@example.test'})
portal = user1.request('/labs/student-result.php')[1]
check('result.php?roll_no=1001' in portal and 'result.php?roll_no=1002' not in portal and 'Burp Repeater' not in portal, 'My Result uses own roll; no walkthrough initially')
for roll, name, subject, marks in [('1001','Ayesha Khan','Computer Science','88'),('1002','Bilal Ahmed','Applied Mathematics','67')]:
    status, page, _ = user1.request('/labs/result.php?roll_no='+roll)
    check(status == 200 and all(value in page for value in (roll,name,subject,marks)), 'same user1 session reads '+roll)
    check('password_hash' not in page and 'example.test' not in page and 'name="token"' not in page, 'result contains no account secrets')
for query, expected in [('',400),('?roll_no=9999',404),('?roll_no=1001%20OR%201=1',400),('?roll_no[]=1001',400)]:
    check(user1.request('/labs/result.php'+query)[0] == expected, 'invalid/unknown result '+query)
old_cookie = '; '.join(f'{c.name}={c.value}' for c in user1.jar)
check(user1.request('/logout.php')[0] == 405, 'GET cannot log out')
_, profile, _ = user1.request('/profile.php')
csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', profile).group(1)
user1.request('/logout.php', {'csrf':csrf})
for roll in ('1001','1002'):
    check(Client().request('/labs/result.php?roll_no='+roll, cookie=old_cookie)[2].endswith('/login.php'), 'old session denied after logout '+roll)

name = 'test_'+uuid.uuid4().hex[:12]
email = name+'@example.test'
new = Client()
try:
    status, page, url = new.post('/register.php', {'username':name,'name':'Test Student','email':email,'password':'testpassword1','confirm_password':'testpassword1','role':'admin','id':ids['admin']})
    check(url.endswith('/profile.php') and '<dd>user</dd>' in page, 'registration ignores supplied admin role and ID')
    check(new.request('/admin/users.php')[0] == 403, 'new account denied admin')
    check(new.request('/profile.php?id='+ids['user2'])[0] == 403, 'new account denied another profile')
    reset = Client()
    reset.post('/forgot_password.php', {'email':email})
    entries = docker('exec','-T','web','cat','/var/lib/svwa-reset/outbox.log').splitlines()
    path = next(json.loads(line)['path'] for line in reversed(entries) if json.loads(line)['to']==email)
    token = urllib.parse.parse_qs(urllib.parse.urlparse(path).query)['token'][0]
    check('Password changed.' in reset.post(path, {'token':token,'password':'changedpassword1','confirm_password':'changedpassword1'})[1], 'reset changes password')
    check(new.request('/profile.php')[2].endswith('/login.php'), 'reset invalidates existing session')
    check('Invalid or expired' in reset.post(path, {'token':token,'password':'changedpassword2','confirm_password':'changedpassword2'})[1], 'reset token is single-use')
    check('Invalid username or password.' in Client().post('/login.php', {'username':name,'password':'testpassword1'})[1], 'old password rejected')
    Client().login(name,'changedpassword1')
finally:
    sql("DELETE FROM svwa_accounts.accounts WHERE username='"+name+"';")
print(f'{checks} authentication checks passed.')
