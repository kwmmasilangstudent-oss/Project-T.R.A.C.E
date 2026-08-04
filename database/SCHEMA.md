# Project T.R.A.C.E. — Database Schema Documentation

## Overview
Database name: `trace_db`
Engine: MySQL
Character set: utf8mb4

## Entity Relationship Diagram (Textual)

```
roles (1) ----< (N) users
users (1) ----< (N) residents
users (1) ----< (N) documents
users (1) ----< (N) applications
users (1) ----< (N) activity_logs
users (1) ----< (N) audit_logs
users (1) ----< (N) notifications
users (1) ----< (N) session_logs
users (1) ----< (N) download_logs

officials (standalone)

residents (1) ----< (N) documents
residents (1) ----< (N) applications
residents (1) ----< (N) personal_information
residents (1) ----< (N) qr_codes
residents (1) ----< (N) verification_logs
residents (1) ----< (N) complaints
residents (1) ----< (N) appointments
residents (1) ----< (N) households

projects (1) ----< (N) project_budget
projects (1) ----< (N) expenses

announcements (1) ----< (N) announcement_reads
announcements (1) ----< (N) notifications

settings (standalone key-value store)
landing_content (standalone key-value store)
gallery (standalone)
events (standalone)
disaster_reports (standalone)
password_resets (standalone)
system_logs (standalone)
```

## Tables

### roles
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| name | VARCHAR(50) | Role name (admin, secretary, resident) |
| description | TEXT | Role description |
| created_at | TIMESTAMP | Record creation time |

### users
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| full_name | VARCHAR(150) | User full name |
| email | VARCHAR(150) | Login email |
| password_hash | VARCHAR(255) | Bcrypt hashed password |
| role | VARCHAR(50) | FK to roles.name |
| status | VARCHAR(20) | Account status (active, inactive) |
| created_at | TIMESTAMP | Record creation time |

### residents
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT FK | Linked user account |
| full_name | VARCHAR(150) | Resident name |
| birth_date | DATE | Date of birth |
| sex | VARCHAR(20) | Gender |
| address | TEXT | Residential address |
| contact_number | VARCHAR(50) | Phone number |
| household_number | VARCHAR(50) | Household ID |
| civil_status | VARCHAR(50) | Civil status |
| occupation | VARCHAR(100) | Occupation |
| education | VARCHAR(100) | Education level |
| emergency_contact | VARCHAR(150) | Emergency contact |
| created_at | TIMESTAMP | Record creation time |

### personal_information
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| resident_id | INT FK | Linked resident |
| civil_status | VARCHAR(50) | Civil status |
| citizenship | VARCHAR(100) | Citizenship |
| occupation | VARCHAR(100) | Occupation |
| education | VARCHAR(100) | Education level |
| created_at | TIMESTAMP | Record creation time |

### documents
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| resident_id | INT FK | Linked resident |
| document_type | VARCHAR(100) | Type of document |
| document_number | VARCHAR(100) | Auto-generated number |
| control_number | VARCHAR(100) | Control tracking number |
| purpose | TEXT | Purpose of document |
| status | VARCHAR(50) | draft, issued, archived |
| file_path | VARCHAR(255) | PDF file path |
| qr_code_path | VARCHAR(255) | QR image path |
| issued_by | INT FK | Issuing official |
| issued_at | DATETIME | Issue timestamp |
| created_at | TIMESTAMP | Record creation time |

### applications
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| resident_id | INT FK | Linked resident |
| application_type | VARCHAR(100) | Type of request |
| purpose | TEXT | Application purpose |
| priority | VARCHAR(20) | normal, high, urgent |
| status | VARCHAR(50) | Workflow status |
| remarks | TEXT | Review notes |
| reviewed_by | INT FK | Reviewer user ID |
| reviewed_at | DATETIME | Review timestamp |
| completed_at | DATETIME | Completion timestamp |
| created_at | TIMESTAMP | Record creation time |

### application_templates
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| name | VARCHAR(120) | Template name |
| document_type | VARCHAR(100) | Associated document type |
| description | TEXT | Template description |
| header_content | TEXT | Header HTML/text |
| body_content | TEXT | Body HTML/text |
| footer_content | TEXT | Footer HTML/text |
| background_path | VARCHAR(255) | Background image |
| watermark_text | VARCHAR(150) | Watermark text |
| signature_line_1 | VARCHAR(150) | First signatory |
| signature_line_2 | VARCHAR(150) | Second signatory |
| created_at | TIMESTAMP | Record creation time |

### projects
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| title | VARCHAR(200) | Project name |
| description | TEXT | Project description |
| objectives | TEXT | Project objectives |
| category | VARCHAR(100) | Infrastructure, Health, etc. |
| location | TEXT | Project location |
| status | VARCHAR(50) | planned, ongoing, completed |
| start_date | DATE | Start date |
| end_date | DATE | End date |
| progress_percent | INT | Completion percentage |
| approved_by | INT FK | Approving admin |
| approved_at | DATETIME | Approval timestamp |
| created_at | TIMESTAMP | Record creation time |

### project_budget
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| project_id | INT FK | Linked project |
| amount | DECIMAL(12,2) | Budget amount |
| source | VARCHAR(100) | Funding source |
| type | VARCHAR(50) | allocation, donation, grant |
| description | TEXT | Budget description |
| created_at | TIMESTAMP | Record creation time |

### expenses
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| project_id | INT FK | Linked project |
| amount | DECIMAL(12,2) | Expense amount |
| description | TEXT | Expense details |
| receipt_path | VARCHAR(255) | Receipt file path |
| created_at | TIMESTAMP | Record creation time |

