# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project overview

This repository implements a small PHP/MySQL-based Simple Inventory Management System (SIMS) with separate admin and user dashboards. The stack is:
- Static HTML/CSS/vanilla JS for all pages
- Plain PHP scripts for authentication, authorization, and page protection
- A MySQL database defined in `SIMS db.sql`

High-level structure at the repo root:
- `index.html`, `sign_in_page.html`, `sign_up_page.html` – public landing and auth forms
- `php/` – backend PHP scripts (DB config, login, registration, session/auth helpers)
- `admin_pages/` – admin-only pages, each with a `.php` access wrapper and a `.html` layout
- `user_pages/` – user-only pages, same pattern as admin
- `image/` – static images used by the UI
- `SIMS db.sql` – MySQL schema and empty tables for assets, employees, departments, requests, audits, users, and saved queries

There is no build system, package manager configuration, or automated test framework in this repo; everything runs directly from the PHP and HTML files.

## Running the application locally

The app is designed to run on any PHP-capable web server (e.g., Apache+PHP, XAMPP, WAMP, or the PHP built-in server) with a MySQL-compatible database.

### 1. Set up the database

1. Create a MySQL database (for example, `sims` or `sims_db`).
2. Import `SIMS db.sql` into that database.
3. Update `php/db_config.php` to match your actual database name, host, user, password, and port:
   - `$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS`, `$DB_PORT`
4. Ensure the `users` table contains at least one row with `role_type = 'admin'` so you can access admin pages after logging in.

### 2. Run a local PHP server (simple setup)

From the repository root (where `index.html` lives), run:

```bash path=null start=null
php -S localhost:8000
```

Then open `http://localhost:8000/` in a browser. Navigation flow:
- `index.html` is the landing page with **Sign-in** and **Sign-up** buttons.
- `sign_in_page.html` posts to `php/login.php`.
- `sign_up_page.html` posts to `php/register.php`.
- On successful login, users are redirected to:
  - `admin_pages/admin_dashboard.php` if `role_type = 'admin'`
  - `user_pages/user_dashboard.php` otherwise

If you are using Apache/XAMPP/WAMP instead of the built-in server, place this folder under the HTTP root (e.g., `htdocs`) and browse to `http://localhost/simsv2/` (adjust path as needed).

### 3. Linting and tests

This repo does **not** define any linting or automated test commands:
- There is no `composer.json`, PHPUnit config, or other test tooling.
- There is no PHP linter or formatter configuration checked in.

If you want to introduce linting or tests, you will need to add your own tools (e.g., PHP_CodeSniffer, PHPUnit) and corresponding commands.

## Application architecture

### Authentication and session management

Authentication and authorization are handled entirely in the `php/` directory:
- `php/db_config.php`
  - Defines DB connection settings and a `getPDO()` helper that creates a `PDO` instance with exceptions and associative fetch mode enabled.
  - All DB-using scripts should call `getPDO()` instead of creating their own connections.
- `php/auth_check.php`
  - Starts the session and provides two helpers:
    - `is_logged_in()` – checks for `$_SESSION['logged_in']` and `$_SESSION['user_id']`.
    - `require_login($requiredRole = null)` – enforces login and (optionally) a specific role.
  - If the user is not logged in, it redirects to `sign_in_page.html?error=auth`.
  - If `$requiredRole` is provided, it compares it (case-insensitive) against `$_SESSION['role_type']` or the legacy `$_SESSION['role']` and redirects with `error=forbidden` on mismatch.
- `php/login.php`
  - Handles POSTed `email` and `password` from `sign_in_page.html`.
  - Looks up the user in the `users` table and verifies the password using `password_verify`.
  - On success, it:
    - Regenerates the session ID
    - Stores `user_id`, `email`, name components (`first_name`, `middle_name`, `last_name`), and a combined `name`
    - Sets `role_type` (and legacy `role`) and `logged_in = true`
    - Redirects admins to `admin_pages/admin_dashboard.php` and all others to `user_pages/user_dashboard.php`
  - On failure, it redirects back to `sign_in_page.html?error=invalid`.
- `php/register.php`
  - Handles account creation from `sign_up_page.html`.
  - Validates basic presence, email format, password confirmation, and minimum password length.
  - Enforces email uniqueness in the `users` table.
  - Inserts a new user with a bcrypt hash (`password_hash`) and a default `role_type` of `user`.
  - Redirects back to `sign_in_page.html?success=registered` or appropriate `error` codes on failure.

Error and status messages on the sign-in/sign-up screens are driven via query parameters (`?error=...`, `?success=...`) parsed by small inline `<script>` blocks in the HTML pages.

### Role-based page access

Admin and user dashboards follow a consistent pattern:
- Each protected page has a thin `.php` wrapper and a `.html` layout file in the same directory.
- For example, `admin_pages/admin_dashboard.php`:
  - Requires `php/auth_check.php` and calls `require_login('admin')`.
  - Includes `admin_dashboard.html`.
- Similarly, `user_pages/user_dashboard.php` calls `require_login('user')` and includes `user_dashboard.html`.

This pattern is repeated for other admin/user pages (employees, departments, assets, requests, audit, query, etc.), making it easy to add new protected pages:
- Create the HTML layout.
- Create a small PHP file that requires `auth_check.php`, calls `require_login('admin')` or `require_login('user')`, and then includes the HTML file.

### Frontend layouts

- All pages use inline `<style>` blocks and Google Fonts (`DM Sans`) with background images from `image/`.
- Layouts are entirely static at present: placeholders exist for content (e.g., employee cards, department summaries) but they do not yet pull dynamic data from the database.
- Navigation between admin/user subsections is handled using regular `<a href="...">` links to the corresponding `.php` wrappers.

### Database schema

`SIMS db.sql` defines the relational model for the system:
- `users` – application users with `role_type` enum (`'admin'` or `'user'`) and a 60-character `password` hash.
- `asset` – assets and quantities.
- `dept` – departments.
- `employee` – employees with a foreign key to `dept`.
- `request` – asset requests, linking `asset` and `employee` with quantity and date.
- `audit` – audit trail of user actions (links to `users`).
- `query` – simple query history table (links to `users`).

Currently, only the `users` table is actively used by the PHP code (login and registration). The other tables are defined but not yet wired into the UI logic in this repo version.

## Notes for future changes with Warp

- When adding any new DB-backed features (e.g., listing assets, creating requests), prefer to reuse `getPDO()` from `php/db_config.php` and keep all DB access functions in `php/` rather than inside the HTML files.
- Preserve the `.php` wrapper + `.html` layout pattern for protected pages so that access control remains centralized in `auth_check.php`.
- If you introduce Composer, PHPUnit, or linters, update this `WARP.md` with the new install/build/test commands so future Warp runs can use them directly.