CREATE DATABASE IF NOT EXISTS trace_db;
USE trace_db;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'resident',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    security_question VARCHAR(255) NULL,
    security_answer VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS officials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    position VARCHAR(100) NOT NULL,
    contact_number VARCHAR(50) NULL,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    full_name VARCHAR(150) NOT NULL,
    birth_date DATE NULL,
    sex VARCHAR(20) NULL,
    address TEXT NULL,
    contact_number VARCHAR(50) NULL,
    household_number VARCHAR(50) NULL,
    civil_status VARCHAR(50) NULL,
    occupation VARCHAR(100) NULL,
    education VARCHAR(100) NULL,
    emergency_contact VARCHAR(150) NULL,
    resident_type VARCHAR(50) NULL DEFAULT 'regular',
    qr_code_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS personal_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    civil_status VARCHAR(50) NULL,
    citizenship VARCHAR(100) NULL,
    occupation VARCHAR(100) NULL,
    education VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_number VARCHAR(100) NOT NULL,
    control_number VARCHAR(100) NOT NULL,
    purpose TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    file_path VARCHAR(255) NULL,
    qr_code_path VARCHAR(255) NULL,
    issued_by INT NULL,
    issued_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    application_type VARCHAR(100) NOT NULL,
    purpose TEXT NULL,
    priority VARCHAR(20) DEFAULT 'normal',
    status VARCHAR(50) NOT NULL DEFAULT 'submitted',
    remarks TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS application_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    header_content TEXT NULL,
    body_content TEXT NULL,
    footer_content TEXT NULL,
    background_path VARCHAR(255) NULL,
    watermark_text VARCHAR(150) NULL,
    signature_line_1 VARCHAR(150) NULL,
    signature_line_2 VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    objectives TEXT NULL,
    category VARCHAR(100) NULL,
    location TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'planned',
    start_date DATE NULL,
    end_date DATE NULL,
    progress_percent INT DEFAULT 0,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS project_budget (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    source VARCHAR(100) NULL,
    type VARCHAR(50) DEFAULT 'allocation',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    description TEXT NULL,
    receipt_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    meeting_type VARCHAR(100) NULL,
    agenda_date DATE NULL,
    time_from TIME NULL,
    time_to TIME NULL,
    location TEXT NULL,
    attendees TEXT NULL,
    minutes TEXT NULL,
    action_items TEXT NULL,
    status VARCHAR(50) DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    audience VARCHAR(50) NULL,
    type VARCHAR(50) DEFAULT 'general',
    priority VARCHAR(20) DEFAULT 'normal',
    is_pinned TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    expires_at DATETIME NULL,
    attachment_path VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    resident_id INT NOT NULL,
    is_read TINYINT(1) DEFAULT 1,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_announcement_resident (announcement_id, resident_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(150) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(150) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS session_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS download_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    key_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS landing_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL UNIQUE,
    content TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS landing_officials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    official_name VARCHAR(150) NOT NULL,
    position_title VARCHAR(150) NOT NULL,
    position_label VARCHAR(100) NULL,
    tier VARCHAR(50) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    photo_path VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS landing_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_label VARCHAR(100) NOT NULL,
    stat_value INT DEFAULT 0,
    stat_suffix VARCHAR(20) NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    qr_value VARCHAR(500) NOT NULL,
    qr_type VARCHAR(50) DEFAULT 'resident',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id INT NOT NULL,
    resident_id INT NOT NULL,
    verified_by INT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    event_date DATE NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS disaster_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    severity VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NULL,
    status VARCHAR(50) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    purpose VARCHAR(200) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_number VARCHAR(50) NULL,
    address TEXT NULL,
    head_name VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO roles (name, description) VALUES
('admin', 'Administrator'),
('secretary', 'Barangay Secretary'),
('resident', 'Resident');

INSERT INTO users (full_name, email, password_hash, role, status) VALUES
('Administrator', 'admin@trace.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('Secretary', 'secretary@trace.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'secretary', 'active'),
('Resident User', 'resident@trace.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'active');

INSERT INTO landing_content (section_name, content) VALUES
('hero', 'A transparent, resilient, and accountable barangay system for every resident.'),
('mission', 'To deliver responsive public service with integrity and accountability.'),
('vision', 'A digitally connected barangay that promotes transparency and civic participation.'),
('objectives', 'To improve accessibility to services, records, and public information.'),
('history', 'The barangay continues to strengthen governance through innovation and community engagement.'),
('services', 'Residents can request documents, view announcements, and verify their identity through QR access.'),
('contact', 'Office: Barangay Hall, Tumalaytay. Contact the barangay office for verification and support.'),
('footer', 'Thank you for partnering with the barangay in building a stronger community.');

INSERT INTO settings (key_name, key_value) VALUES
('barangay_name', 'Barangay Tumalaytay'),
('barangay_address', 'Barangay Hall, Tumalaytay'),
('maintenance_mode', '0'),
('theme', 'light'),
('email_notifications', '1'),
('sms_notifications', '0'),
('barangay_logo', ''),
('officials_signature', '');
