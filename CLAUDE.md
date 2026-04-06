# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Setup

This is a PHP/MySQL web application that runs on a local server (XAMPP/MAMP).

1. Copy `config/database.example.php` to `config/database.php` and fill in your credentials
2. Import `config/database.sql` into a MySQL database named `rental_inventory`
3. Place the project under `htdocs/rental-inventory` and start Apache + MySQL in XAMPP/MAMP
4. Visit `http://localhost/rental-inventory`

**Demo credentials** (all use password `password`):
- Admin: `admin@university.edu`
- Chair: `chair@university.edu`
- Instructor: `instructor@university.edu`
- Student: `student@university.edu`

## Architecture

**Stack:** PHP (no framework), MySQL via MySQLi, Bootstrap 5 frontend, vanilla JS.

**Database connection:** Every page that needs DB access calls `getConnection()` from `config/database.php`, which returns a raw MySQLi connection. Connections are opened and closed per-request inside each function — there is no connection pooling or ORM.

**Auth & sessions:** `includes/auth.php` manages login/logout and session state (`$_SESSION['user_id']`, `$_SESSION['role']`, etc.). Every protected page calls `requireLogin()` or `requireRole(['admin'])` at the top. The `sanitize()` function in auth.php wraps `htmlspecialchars` for output escaping.

**Role routing:** After login, users are redirected to their role-specific dashboard. All pages in `admin/`, `instructor/`, `chair/`, and `student/` enforce role access via `requireRole()`. Pages in `guest/` are publicly accessible.

**Approval workflow:** Rental requests go through a two-step approval: instructor first, then chair. `includes/functions.php::updateOverallStatus()` recalculates `overall_status` on the `rental_requests` table whenever either party acts. Once both approve, it triggers `sendApprovalEmail()`. Admin then physically checks equipment in/out via `admin/checkout.php`.

**Email:** Uses PHP's native `mail()`. Every send attempt is logged to the `email_logs` table with a `sent`/`failed` status. Constants `MAIL_FROM` and `MAIL_FROM_NAME` are defined in `config/database.php`.

**File uploads:** Equipment images go to `/uploads/`. The `uploadImage()` helper in `functions.php` handles validation (JPEG/PNG/GIF/WebP, 5 MB max) and generates unique filenames with `uniqid()`.

**Key shared helpers** (`includes/functions.php`):
- `getStatusBadge($status)` — returns Bootstrap badge HTML for request/equipment statuses
- `formatDate()` / `formatDateTime()` — standard display formatting
- `getPagination()` — calculates offset/page metadata for paginated queries
- `setFlashMessage()` / `getFlashMessage()` — session-based flash messages

**Includes layout:** Pages include `includes/header.php` (nav + session check) and `includes/footer.php` (Bootstrap JS) manually — there is no templating engine.
