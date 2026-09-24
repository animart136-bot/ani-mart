# Deploy Ani-Mart to InfinityFree

## 1. Push the source to GitHub

The repository intentionally excludes secrets, database exports, dependencies,
private files, logs, temporary files, and user uploads.

```sh
git push -u origin main
```

Keep the repository private if it contains business logic or data you do not
want to publish. Do not force-add any ignored file.

## 2. Prepare the upload locally

InfinityFree does not provide SSH, Composer, or npm on free hosting. Install the
production PHP dependencies locally before uploading:

```sh
composer install --no-dev --prefer-dist
composer dump-autoload --no-dev --optimize=false
```

Upload the project files into the domain's `htdocs` directory with an FTP
client. Include `vendor`, but do not upload `node_modules`, local SQL backups,
tests, tools, `.git`, or the local `.env` file. Ensure hidden files such as the
two `.htaccess` files are included.

The root `.htaccess` routes requests into `public/`, because InfinityFree does
not let an account change its web document root.

## 3. Create the production database

1. Create a MySQL database in the InfinityFree control panel.
2. Open the database using the control panel's phpMyAdmin link.
3. Import a clean schema or an intentionally sanitized database export.
4. Apply the SQL migrations in `database/migrations/` in chronological order
   if they are not already represented in the imported schema.

Use the exact database hostname and credentials shown by InfinityFree. The host
is not normally `localhost`, and free-hosting databases cannot be accessed
remotely.

## 4. Create the production `.env`

Create `.env` directly inside `htdocs` from `.env.example` and set at least:

```dotenv
APP_URL=https://your-domain.example
APP_ENV=production
APP_TIMEZONE=Asia/Manila

DB_HOST=the-host-shown-by-infinityfree
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-database-username
DB_PASSWORD=your-database-password

DATA_ENCRYPTION_KEY_BASE64=your-stable-random-base64-key
```

Generate the encryption key once on your own computer and store a secure backup:

```sh
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Do not change or lose this key after encrypted data has been stored. Add the
Cloudinary, Gemini, and mail settings only when those features are used.

## 5. Final checks

- Confirm the site opens at the HTTPS domain without `/public` in the URL.
- Confirm registration, login, images, and database-backed pages work.
- Confirm `/.env`, `/storage/`, `/vendor/`, `/database/`, and `/tests/` are not
  publicly accessible.
- Confirm `storage/logs` is writable by PHP.
- Keep local and remote backups of the production database and encryption key.
