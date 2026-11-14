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
docker compose -f docker-compose.prod.yml up -d                                     # first start
docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d   # deploy a new version
docker compose -f docker-compose.prod.yml exec -u www-data web php bin/create-admin.php <email> <password> <name> <surname>
```

The database (`data/`) and uploaded photos (`public/uploads/`) are kept in the volumes `data` and `uploads`, so they survive redeploys.
On first start, the empty `data` volume is filled with the content committed in `data/`; after that, edit content through the admin, not in git.
Never run `docker compose down -v`, which deletes the volumes.
