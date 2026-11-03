# SAHI Vulnerable WebApp (SVWA)

16 focused exercises in SQL injection, reflected XSS, DOM XSS, stored XSS, file uploads, and broken access control. Shared files handle layout, request parsing, database connections, and account authorization.

See [DOCKER-GUIDE.md](DOCKER-GUIDE.md) for student setup and Docker commands.

```sh
docker compose up -d --build
```

Open http://localhost:8888. Existing exercises remain public; the Student Result IDOR lab requires login.

Training accounts: `admin / password` (administrator), `user1 / password1`, and `user2 / password2` (ordinary users). Seed emails are `admin@example.test`, `user1@example.test`, and `user2@example.test`. Public registration always creates an ordinary user. Administrators can list/view accounts; self-service editing always targets the signed-in account.

The lab is at http://localhost:8888/labs/student-result.php. Account data and fictional results live in the separate `svwa_accounts` database, inaccessible to the legacy SQL lab database user. The legacy `svwa.users` records remain unchanged for SQL-injection exercises. As before, the application also contains deliberate XSS and executable-upload labs; this is a local training environment, not a production security boundary against arbitrary PHP execution.

For an **existing database volume**, run the additive migration after rebuilding (PowerShell or POSIX shell):

```sh
docker compose up -d --build
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot < /docker-entrypoint-initdb.d/accounts.sql'
```

The migration can be repeated without duplicate accounts/results and preserves existing passwords and profile edits. Fresh setup and the normal volume reset automatically seed the three accounts and two results. See [DOCKER-GUIDE.md](DOCKER-GUIDE.md) for reset instructions and local password-reset delivery.

Authentication is adapted from the read-only `netscan/webapp` implementation (`auth.php`, `account_page.php`, login/register/profile wrappers and logout). Its forgot-password form was demo-only; SVWA adds hashed, expiring, single-use reset tokens and invalidates existing sessions after a reset.

Burp Repeater demonstration:

1. Login as `user1 / password1` and open the Student Result IDOR lab.
2. Intercept **My Result**: `GET /labs/result.php?roll_no=1001`. Send it to Repeater.
3. Send the baseline request: Ayesha Khan, Computer Science, 88/100.
4. Keep the session cookie and change only `1001` to `1002`. Send again: Bilal Ahmed, Applied Mathematics, 67/100.
5. Logout through My Profile; replaying either request with the old cookie redirects to Login.

The initial portal shows only the learner objective and the signed-in student's result link. A separate explanation page describes the missing ownership check and its fix; SVWA has no secure-mode convention.

This intentionally vulnerable application is for local practice. The default configuration binds the website to localhost and does not publish MySQL's port.

Settings select GET, URL-encoded POST, JSON POST, or multipart POST for SQL, reflected XSS, and stored XSS. DOM exercises use browser URL input. Upload exercises always use multipart. SQL query and database error displays are off by default so blind exercises retain their intended behavior.

## Files

- `src/labs/`: individual exercises, with the vulnerable operation visible in each file.
- `src/includes/`: shared layout, catalog, and helpers.
- `src/assets/`: local stylesheet and request submission script; no CDN dependencies.
- `database/init.sql`: sample records and initial schema.
- `Dockerfile`: PHP/Apache application image.
- `compose.yaml`: application and MySQL services with persistent volumes.
- `tests/smoke.py`: HTTP behavior checks against a running local instance.

Run verification with `python tests/smoke.py`. The checks add test guestbook messages and uploaded marker files. Use the guide's reset commands to return to the initial state.

Run `python tests/auth.py` for account/IDOR HTTP checks (requires the normal Compose stack). It reapplies the migration twice, exercises all seeded logins, directory permissions, profile tampering, registration, logout and password reset. It restores user1's seeded name/email and deletes its temporary registered account. Run `python tests/reset.py` for two fresh setup/reset cycles in a temporary, isolated Compose project on port 18888; its volumes are removed afterwards.
