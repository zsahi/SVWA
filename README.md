# SAHI Vulnerable WebApp (SVWA)

17 focused exercises in SQL injection, reflected XSS, DOM XSS, stored XSS, file uploads, SSRF, and broken access control. Shared files handle layout, request parsing, database connections, and account authorization.

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

The shared header?s **Submission Type** dropdown selects GET, URL-encoded POST, JSON POST, or multipart POST for SQL, reflected XSS, and stored XSS. It saves automatically and updates the current form without clearing input. The Settings page is retained for future options. DOM exercises use browser URL input. Upload exercises always use multipart. The top-right header on SQL Injection lab pages contains **Display SQL queries** and **Display database errors** toggles. Changes save automatically for the current session and apply to the next lab request without resubmitting the current page. Both displays are off by default so blind exercises retain their intended behavior.

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

## SSRF stock checker

Open http://localhost:8888/labs/ssrf.php after rebuilding with `docker compose up -d --build`. No database migration is needed for this lab. Click **Check stock**, intercept the JavaScript POST request in Burp, then change `stockApi` to `http://127.0.0.1/internal/admin.php` to retrieve a fictional training flag through the server. Direct browser access to `/internal/admin.php` returns 403 with the supplied Docker setup. A separate explanation page covers the missing destination restriction and remediation.

The internal fixtures use the actual connection address, not forwarding headers. Run this exercise with the supplied Apache/Docker setup: a host-native server or loopback reverse proxy changes the trust boundary, and a single-worker PHP development server cannot service a request back to itself. HTTP/HTTPS fetches have a three-second read timeout and 64 KiB response cap; redirects are disabled. The product page sends `stockApi` through a JavaScript URL-encoded POST, independently of Settings; there is no visible URL input. All services run in the same web instance.

Run `python tests/ssrf.py` for SSRF checks (normal stock response, internal access, all request formats, invalid input, failed connections, and plain-text output).
