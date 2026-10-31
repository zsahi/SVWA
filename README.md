# SAHI Vulnerable WebApp (SVWA)

15 focused exercises in SQL injection, reflected XSS, DOM XSS, stored XSS, and file uploads. Each exercise is a separate PHP file in `src/labs/`. Shared files handle layout, request parsing, and database connections.

See [DOCKER-GUIDE.md](DOCKER-GUIDE.md) for student setup and Docker commands.

```sh
docker compose up -d --build
```

Open http://localhost:8888. No login is required.

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
"# SVWA" 
