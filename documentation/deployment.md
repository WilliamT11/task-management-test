# Deployment Guide

## Prepare the application

1. Deploy to a PHP 8.2+ host with Composer and MySQL/MariaDB.
2. Configure production `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, MySQL connection values, suitable cache/session drivers, and mail settings.
3. Generate unique application and JWT secrets on the host with `php artisan key:generate` and `php artisan jwt:secret`. Never commit `.env` or use the sample credentials in a public deployment.
4. Install dependencies, build assets, and apply migrations:

   ```sh
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   ```

5. Point the web-server document root at `public/`. Ensure `storage/` and `bootstrap/cache/` are writable by the PHP worker. Keep uploads on the private local disk or configure a private object-storage disk.
6. Rebuild Laravel's cached configuration after environment setup with `php artisan optimize`.

## Operational limitations

Background jobs are not implemented. Do not select the database queue driver until queue migrations and job handlers are added. Real-time broadcasting, thumbnail generation, and comment APIs are also outside the current implementation.

Use HTTPS in production, rotate demo passwords, protect application/JWT keys, restrict database credentials, and configure PHP/web-server upload limits consistently with the 50 MB application limit.
## Deploying to Railway (Docker)

The repository ships a `Dockerfile` (PHP 8.2 + Apache, Vite assets built in a separate stage) and `docker/start.sh`, which binds Apache to `$PORT`, runs `migrate --force`, optionally seeds, and caches config. The same image works on Render or Fly.io.

1. Install the CLI and create the project: `npm i -g @railway/cli`, `railway login`, `railway init`.
2. Add a database: `railway add --database mysql`.
3. Create the app service from this folder with `railway up`, then in the service settings add a **volume** mounted at `/var/www/html/storage/app` so uploads survive redeploys, and generate a public domain.
4. Set the service variables:

   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:...            # php artisan key:generate --show
   JWT_SECRET=...                # random 64-char string
   APP_URL=https://<your-domain>
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   QUEUE_CONNECTION=sync
   CACHE_STORE=file
   SESSION_DRIVER=file
   LOG_CHANNEL=stderr
   SEED_ON_DEPLOY=true           # first deploy only; set to false afterwards
   ```

5. Redeploy (`railway up`). The health check endpoint is `/up`.
