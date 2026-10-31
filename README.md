# SWD Blog CMS

A PHP 8 and MySQL blog CMS for `http://localhost/swd/07-php-blog/`.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Import [database.sql](./database.sql) using phpMyAdmin or the MySQL command line. It creates the `swd_blog` database and its `admins`, `categories`, and `posts` tables.
3. Configure the database connection with environment variables if your local MySQL credentials differ from the defaults:
   - `DB_HOST` (default `127.0.0.1`)
   - `DB_PORT` (default `3306`)
   - `DB_NAME` (default `swd_blog`)
   - `DB_USER` (default `root`)
   - `DB_PASSWORD` (default empty)
4. Create the first admin from a terminal in this folder:

   ```text
   C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com
   ```

   The command securely prompts for a password on supported terminals. It must be at least 12 characters. You can also provide it using the `ADMIN_PASSWORD` environment variable.
5. Sign in at `http://localhost/swd/07-php-blog/admin/login.php` and create at least one category before writing posts.

The `app/` directory denies web access, logout and other state-changing admin actions require POST and CSRF tokens, and sessions use strict cookie settings and expire after inactivity. Cover uploads are limited to 2 MB, checked as JPEG/PNG/WebP by `finfo` and image decoding, stored under random names, and never rely on the client-supplied filename or MIME type.

If upgrading a database created before excerpts were added, run this once:

```sql
ALTER TABLE posts ADD COLUMN excerpt TEXT NULL AFTER slug;
```

## Environment notes

The application targets the URL above. Ensure Apache has `AllowOverride All` enabled so the access rules in `app/.htaccess` and `uploads/.htaccess` are applied. Enable PHP's PDO MySQL, Fileinfo, iconv, and mbstring extensions.
