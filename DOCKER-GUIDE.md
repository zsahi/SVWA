# SVWA — Docker command guide

## 1. Requirements

Install and start Docker Desktop with Linux containers on Windows or macOS. On Linux, use Docker Engine with the Docker Compose plugin. You need the complete SVWA folder, including `compose.yaml`, `Dockerfile`, `src`, `docker`, and `database`.

Check Docker:

```sh
docker version
docker compose version
```

## 2. Start SVWA

Open a terminal in the SVWA folder. For this Windows installation:

```powershell
cd C:\Users\LENOVO\Desktop\netscan\SVWA
docker compose up -d --build
docker compose ps
```

The first run downloads the base images, builds the application, and creates the sample database. Open **http://localhost:8888** after the services start. No account or login is needed. Internet access is needed for the initial image downloads; the running interface uses local assets.

Keep this intentionally vulnerable application local. The supplied configuration only exposes it on your computer's loopback address.

## 3. Stop and resume

Stop without deleting data:

```sh
docker compose stop
```

Resume:

```sh
docker compose start
```

Remove containers while keeping database records and uploaded files:

```sh
docker compose down
```

Start again:

```sh
docker compose up -d
```

## 4. Reset all lab data

These commands delete this project's database and uploads, then recreate a fresh lab:

```sh
docker compose down -v
docker compose up -d
```

Session settings return to defaults when the web container is recreated. The database initialization script runs only when the database volume is empty.

## 5. Rebuild after source changes

```sh
docker compose up -d --build
```

Source files are copied into the image, so editing PHP or CSS requires a rebuild. Existing database records and uploads remain.

## 6. Status and logs

```sh
docker compose ps
docker compose logs --tail=100 web
docker compose logs --tail=100 db
docker compose logs -f
```

Press Ctrl+C to exit log viewing; the containers keep running.

If Docker cannot connect to its daemon, start Docker Desktop and retry. If MySQL is still initializing, check its logs and wait for the service to become healthy. If port 8888 is occupied, change `127.0.0.1:8888:80` in `compose.yaml` to `127.0.0.1:8889:80`, run `docker compose up -d`, and open http://localhost:8889.

If an upload fails, keep the file below 2 MB. If a lab database query fails, Settings can enable database error output. Disable query and error output when practicing blind SQL injection.

## 7. Distribute prebuilt images

On the machine that built SVWA:

```sh
docker compose build
docker image save -o svwa-images.tar sahi/svwa:local mysql:8.0.46
```

Give students `svwa-images.tar` and the SVWA project folder. On a compatible Docker platform, students can load the images without building:

```sh
docker image load -i svwa-images.tar
docker compose up -d --no-build --pull never
```

The `database/init.sql` file must remain beside the Compose configuration in its `database` folder. Images exported this way target the source machine's container architecture. For a different architecture, use the normal build instructions instead.

The local image is named `sahi/svwa:local`; it is built locally and does not require a registry account.
