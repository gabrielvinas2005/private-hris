# Attachments Database Migrations

This directory contains migrations specifically for the **attachments database** (separate from the main database).

## Why Separate?

The attachments database is a separate SQL Server database used to store file attachments (resumes, documents, etc.) as BLOBs. To prevent conflicts and ensure clean migrations, these migrations are kept separate from the main database migrations.

## Creating New Migrations

### Create a New Migration File

Create a new migration file in the attachments directory:

```bash
php artisan make:migration:attachments create_example_table
```

Create a migration that creates a table:
```bash
php artisan make:migration:attachments create_example_table --create=example_table
```

Create a migration that modifies an existing table:
```bash
php artisan make:migration:attachments add_column_to_example_table --table=example_table
```

The command will automatically:
- Create the migration file in `database/migrations/attachments/`
- Add `protected $connection = 'attachments';` to the migration class

## Running Migrations

### Run Migrations

```bash
php artisan migrate:attachments
```

### Check Migration Status

```bash
php artisan migrate:attachments --status
```

This will show which migrations have been run and which are pending.

### Fresh Migration (Drops all tables and re-runs)

```bash
php artisan migrate:attachments --fresh
```

### Rollback Migrations

Rollback the last batch of migrations:
```bash
php artisan migrate:attachments --rollback
```

Rollback a specific number of migrations:
```bash
php artisan migrate:attachments --rollback --step=1
```

## Important Notes

- **DO NOT** run `php artisan migrate` on this directory - it will try to use the default database connection
- Always use `php artisan migrate:attachments` for these migrations
- The migration files in this directory have `protected $connection = 'attachments'` set
- Regular migrations in the parent `migrations/` directory will continue to use the default database connection

## Current Migrations

- `2026_03_02_143152_create_applicant_attachments_blob_table.php` - Applicant resume/file attachments (BLOB)
- `2026_03_03_090500_create_employee_documents_blob_table.php` - Employee HR documents (BLOB)
- `2026_06_11_084236_create_non_dtr_attachments_table.php` - Non-DTR task attachments (VARBINARY)

