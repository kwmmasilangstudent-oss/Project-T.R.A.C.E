# Requirements Document - Project T.R.A.C.E.

## Project Overview
Project T.R.A.C.E. (Transparency, Resilience, Accountability, Community Verification, Economic Growth) is an integrated web-based management system for Barangay Tumalaytay.

## Phase 1: Planning

### 1.1 Identified Users

| Role | Description | Access Level |
|------|-------------|--------------|
| Administrator | Full system control | All modules |
| Barangay Secretary | Day-to-day operations | Documents, Residents, Projects, Budget, Agenda, Announcements |
| Resident | Service consumer | Profile, QR, Requests, Announcements, Notifications |

### 1.2 Functional Requirements

#### Authentication & Authorization
- Secure login with email and password
- Password hashing using PHP password_hash()
- Session-based authentication
- Role-Based Access Control (RBAC) with three roles
- Protected routes requiring authentication

#### Resident Management
- Record resident personal information
- Track household data
- Manage civil status, occupation, education
- Emergency contact management

#### QR Identification System
- Generate unique QR codes for each resident
- QR payload contains resident ID and verification link
- Download and print QR codes
- QR verification logs with timestamp and IP

#### Document Management
- Generate 10 document types:
  - Barangay Clearance
  - Certificate of Residency
  - Certificate of Indigency
  - Business Clearance
  - First Time Job Seeker
  - Good Moral
  - Solo Parent Certificate
  - Low Income Certificate
  - Certification
  - Custom Certificate
- Auto document and control numbering
- QR verification on documents
- Preview, print, and archive documents
- Document search and history

#### Application Request System
- Residents submit service requests
- Workflow: Submitted → Pending → Under Review → Approved → Ready for Pickup → Completed
- Rejected status option
- Priority levels (Normal, High, Urgent)
- Remarks and tracking

#### Project Management
- Create projects with objectives, timeline, category, location
- Track progress percentage
- Budget allocation per project
- Admin approval for projects
- Completion tracking

#### Budget Management
- Budget allocation per project
- Expense recording with descriptions
- Remaining balance calculations
- Financial summary reports
- CSV export

#### Agenda Management
- Meeting scheduling with date, time, location
- Attendee management
- Meeting minutes recording
- Action items with deadlines
- Status tracking (Scheduled, Ongoing, Completed, Cancelled)

#### Announcement System
- 7 announcement types: Announcement, News, Event, Emergency, Program, Meeting, Maintenance
- Priority levels (Normal, High, Urgent)
- Audience targeting (All, Secretary, Admin)
- File attachments
- Read tracking per resident
- Archive and delete
- Search and filter

#### Reports Module
- Resident Reports
- Project Reports
- Financial Reports
- Certificate Reports
- Application Reports
- Attendance Reports
- Announcement Reports
- Activity Reports
- Audit Reports
- CSV export

#### Admin Dashboard
- Real-time system analytics
- Sex and age distribution
- Monthly, daily, yearly application graphs
- Total residents, officials, projects, budget
- QR scans tracking
- Certificates issued

#### Audit & Security
- Audit logging for all actions
- IP address and user agent tracking
- Login/logout tracking
- Activity logs
- Backup and restore functionality

#### Notifications
- Real-time dashboard notifications
- Unread counter
- Mark as read functionality
- Action links

#### Landing Website
- Dynamic content managed by admin
- Hero section, mission, vision, objectives
- Services, gallery, announcements
- Officials display
- Contact information

### 1.3 Non-Functional Requirements

- **Performance**: Page load under 3 seconds on standard network
- **Security**: Prepared statements, password hashing, session authentication, CSRF-ready
- **Usability**: Glassmorphism UI, Bootstrap 5 responsive grid, intuitive navigation
- **Reliability**: Session-based auth, error handling with try-catch
- **Maintainability**: Modular PHP, reusable components, clean code structure

### 1.4 Security Requirements

- All database queries use prepared statements
- Password hashing with PHP password_hash()
- Session-based authentication with role checks
- Input sanitization with htmlspecialchars()
- File upload validation
- IP address logging for audit trail
- Maintenance mode for admin-only access

### 1.5 QR System Requirements

- Unique QR code per resident
- QR payload: resident:ID:encoded_name
- Document QR payload: document:number:resident_id:timestamp
- PNG image generation
- File storage in assets/uploads/qr/
- Verification logging

### 1.6 Document Templates

- Editable template fields:
  - Header content
  - Body content
  - Footer content
  - Background image path
  - Watermark text
  - Signature lines (2)
- Template reuse across document types

### 1.7 Printing

- Print-ready document layouts
- Browser print capabilities
- PDF download preparation (framework in place)

### 1.8 Role Permissions

| Module | Admin | Secretary | Resident |
|--------|-------|-----------|----------|
| Dashboard | Full | Limited | Own |
| Users | Full | None | None |
| Officials | Full | View | View |
| Residents | Full | Full | Own |
| Documents | Full | Full | None |
| Requests | Full | Full | Own |
| Projects | Full | Full | View |
| Budget | Full | Full | View |
| Agenda | Full | Full | View |
| Announcements | Full | Full | View |
| Reports | Full | Full | None |
| Settings | Full | None | Own |
| Logs | Full | None | None |
| Backup | Full | None | None |

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5, Bootstrap Icons
- **Backend**: PHP 8 (Procedural)
- **Database**: MySQL
- **Design**: Glassmorphism UI with custom CSS
- **QR**: PHP GD library for PNG generation

## File Structure

```
trace/
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── uploads/
├── config/
│   ├── constants.php
│   ├── database.php
│   └── session.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   ├── navbar.php
│   ├── sidebar.php
│   ├── footer.php
│   └── qr.php
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── officials.php
│   ├── residents.php
│   ├── announcements.php
│   ├── projects.php
│   ├── budget.php
│   ├── agenda.php
│   ├── templates.php
│   ├── reports.php
│   ├── applications.php
│   ├── logs.php
│   ├── backup.php
│   └── settings.php
├── secretary/
│   ├── dashboard.php
│   ├── residents.php
│   ├── qr.php
│   ├── documents.php
│   ├── requests.php
│   ├── projects.php
│   ├── budget.php
│   ├── agenda.php
│   ├── announcements.php
│   └── reports.php
├── resident/
│   ├── dashboard.php
│   ├── profile.php
│   ├── qr.php
│   ├── requests.php
│   ├── announcements.php
│   ├── notifications.php
│   ├── appointments.php
│   └── settings.php
├── landing/
│   ├── home.php
│   ├── about.php
│   ├── services.php
│   ├── officials.php
│   ├── announcements.php
│   ├── events.php
│   └── contact.php
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   └── logout.php
├── database/
│   └── trace.sql
├── index.php
└── plan.docs
```
