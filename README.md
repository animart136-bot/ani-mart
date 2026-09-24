# Ani-Mart

Ani-Mart is a localized agricultural marketplace built with PHP and MySQL.

## Local setup

1. Copy `.env.example` to `.env` and fill in the local database settings.
2. Install PHP dependencies with `composer install`.
3. Import the database schema and run the migrations in `database/migrations/`.
4. Serve the project through Apache and open the URL configured in `APP_URL`.

Run the core test suite with:

```sh
composer test
```

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the GitHub and InfinityFree deployment checklist.

Never commit `.env`, SQL database exports, encryption keys, user uploads, logs,
`vendor`, or `node_modules`. These are excluded by `.gitignore`.
