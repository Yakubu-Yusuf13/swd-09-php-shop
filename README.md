# SWD PHP Shop

A PHP 8 storefront built for XAMPP with a MySQL-backed catalog, cart, order checkout, order lookup, and protected admin dashboard.

## Requirements

- XAMPP with Apache and MySQL running
- PHP 8+
- MySQL database access via `root` (or your local XAMPP user)

## Local setup

1. Place the project inside `C:\xampp\htdocs\swd\09-php-shop`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Create the database:
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Import the file `database.sql`
   - Or run the SQL file manually in MySQL
4. Open the shop in the browser:
   - `http://localhost/swd/09-php-shop/`

## Seed the catalog

This app includes a CLI seeding script. Run it from the project root:

```bash
C:\xampp\php\php.exe seed.php
```

The script creates the `swd_shop` database if it does not exist and inserts 15 realistic products across categories.

## Admin panel

The admin area is available here:

- `http://localhost/swd/09-php-shop/admin/login.php`

Default admin credentials:

- Username: `admin`
- Password: `admin123`

The password is verified with `password_verify()` and a hashed value stored in `admin/auth.php`.

## Customer order lookup

Customers can check order status here:

- `http://localhost/swd/09-php-shop/order-lookup.php`

They need the order reference and the email used during checkout.

## Deploy to Vercel

Vercel runs this PHP app using its Docker-based Container runtime (currently in Beta). The XAMPP MySQL service is local to your computer and cannot be used by a public deployment. Configure hosted MySQL and Redis services before deploying; the Redis connection is used to share customer carts and admin sessions between instances.

Suggested services:

- MySQL: Railway MySQL or another managed MySQL provider that allows connections from Vercel.
- Redis: Upstash Redis with TLS enabled.

1. Create a hosted MySQL database and import `database.sql` into it. This creates the tables and inserts the five categories and 15 catalog products. The inserts are safe to re-run and do not overwrite existing catalog records. Alternatively, import the schema then run `seed.php` once from a trusted CLI with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` set to the hosted database values; setting `DB_HOST` prevents the script from trying to create a database. Record the provider's CA certificate path if its MySQL service requires one.
2. Create a Redis database and copy its TLS endpoint and password. Do not commit credentials or post them in chat.
3. Sign in at [Vercel](https://vercel.com), choose **Add New → Project**, and import `Yakubu-Yusuf13/swd-09-php-shop` from GitHub. Set the project framework/preset to **Container** if it is not detected automatically.
4. In **Project Settings → Environment Variables**, set the following for Production (and Preview if desired):

   | Variable | Value |
   | --- | --- |
   | `DB_HOST` | Hosted MySQL hostname |
   | `DB_PORT` | Hosted MySQL port, usually `3306` |
   | `DB_NAME` | The database name |
   | `DB_USER` | Hosted MySQL username |
   | `DB_PASS` | Hosted MySQL password |
   | `DB_SSL_CA` | Optional PEM contents of the provider CA certificate for verified TLS |
   | `REDIS_SESSION_DSN` | Redis session endpoint, e.g. `tls://HOST:PORT?auth=PASSWORD` |
   | `ADMIN_PASSWORD_HASH` | A PHP `password_hash()` hash for a strong, unique admin password |

   Alternatively, set `DB_DSN` to a provider-compatible PDO MySQL DSN and supply `DB_USER` / `DB_PASS`. If the provider requires a CA certificate, paste its PEM contents into `DB_SSL_CA`. Do not set `APP_URL` unless the app is hosted under a subpath; the Vercel deployment uses the domain root automatically.
5. To generate `ADMIN_PASSWORD_HASH` locally, run this in PowerShell, replacing the example password with a strong unique one:

   ```powershell
   & 'C:\xampp\php\php.exe' -r "echo password_hash('replace-with-a-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Add only the resulting hash to Vercel's environment settings. Do not commit it. The local development login remains `admin` / `admin123`; that fallback is disabled on Vercel.
6. Click **Deploy** in Vercel. Once deployment finishes, open its assigned `.vercel.app` URL and verify the shop, checkout database connection, cart session, and admin login.

Vercel does not provide the external MySQL/Redis services automatically. Set up those providers and Vercel environment variables in their dashboards; never place database or Redis secrets in this repository. The `database.sql` file does not drop existing databases or tables.

## Project structure

- `app/bootstrap.php` — app setup, PDO connection, helpers, session, CSRF
- `app/cart.php` — shopping cart logic
- `index.php` — shop catalog and filtering
- `product.php` — product detail page
- `cart.php` — cart page
- `checkout.php` — order creation and transactional stock handling
- `order-success.php` — success screen after checkout
- `order-lookup.php` — customer lookup page
- `admin/` — login, dashboard, order listing, order detail management
- `assets/styles.css` — storefront and admin styling
- `seed.php` — schema + product seeding
- `database.sql` — export of the main database schema
- `Dockerfile` / `Caddyfile` — Vercel Container runtime configuration

## Notes

- Cart data is stored in the session and contains only product IDs and quantities.
- Product prices are always recalculated from the database before checkout to prevent tampering.
- The checkout transaction uses `SELECT ... FOR UPDATE` and atomic stock updates to protect against overselling.