### agenda
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| title | VARCHAR(200) | Agenda title |
| description | TEXT | Agenda description |
| meeting_type | VARCHAR(100) | Regular, Special, etc. |
| agenda_date | DATE | Meeting date |
| time_from | TIME | Start time |
| time_to | TIME | End time |
| location | TEXT | Meeting venue |
| attendees | TEXT | Attendee list |
| minutes | TEXT | Meeting minutes |
| action_items | TEXT | Action items |
| status | VARCHAR(50) | scheduled, ongoing, completed |
| created_at | TIMESTAMP | Record creation time |

### announcements
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| title | VARCHAR(200) | Announcement title |
| content | TEXT | Announcement content |
| audience | VARCHAR(50) | all, secretary, admin |
| type | VARCHAR(50) | announcement, news, event, emergency |
| priority | VARCHAR(20) | normal, high, urgent |
| attachment_path | VARCHAR(255) | File attachment path |
| created_at | TIMESTAMP | Record creation time |

### announcement_reads
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| announcement_id | INT FK | Linked announcement |
| resident_id | INT FK | Linked resident |
| is_read | TINYINT(1) | Read status |
| read_at | TIMESTAMP | Read timestamp |
| unique_announcement_resident | UNIQUE KEY | Prevents duplicate reads |

### notifications
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT FK | Recipient user |
| message | TEXT | Notification message |
| is_read | TINYINT(1) | Read status |
| link | TEXT | Action link |
| created_at | TIMESTAMP | Creation timestamp |

### activity_logs
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT FK | Actor user |
| action | VARCHAR(150) | Action performed |
| details | TEXT | Additional details |
| created_at | TIMESTAMP | Log timestamp |

### audit_logs
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT FK | Actor user |
| action | VARCHAR(150) | Action name |
| details | TEXT | Action details |
| ip_address | VARCHAR(45) | Client IP |
| user_agent | TEXT | Browser info |
| created_at | TIMESTAMP | Log timestamp |

### session_logs
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT FK | User ID |
| action | VARCHAR(50) | login, logout |
| ip_address | VARCHAR(45) | Client IP |
| user_agent | TEXT | Browser info |
| created_at | TIMESTAMP | Log timestamp |

### download_logs
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| user_id | INT FK | Downloading user |
| report_type | VARCHAR(100) | Type of report |
| ip_address | VARCHAR(45) | Client IP |
| created_at | TIMESTAMP | Download timestamp |

### settings
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| key_name | VARCHAR(100) | Setting key (UNIQUE) |
| key_value | TEXT | Setting value |
| created_at | TIMESTAMP | Record creation time |

### landing_content
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| section_name | VARCHAR(100) | Section identifier (UNIQUE) |
| content | TEXT | HTML/text content |
| created_at | TIMESTAMP | Record creation time |

### qr_codes
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| resident_id | INT FK | Linked resident |
| qr_value | VARCHAR(500) | QR payload data |
| qr_type | VARCHAR(50) | resident, document |
| created_at | TIMESTAMP | Generation timestamp |

### verification_logs
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| qr_id | INT FK | Linked QR code |
| resident_id | INT FK | Linked resident |
| verified_by | INT FK | Verifier user |
| ip_address | VARCHAR(45) | Client IP |
| created_at | TIMESTAMP | Verification timestamp |

### password_resets
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| email | VARCHAR(150) | User email |
| token | VARCHAR(255) | Reset token |
| expires_at | DATETIME | Expiration time |
| created_at | TIMESTAMP | Request timestamp |

### system_logs
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| message | TEXT | Log message |
| created_at | TIMESTAMP | Log timestamp |

### gallery
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| title | VARCHAR(200) | Image title |
| image_path | VARCHAR(255) | Image file path |
| created_at | TIMESTAMP | Upload timestamp |

### events
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| title | VARCHAR(200) | Event title |
| event_date | DATE | Event date |
| description | TEXT | Event details |
| created_at | TIMESTAMP | Insertion timestamp |

### disaster_reports
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| report_type | VARCHAR(100) | Type of disaster |
| description | TEXT | Report details |
| severity | VARCHAR(50) | Severity level |
| created_at | TIMESTAMP | Submission timestamp |

### complaints
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| resident_id | INT FK | Complaining resident |
| subject | VARCHAR(200) | Complaint subject |
| description | TEXT | Complaint details |
| status | VARCHAR(50) | open, closed, etc. |
| created_at | TIMESTAMP | Submission timestamp |

### appointments
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| resident_id | INT FK | Resident user |
| appointment_date | DATE | Appointment date |
| purpose | VARCHAR(200) | Purpose of visit |
| status | VARCHAR(50) | pending, approved, rejected |
| created_at | TIMESTAMP | Booking timestamp |

### households
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| household_number | VARCHAR(50) | Household ID |
| address | TEXT | Household address |
| head_name | VARCHAR(150) | Household head |
| created_at | TIMESTAMP | Record creation time |

### officials
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| full_name | VARCHAR(150) | Official name |
| position | VARCHAR(100) | Official position |
| contact_number | VARCHAR(50) | Contact info |
| photo_path | VARCHAR(255) | Photo file path |
| created_at | TIMESTAMP | Record creation time |

## Seed Data

- 3 roles: admin, secretary, resident
- 3 default users (admin@trace.test, secretary@trace.test, resident@trace.test)
- Default landing content sections: hero, mission, vision, objectives, history, services, contact, footer
- Default settings: barangay_name, barangay_address, maintenance_mode, theme, email_notifications, sms_notifications, barangay_logo, officials_signature

## Indexes & Constraints

- Primary keys on all tables (`id`)
- Foreign keys enforce referential integrity
- Unique constraints on:
  - users.email
  - roles.name
  - landing_content.section_name
  - settings.key_name
  - announcement_reads (announcement_id, resident_id) composite key
