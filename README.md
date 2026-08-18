# Project T.R.A.C.E.

Tracking, Records, Administration, and Community Engagement — A Barangay Management System.

## Overview

Project T.R.A.C.E. is a web-based barangay management system built with PHP and MySQL. It provides role-based dashboards for Administrators, Secretaries, and Residents to streamline record-keeping, service delivery, and community engagement.

## User Roles

### Admin
- Dashboard with analytics, charts, and system health metrics
- User management (create, edit, reset passwords, delete)
- Resident management and bulk CSV import
- Resident profiling with comprehensive tabbed forms (PCN masking, age calculation, conditional PWD fields)
- QR code and ID card generation
- Document template management
- Project and budget tracking
- Agenda and event management
- Application workflow (review, approve, reject)
- Appointment scheduling and management
- Announcements and landing page CMS
- CCTV camera management
- Reports with CSV export
- Audit logs, scan logs, and attendance tracking
- Database backup and restore
- System settings and maintenance mode

### Secretary
- Dashboard with key metrics and recent activity
- Resident management
- Resident profiling
- QR code generation
- Document generation and printing
- Application workflow
- Appointment management
- Project and budget viewing
- Agenda management
- QR scanning and attendance
- Announcements
- Reports with CSV export
- Scan logs
- Landing page CMS
- Notifications

### Resident
- Personal dashboard with stats and quick actions
- Profile management and password change
- Submit service requests (clearance, residency, indigency, business, permits, etc.)
- Book appointments
- View documents and announcements
- Download personal QR code
- Theme preference (light/dark/system)

## Technology Stack
- PHP 8+
- MySQL / MariaDB
- HTML, CSS, JavaScript
- Chart.js for analytics
- QR code generation library

## Setup
1. Import the SQL dump from `database.sql`
2. Configure database credentials in `config/database.php`
3. Ensure write permissions on `assets/uploads/`
4. Access the application via web server

## Default Credentials
See `README_CREDENTIALS.md` or contact the system administrator.

## License
Internal use — Barangay IT Project
