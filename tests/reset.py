"""Verify two fresh-volume setup/reset cycles in an isolated Compose project."""
import http.cookiejar
import re
import subprocess
import tempfile
import urllib.parse
import urllib.request
import uuid
from pathlib import Path

root = Path(__file__).resolve().parents[1]
project = 'svwa-reset-test-'+uuid.uuid4().hex[:8]
with tempfile.TemporaryDirectory() as temp:
    override = Path(temp)/'ports.yaml'
    override.write_text('services:\n  web:\n    ports: !override\n      - "127.0.0.1:18888:80"\n')
    command = ['docker','compose','-p',project,'-f',str(root/'compose.yaml'),'-f',str(override)]
    try:
        for cycle in range(2):
            subprocess.run(command+['up','-d','--wait','--no-build'],check=True)
            counts = subprocess.check_output(command+['exec','-T','db','sh','-c',
                'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -B -e "SELECT COUNT(*) FROM svwa_accounts.accounts; SELECT COUNT(*) FROM svwa_accounts.student_results;"'],text=True)
            assert counts.split() == ['3','2'], counts
            for username, password in [('admin','password'),('user1','password1'),('user2','password2')]:
                client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
                base = 'http://127.0.0.1:18888'
                page = client.open(base+'/login.php').read().decode()
                csrf = re.search(r'name="csrf" value="([a-f0-9]+)"',page).group(1)
                response = client.open(base+'/login.php',urllib.parse.urlencode(dict(username=username,password=password,csrf=csrf)).encode())
                assert response.url.endswith('/profile.php')
            print(f'PASS reset cycle {cycle+1}: exactly 3 accounts, 2 results; all seeded logins work', flush=True)
            subprocess.run(command+['down','-v'],check=True)
    finally:
        subprocess.run(command+['down','-v'],check=True)
