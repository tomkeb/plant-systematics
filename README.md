# Plant systematics

## Docker

Local development (the project folder is mounted into the container, so edits show up immediately):

```sh
docker compose up --build
docker compose exec -u www-data web php bin/create-admin.php <email> <password> <name> <surname>
```

The site runs at http://localhost:8080. After changing `composer.json`, rebuild with `docker compose up --build -V`.

Production uses the image that GitHub Actions publishes to `ghcr.io/tomkeb/plant-systematics` on every push to `master`.
Copy `docker-compose.prod.yml` to the server (e.g. `/opt/katedra`) and run (the site is then at `http://<server-ip>:8080`):

```sh
mkdir -p data public/uploads/photos && sudo chown -R 33:33 data public/uploads      # once: writable by www-data
docker compose -f docker-compose.prod.yml up -d                                     # first start
docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d   # deploy a new version
docker compose -f docker-compose.prod.yml exec -u www-data web php bin/create-admin.php <email> <password> <name> <surname>
```

The content — the database (`data/`) and uploaded photos (`public/uploads/`) — is not in git or in the image.
In both setups it lives in those folders next to the compose file, so it survives redeploys and can be copied between
local dev and the server by hand, e.g. from local to server:

```sh
rsync -av data/ <server>:/opt/katedra/data/
rsync -av public/uploads/ <server>:/opt/katedra/public/uploads/
```

Sync while nobody is editing on the target side: the copy overwrites its changes.
After copying to the server, fix ownership again with `sudo chown -R 33:33 data public/uploads`.
