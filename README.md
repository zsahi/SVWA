# SAHI Vulnerable WebApp (SVWA)

18 focused exercises in SQL injection, reflected XSS, DOM XSS, stored XSS, file uploads, SSRF, XXE, and broken access control. Shared files handle layout, request parsing, database connections, and account authorization.

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

### Classroom XSS report collector

`src/x.js` posts classroom reports to `/b.php`. The collector accepts URL-encoded
POST fields `origin` and `msg` and returns HTTP 201 with the saved report ID.
Reports are stored in the SQLite `reports` table (`id`, `received_at` in UTC,
`origin`, `msg`) at `/var/lib/svwa-reports/reports.sqlite`, outside the web root.
The Docker `reports` volume preserves these across container recreation; removing
volumes with `docker compose down -v` also deletes the reports. Rebuild with
`docker compose up -d --build` after adding these files. For a native PHP setup,
enable PDO SQLite and set `XSS_DB_PATH` to a writable database path outside the
web root; its parent directory must already exist.

Use synthetic classroom data: the script collects readable cookies, storage,
and page HTML. Open `/d.php` for the dashboard (no login required), with newest
reports first, 25 entries per page, and individual report details. Captured HTML
is displayed as escaped text, with an optional **Render HTML** panel for the
`Full DOM` section. The preview uses a sandboxed frame and restrictive CSP to
block scripts, forms, and external resources; inline styles and embedded data
images can display. The dashboard uses the
same database path as `/b.php`. Delete buttons on the list and detail views
permanently remove individual reports using POST forms with session CSRF tokens;
no login is required.

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

## XXE XML order preview

Rebuild with `docker compose up -d --build`, then open http://localhost:8888/labs/xxe.php. No database migration is needed. Submit a normal order, then use the linked explanation page's XML payloads to demonstrate reading `file:///etc/passwd` and requesting `http://127.0.0.1/internal/admin.php` through an external entity. The Linux filename is `/etc/passwd`, not `/etc/password`; the file belongs to the web container.

The editor sends a raw `application/xml` POST, independently of the Submission Type setting, ready to intercept in Burp Repeater. The deliberately unsafe DOM parser expands external entities using file/HTTP/HTTPS resources. Direct access to the internal admin fixture returns 403, while the XXE request reveals its fictional training flag. Use the supplied Apache stack, not a single-worker development server. Inputs and individual external resources are capped at 64 KiB; HTTP reads have a three-second timeout and redirects are disabled. These limits do not prevent XXE.

Run `python tests/xxe.py` for actual file-read and SSRF checks, baseline XML, invalid requests, missing resources, and plain-text output.
