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

## Notes

- Cart data is stored in the session and contains only product IDs and quantities.
- Product prices are always recalculated from the database before checkout to prevent tampering.
- The checkout transaction uses `SELECT ... FOR UPDATE` and atomic stock updates to protect against overselling.
