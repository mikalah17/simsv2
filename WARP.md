# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Tech stack and runtime
- PHP application (no framework) with MySQL/MariaDB backend.
- Core configuration: `php/db_config.php` (database host, name, user, password, port) and `sims.sql` (schema and seed for the `sims` database).
- Sessions and auth helpers: `php/auth_check.php`, plus `php/login.php`, `php/register.php`, and `php/logout.php`.

## Running the application locally
1. **Database setup**
   - Create a MySQL/MariaDB database named `sims`.
   - Import the schema dump:
     - From this directory:
       - PowerShell / bash: `mysql -u <db_user> -p sims < sims.sql`
   - Ensure the credentials in `php/db_config.php` match your local database user. Do not commit credential changes.

2. **Start a PHP development server**
   - From the project root:
     - `php -S localhost:8000 -t .`
   - Then open `http://localhost:8000/` in a browser. The landing page (`index.php`) links to `sign_in_page.html` and `sign_up_page.html`, which post to the PHP endpoints in `php/`.
   - If using an external stack like Laragon/XAMPP, point the web server document root to this directory so that `/php`, `/admin_pages`, and `/user_pages` resolve correctly.

## Diagnostics and maintenance scripts
These scripts are intended to be run from the project root with the `php` CLI while the database is available.

- **Connectivity and basic checks**
  - Database connectivity + table presence: `php db-debug.php`
  - Direct PDO diagnostic (used originally for Laragon): `php php/test_db.php`

- **Schema inspection and adjustment** (these operate directly on the DB):
  - Inspect core tables (`asset`, `employee`, `dept`, `request`): `php check_schema.php`
  - Ensure primary keys on core tables are `AUTO_INCREMENT` (modifies schema): `php fix_schema.php`
  - Inspect `users` table structure and a sample row: `php check_users.php`

Use these scripts instead of ad-hoc one-off debug files when checking DB issues.

## Testing
- There is **no automated test suite** (no PHPUnit or similar). "Tests" are performed by hitting pages in a browser and by using the diagnostic scripts above.
- To exercise a single script from the CLI (for debugging), run it directly from the project root, for example:
  - `php php/login.php` (with appropriate `$_POST` emulation only if you modify it to be CLI-friendly).
  - `php db-debug.php`

## High-level architecture

### Entry points and auth flow
- The application is a traditional PHP site structured around **page scripts** rather than a front controller.
- **Landing and public pages**:
  - `index.php` – static landing page with buttons to sign-in/sign-up.
  - `sign_in_page.html` – login form posting to `php/login.php`.
  - `sign_up_page.html` – registration form posting to `php/register.php`.
- **Authentication backend** (`php/`):
  - `php/db_config.php` – central place to construct a PDO connection via `getPDO()`; all DB-aware scripts should use this helper.
  - `php/login.php` – validates email/password, loads the user from `users`, sets session fields (including `role_type`), and redirects to either the admin or user dashboard.
  - `php/register.php` – validates and inserts into `users`, then populates the session and redirects to the user dashboard.
  - `php/logout.php` – destroys the session and redirects back to sign-in.
  - `php/auth_check.php` – reusable `require_login($requiredRole = null)` guard used by dashboards and restricted pages.

### Page layout and roles
- The UI is split by role into **admin** and **user** areas, each with its own sidebar and set of pages:
  - `admin_pages/` – admin-only pages, each thin PHP wrapper (`admin_*.php`) calling `require_login('admin')` and including the corresponding static layout HTML (`admin_*.html`).
  - `user_pages/` – user-facing pages, with a mix of pure HTML and PHP+HTML pages that embed business logic directly (for assets and requests).
- Both admin and user areas share a pattern:
  - A role-specific sidebar include (`admin_pages/admin_sidebar.php`, `user_pages/user_sidebar.php`).
  - A main content area that is either static (admin) or populated by PHP variables set earlier in the same file (user pages).

### Data model integration
- The **database schema** is defined in `sims.sql` with core tables:
  - `users` – application accounts with `role_type` (`admin`/`user`).
  - `asset` – inventory items and quantities.
  - `employee` and `dept` – employees and their departments.
  - `request` – asset requests, joining `asset` and `employee`.
  - `audit` and `query` – for logging activity and saved queries.
- User pages wire this schema into the UI:
  - `user_pages/user_dashboard.php` – reads summary slices of `asset` and `request` data into `$assets` and `$requests`, then includes `user_dashboard.html`, which renders those arrays via embedded PHP.
  - `user_pages/user_asset.php` – full CRUD for `asset` (add/update/delete) with validation and basic referential checks (e.g., preventing deletion when requests exist), then renders an HTML view and uses JS to post updates.
  - `user_pages/user_request.php` – builds dropdown data for `asset` and `employee`, handles new `request` inserts, and renders request history.

### Patterns to follow when extending
- **New authenticated pages** should:
  - Start with `require_once __DIR__ . '/../php/auth_check.php';` and a call to `require_login('admin')` or `require_login('user')`.
  - Reuse `php/db_config.php` and `getPDO()` for any DB access; avoid creating new PDO connections manually.
  - Follow the existing pattern of preparing data first in PHP, then including an HTML template (or rendering inline HTML) that uses simple `echo` + `htmlspecialchars()` for dynamic content.
- **Database changes** should be reflected in both:
  - The `sims.sql` dump (for new developers to bootstrap the database), and
  - The diagnostic/maintenance scripts if they rely on a fixed list of tables or columns.
