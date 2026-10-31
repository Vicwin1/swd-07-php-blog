# SWD Blog CMS

A PHP 8 and MySQL blog CMS for XAMPP.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Import `database.sql` into MySQL (phpMyAdmin or the MySQL CLI).
3. Configure the database connection with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables if the defaults (`127.0.0.1`, `swd_blog`, `root`, empty password) are not right.
4. Create the first administrator from the project directory:

   ```text
   C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com
   ```

   Enter a password of at least 12 characters when prompted. `ADMIN_PASSWORD` may also be supplied through the environment.
5. Open <http://localhost/swd/07-php-blog/>.

## Features

- Public post, category, and MySQL FULLTEXT search pages.
- Admin sign-in, dashboard, and post/category create, edit, and delete screens.
- Draft/published states, unique slugs, category and author foreign keys, and image uploads.
- CSRF-protected admin mutations, password hashing, strict session cookies, idle/absolute expiry, and session ID rotation.

The `app/` directory is denied by Apache and uploaded content cannot execute PHP. If the project is installed at a different URL path, update `APP_BASE_URL` in `app/helpers.php`.