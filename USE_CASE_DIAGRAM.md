# Project T.R.A.C.E. — Use Case Diagram

## System Overview
Project T.R.A.C.E. (Tracking, Records, Administration, and Community Engagement) is a Barangay Management System with three distinct user roles: **Admin**, **Secretary**, and **Resident**.

## Use Case Diagram

```mermaid
usecaseDiagram
    title Project T.R.A.C.E. — Use Case Diagram

    actor Admin
    actor Secretary
    actor Resident

    package "User Management" {
        Admin --> Manage_Users
        Admin --> View_Audit_Logs
        Admin --> Backup_Restore_Database
        Admin --> Toggle_Maintenance_Mode
        Admin --> Manage_Settings
    }

    package "Resident & Records" {
        Admin --> Manage_Residents
        Admin --> Resident_Profiling
        Admin --> Generate_QR_Codes
        Admin --> Generate_ID_Cards
        Admin --> View_CCTV_Cameras
        Secretary --> Manage_Residents
        Secretary --> Resident_Profiling
        Secretary --> Generate_QR_Codes
    }

    package "Services & Applications" {
        Admin --> Review_Applications
        Admin --> Manage_Templates
        Admin --> Manage_Documents
        Admin --> Manage_Appointments
        Secretary --> Review_Applications
        Secretary --> Manage_Templates
        Secretary --> Generate_Documents
        Secretary --> Manage_Appointments
        Secretary --> Scan_QR_Attendance
        Resident --> Submit_Application
        Resident --> View_My_Documents
        Resident --> Book_Appointment
        Resident --> View_My_QR
    }

    package "Operations" {
        Admin --> Manage_Projects
        Admin --> Manage_Budget
        Admin --> Manage_Agenda
        Admin --> Manage_Announcements
        Admin --> Manage_Landing_Content
        Admin --> View_Reports
        Secretary --> Manage_Projects
        Secretary --> Manage_Budget
        Secretary --> Manage_Agenda
        Secretary --> Manage_Announcements
        Secretary --> Manage_Landing_Content
        Secretary --> View_Reports
        Secretary --> View_Scan_Logs
    }

    package "Communication" {
        Admin --> View_Notifications
        Admin --> Manage_Officials
        Secretary --> View_Notifications
        Secretary --> Manage_Officials
        Resident --> View_Announcements
        Resident --> View_Notifications
    }

    package "Account & Preferences" {
        Admin --> Manage_Own_Profile
        Admin --> Change_Password
        Secretary --> Manage_Own_Profile
        Secretary --> Change_Password
        Resident --> Manage_Own_Profile
        Resident --> Change_Password
        Resident --> Set_Theme_Preference
    }
```

## Actor Descriptions

| Actor | Description |
|---|---|
| **Admin** | Full system access with elevated privileges for system configuration, user management, surveillance, and backups. |
| **Secretary** | Operational access for day-to-day barangay records, document generation, appointment management, and reporting. |
| **Resident** | Self-service access for personal records, service requests, appointments, and community updates. |
