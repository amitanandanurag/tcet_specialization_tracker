# TCET Specialization Tracker

A PHP + MySQL web application for managing students and role-based users (admin, coordinator, mentor, user) in a specialization tracking workflow.

## Features

- Student admission, listing, update, and delete flows
- Role-specific CRUD pages for:
  - Admin
  - Coordinator
  - Mentor
  - User
- AJAX info endpoints for role and student detail views
- Login/logout module with session-based access flow
- PDF-related utilities under `pdf/`
- Organized static assets (Bootstrap, plugins, custom CSS/JS)

## Tech Stack

- PHP (classic server-rendered pages)
- MySQL / MariaDB
- XAMPP (Apache + MySQL)
- Bootstrap, jQuery, and related frontend plugins

## Project Structure (High Level)

- `index.php` - Root entry point
- `admin/` - Main application modules (role CRUD, student pages, shared assets)
- `database/` - Database connection and SQL dump
- `login/` - Authentication pages and scripts

## Prerequisites

- XAMPP installed
- Apache and MySQL services running
- PHP 7.x+ (recommended to match your XAMPP version)

## Local Setup (XAMPP)

1. Place this project folder at:
   - `C:\xampp\htdocs\st`
2. Start Apache and MySQL from XAMPP Control Panel.
3. Create a database in phpMyAdmin.
4. Import SQL dump:
   - `database/tcet_st.sql`
5. Update DB credentials if required in:
   - `database/db_connect.php`
   - `login/db_connect.php`
6. Open in browser:
   - `http://localhost/st/`
   - or login module: `http://localhost/st/login/`

## Default Entry Points

- Main app: `index.php`
- Admin area pages: `admin/index.php` and related module pages
- Login: `login/index.php`

## Notes

- This project contains legacy-style PHP pages (non-framework).
- Ensure write permissions are available for upload folders such as:
  - `documents/`
  - `emp-photos/`
  - `student_photo/`
- Keep database credentials private in deployment environments.

## Troubleshooting

- If you see DB connection errors, verify host/user/password/database in both DB config files.
- If pages load without styles/scripts, verify Apache document root and folder path under `htdocs`.
- If login redirects fail, check session settings and PHP error logs in XAMPP.

## License

No explicit license file is included in this repository.
Add a `LICENSE` file if you plan to distribute this project.
