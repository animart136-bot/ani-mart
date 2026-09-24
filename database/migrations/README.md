# Ani-Mart database migrations

Schema changes belong in versioned SQL files in this directory. They must not
be added to controllers or views.

```bash
php tools/migrate.php status
php tools/migrate.php migrate
```

For a database that existed before tracked migrations were introduced, verify
that its schema is current and record the existing files once:

```bash
php tools/migrate.php baseline --confirm-existing-schema
```

After baselining, every new migration is applied exactly once. Applied files
must never be edited: the runner verifies their SHA-256 checksums and uses a
database lock so two deployments cannot migrate simultaneously.
