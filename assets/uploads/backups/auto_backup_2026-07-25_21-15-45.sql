

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(150) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `agenda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `meeting_type` varchar(100) DEFAULT NULL,
  `agenda_date` date DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `location` text DEFAULT NULL,
  `attendees` text DEFAULT NULL,
  `minutes` text DEFAULT NULL,
  `action_items` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_type` varchar(50) DEFAULT 'meeting',
  `is_scannable` tinyint(1) DEFAULT 0,
  `qr_session_token` varchar(255) DEFAULT NULL,
  `scan_mode` enum('open','closed','invited') DEFAULT 'open',
  `expected_attendees` int(11) DEFAULT 0,
  `checkin_count` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `agenda` VALUES ('1', 'karaon sa baryo', 'gnzdhzdh', 'Special', '2026-07-20', '07:30:00', '12:00:00', 'dapdap', '', 'dgsasgzfgzdfzdf', '', 'ongoing', '2026-07-20 23:44:28', 'general', '1', NULL, 'open', '63', '0', '2026-07-20 23:44:28');
INSERT INTO `agenda` VALUES ('2', 'karaon sa baryo', 'gnzdhzdh', 'Special', '2026-07-20', '07:30:00', '12:00:00', 'dapdap', '', 'dgsasgzfgzdfzdf', '', 'ongoing', '2026-07-20 23:44:44', 'general', '1', NULL, 'open', '63', '0', '2026-07-20 23:44:44');
INSERT INTO `agenda` VALUES ('3', 'utro', '', 'Regular', '2026-07-21', NULL, NULL, 'dapdap', '', '', '', 'ongoing', '2026-07-21 00:17:23', 'general', '1', NULL, 'open', '8', '2', '2026-07-21 01:40:53');
INSERT INTO `agenda` VALUES ('4', 'kadi lang', 'asfargargwr', 'Regular', '2026-07-25', '12:00:00', '03:00:00', 'dapdap', '', '', '', 'ongoing', '2026-07-25 18:27:00', 'meeting', '0', NULL, 'open', '0', '0', '2026-07-25 18:27:00');
INSERT INTO `agenda` VALUES ('5', 'kadi lang', 'asfargargwr', 'Regular', '2026-07-25', '12:00:00', '03:00:00', 'dapdap', '', '', '', 'ongoing', '2026-07-25 18:28:32', 'meeting', '0', NULL, 'open', '0', '0', '2026-07-25 18:28:32');


CREATE TABLE `agenda_invitees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agenda_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_agenda_resident` (`agenda_id`,`resident_id`),
  KEY `idx_agenda_invitees_resident` (`resident_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `agenda_scan_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agenda_id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `scan_result` enum('success','not_found','inactive','expired','error') NOT NULL,
  `scanned_by_user_id` int(11) NOT NULL,
  `scanned_by_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_agenda_resident_scan` (`agenda_id`,`resident_id`),
  KEY `idx_agenda_scans_agenda_id` (`agenda_id`),
  KEY `idx_agenda_scans_scanned_at` (`scanned_at`),
  KEY `idx_agenda_scans_resident_id` (`resident_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `agenda_scan_logs` VALUES ('1', '2', NULL, 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:13:24');
INSERT INTO `agenda_scan_logs` VALUES ('2', '1', NULL, 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:15:18');
INSERT INTO `agenda_scan_logs` VALUES ('3', '3', NULL, 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:18:44');
INSERT INTO `agenda_scan_logs` VALUES ('4', '3', NULL, 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:20:07');
INSERT INTO `agenda_scan_logs` VALUES ('5', '3', NULL, 'not_found', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:28:20');
INSERT INTO `agenda_scan_logs` VALUES ('6', '3', '37', 'success', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 01:40:13');
INSERT INTO `agenda_scan_logs` VALUES ('7', '3', '36', 'success', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 01:40:53');
INSERT INTO `agenda_scan_logs` VALUES ('8', '3', NULL, 'not_found', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 01:41:39');


CREATE TABLE `announcement_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 1,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_announcement_resident` (`announcement_id`,`resident_id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `announcement_reads` VALUES ('1', '1', '1', '0', '2026-07-03 02:04:29');
INSERT INTO `announcement_reads` VALUES ('2', '2', '1', '0', '2026-07-03 02:04:55');
INSERT INTO `announcement_reads` VALUES ('3', '3', '1', '0', '2026-07-03 02:05:08');
INSERT INTO `announcement_reads` VALUES ('12', '11', '2', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('13', '11', '4', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('14', '11', '6', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('15', '11', '12', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('16', '11', '13', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('17', '11', '14', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('18', '11', '15', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('19', '11', '16', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('20', '11', '17', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('21', '11', '20', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('22', '11', '21', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('23', '11', '22', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('24', '11', '23', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('25', '11', '24', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('26', '11', '26', '0', '2026-07-15 23:17:51');
INSERT INTO `announcement_reads` VALUES ('53', '17', '4', '0', '2026-07-26 03:00:14');
INSERT INTO `announcement_reads` VALUES ('54', '17', '34', '0', '2026-07-26 03:00:14');
INSERT INTO `announcement_reads` VALUES ('55', '17', '36', '0', '2026-07-26 03:00:14');
INSERT INTO `announcement_reads` VALUES ('56', '17', '37', '0', '2026-07-26 03:00:14');
INSERT INTO `announcement_reads` VALUES ('57', '17', '38', '0', '2026-07-26 03:00:14');
INSERT INTO `announcement_reads` VALUES ('58', '17', '39', '0', '2026-07-26 03:00:14');


CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `audience` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'announcement',
  `priority` varchar(20) DEFAULT 'normal',
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `announcements` VALUES ('6', 'Typhoon Preparedness Advisory', 'With the onset of the typhoon season, all residents are reminded to prepare emergency kits and identify evacuation routes.', NULL, 'emergency', 'high', '1', '0', NULL, NULL, '2025-06-20 07:00:00', NULL, '2026-07-19 00:16:07');
INSERT INTO `announcements` VALUES ('11', 'WeGSdVsdgV', 'DSDBZFHDZHZTH', 'all', 'general', 'urgent', '1', '0', '2026-07-15 23:17:00', NULL, '2026-07-15 23:17:51', NULL, '2026-07-16 01:16:19');
INSERT INTO `announcements` VALUES ('12', 'para kay secretary', 'hayy naga gana?', 'secretary', 'maintenance', 'high', '1', '0', '2026-07-18 22:08:00', NULL, '2026-07-18 22:08:45', NULL, '2026-07-25 19:04:33');
INSERT INTO `announcements` VALUES ('15', 'zrgbzdfb', 'ahahhaahahha', 'all', 'event', 'normal', '1', '0', '2026-07-22 15:53:00', NULL, '2026-07-22 15:53:53', '1', '2026-07-24 11:22:22');
INSERT INTO `announcements` VALUES ('16', 'zrgbzdfb', 'ahahhaahahha', 'all', 'event', 'normal', '1', '0', '2026-07-22 15:53:00', NULL, '2026-07-22 17:07:23', '1', '2026-07-24 11:22:02');
INSERT INTO `announcements` VALUES ('17', 'ang lahat ay may ayuda', 'magdali kay limited lang', 'all', 'emergency', 'urgent', '0', '1', '2026-07-26 02:59:00', NULL, '2026-07-26 03:00:14', '2', '2026-07-26 03:00:14');


CREATE TABLE `application_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `header_content` text DEFAULT NULL,
  `body_content` text DEFAULT NULL,
  `footer_content` text DEFAULT NULL,
  `background_path` varchar(255) DEFAULT NULL,
  `watermark_text` varchar(150) DEFAULT NULL,
  `signature_line_1` varchar(150) DEFAULT NULL,
  `signature_line_2` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `application_type` varchar(100) NOT NULL,
  `purpose` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `applications` VALUES ('2', '34', 'Certificate of Indigency', '', 'high', 'completed', 'QWFeqf', '1', '2026-07-21 04:06:10', NULL, '2026-07-17 22:29:44');
INSERT INTO `applications` VALUES ('3', '35', 'Permit', '', 'high', 'completed', '', '2', '2026-07-25 04:11:44', NULL, '2026-07-21 04:22:40');
INSERT INTO `applications` VALUES ('4', '35', 'Barangay Clearance', 'jobseeker', 'urgent', 'rejected', 'pang gatas', '2', '2026-07-20 22:50:24', NULL, '2026-07-21 04:25:23');
INSERT INTO `applications` VALUES ('5', '35', 'Certificate of Residency', '', 'urgent', 'completed', '', '1', '2026-07-24 04:49:37', NULL, '2026-07-21 04:27:34');
INSERT INTO `applications` VALUES ('6', '38', 'Barangay Clearance', 'aegWARGAER', 'urgent', 'completed', 'drhstrhrsr', '1', '2026-07-24 10:49:33', NULL, '2026-07-24 08:19:19');
INSERT INTO `applications` VALUES ('7', '36', 'Business Clearance', 'employment', 'urgent', 'submitted', '', NULL, NULL, NULL, '2026-07-26 02:49:57');
INSERT INTO `applications` VALUES ('8', '37', 'Permit', 'baduya shop', 'normal', 'submitted', 'para may profit daily', NULL, NULL, NULL, '2026-07-26 03:02:10');


CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `purpose` varchar(200) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `appointments` VALUES ('1', '34', '2026-07-17', 'ghxmghcghgh', 'pending', '2026-07-17 22:43:00');
INSERT INTO `appointments` VALUES ('2', '35', '2026-07-18', 'wala testing lang ine', 'pending', '2026-07-18 22:18:16');


CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(150) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=306 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` VALUES ('1', '1', 'create_user', 'Created user: tsss (secretary)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-03 01:27:59');
INSERT INTO `audit_logs` VALUES ('2', '1', 'create_resident', 'Created resident: dasd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-03 01:38:20');
INSERT INTO `audit_logs` VALUES ('3', '2', 'create_announcement', 'Created announcement: hheee', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-03 02:04:29');
INSERT INTO `audit_logs` VALUES ('4', '2', 'create_announcement', 'Created announcement: hheee', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-03 02:04:55');
INSERT INTO `audit_logs` VALUES ('5', '2', 'create_announcement', 'Created announcement: hheee', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-03 02:05:08');
INSERT INTO `audit_logs` VALUES ('6', '3', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-03 02:20:53');
INSERT INTO `audit_logs` VALUES ('7', '1', 'create_project', 'Created project: wefWE;F', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 18:39:13');
INSERT INTO `audit_logs` VALUES ('8', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 18:47:14');
INSERT INTO `audit_logs` VALUES ('9', '1', 'create_resident', 'Created resident: arsiel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 19:06:12');
INSERT INTO `audit_logs` VALUES ('10', '1', 'update_project', 'Updated project ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 19:07:24');
INSERT INTO `audit_logs` VALUES ('11', '1', 'delete_budget', 'Deleted budget entry ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 19:07:38');
INSERT INTO `audit_logs` VALUES ('12', '1', 'delete_project', 'Deleted project ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 22:05:32');
INSERT INTO `audit_logs` VALUES ('13', '1', 'create_official', 'Created official: pogi (president)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 22:28:59');
INSERT INTO `audit_logs` VALUES ('14', '1', 'create_official', 'Created official: pogi (president)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 22:29:52');
INSERT INTO `audit_logs` VALUES ('15', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-08 22:47:03');
INSERT INTO `audit_logs` VALUES ('16', '1', 'upload_hero_image', 'Uploaded hero background image', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 00:49:34');
INSERT INTO `audit_logs` VALUES ('17', '1', 'upload_hero_image', 'Uploaded hero background image', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 01:11:23');
INSERT INTO `audit_logs` VALUES ('18', '1', 'update_landing_content', 'Updated landing page text content', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 01:33:30');
INSERT INTO `audit_logs` VALUES ('19', '1', 'create_announcement', 'Created announcement: fds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 01:57:19');
INSERT INTO `audit_logs` VALUES ('20', '1', 'create_announcement', 'Created announcement: fds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 01:57:47');
INSERT INTO `audit_logs` VALUES ('21', '1', 'create_announcement', 'Created announcement: fds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 01:58:13');
INSERT INTO `audit_logs` VALUES ('22', '1', 'create_announcement', 'Created announcement: fds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 01:58:23');
INSERT INTO `audit_logs` VALUES ('23', '1', 'create_official', 'Created official: dasdas (dasdas)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-07-09 02:03:42');
INSERT INTO `audit_logs` VALUES ('24', '1', 'export_report', 'Exported audit report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 19:39:25');
INSERT INTO `audit_logs` VALUES ('25', '1', 'export_report', 'Exported activity report', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 19:40:01');
INSERT INTO `audit_logs` VALUES ('26', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 19:46:31');
INSERT INTO `audit_logs` VALUES ('27', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 19:47:34');
INSERT INTO `audit_logs` VALUES ('28', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 19:47:35');
INSERT INTO `audit_logs` VALUES ('29', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 19:58:24');
INSERT INTO `audit_logs` VALUES ('30', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-11 20:03:11');
INSERT INTO `audit_logs` VALUES ('31', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:28:38');
INSERT INTO `audit_logs` VALUES ('32', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:32:19');
INSERT INTO `audit_logs` VALUES ('33', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:32:19');
INSERT INTO `audit_logs` VALUES ('34', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:32:20');
INSERT INTO `audit_logs` VALUES ('35', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:32:20');
INSERT INTO `audit_logs` VALUES ('36', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:32:24');
INSERT INTO `audit_logs` VALUES ('37', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-11 20:32:53');
INSERT INTO `audit_logs` VALUES ('38', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-13 20:10:35');
INSERT INTO `audit_logs` VALUES ('39', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 20:11:01');
INSERT INTO `audit_logs` VALUES ('40', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 20:22:09');
INSERT INTO `audit_logs` VALUES ('41', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 21:46:55');
INSERT INTO `audit_logs` VALUES ('42', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 21:47:43');
INSERT INTO `audit_logs` VALUES ('43', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:03:04');
INSERT INTO `audit_logs` VALUES ('44', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:04:36');
INSERT INTO `audit_logs` VALUES ('45', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:04:36');
INSERT INTO `audit_logs` VALUES ('46', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:04:36');
INSERT INTO `audit_logs` VALUES ('47', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:04:36');
INSERT INTO `audit_logs` VALUES ('48', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:09:10');
INSERT INTO `audit_logs` VALUES ('49', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:09:10');
INSERT INTO `audit_logs` VALUES ('50', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:09:10');
INSERT INTO `audit_logs` VALUES ('51', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:09:11');
INSERT INTO `audit_logs` VALUES ('52', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:44:20');
INSERT INTO `audit_logs` VALUES ('53', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:51:21');
INSERT INTO `audit_logs` VALUES ('54', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:51:36');
INSERT INTO `audit_logs` VALUES ('55', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:54:06');
INSERT INTO `audit_logs` VALUES ('56', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-13 22:55:02');
INSERT INTO `audit_logs` VALUES ('57', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 00:00:15');
INSERT INTO `audit_logs` VALUES ('58', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 00:27:14');
INSERT INTO `audit_logs` VALUES ('59', '1', 'create_resident', 'Created resident: gorang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 00:57:25');
INSERT INTO `audit_logs` VALUES ('60', '1', 'create_resident', 'Created resident: gorang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 00:57:27');
INSERT INTO `audit_logs` VALUES ('61', '1', 'create_resident', 'Created resident: gorang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 00:57:29');
INSERT INTO `audit_logs` VALUES ('62', '1', 'create_resident', 'Created resident: gorang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 01:00:24');
INSERT INTO `audit_logs` VALUES ('63', '1', 'create_resident', 'Created resident: gorang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 01:03:35');
INSERT INTO `audit_logs` VALUES ('64', '1', 'delete_resident', 'Deleted resident ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 09:31:20');
INSERT INTO `audit_logs` VALUES ('65', '1', 'create_resident', 'Created resident: akjf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 09:32:32');
INSERT INTO `audit_logs` VALUES ('66', '1', 'create_resident', 'Created resident: akjf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 09:51:09');
INSERT INTO `audit_logs` VALUES ('67', '1', 'create_resident', 'Created resident: akjf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 09:51:11');
INSERT INTO `audit_logs` VALUES ('68', '1', 'create_resident', 'Created resident: akjf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 09:51:13');
INSERT INTO `audit_logs` VALUES ('69', '1', 'delete_resident', 'Deleted resident ID: 28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 11:48:05');
INSERT INTO `audit_logs` VALUES ('70', '1', 'delete_resident', 'Deleted resident ID: 28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 11:54:29');
INSERT INTO `audit_logs` VALUES ('71', '1', 'delete_resident', 'Deleted resident ID: 10', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:19:42');
INSERT INTO `audit_logs` VALUES ('72', '1', 'delete_resident', 'Deleted resident ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:20:52');
INSERT INTO `audit_logs` VALUES ('73', '1', 'delete_resident', 'Deleted resident ID: 19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:20:56');
INSERT INTO `audit_logs` VALUES ('74', '1', 'delete_resident', 'Deleted resident ID: 31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:21:12');
INSERT INTO `audit_logs` VALUES ('75', '1', 'delete_resident', 'Deleted resident ID: 32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:46:05');
INSERT INTO `audit_logs` VALUES ('76', '1', 'delete_resident', 'Deleted resident ID: 32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:47:20');
INSERT INTO `audit_logs` VALUES ('77', '1', 'delete_resident', 'Deleted resident ID: 32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:58:05');
INSERT INTO `audit_logs` VALUES ('78', '1', 'delete_resident', 'Deleted resident ID: 32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 17:59:38');
INSERT INTO `audit_logs` VALUES ('79', '1', 'delete_official', 'Deleted official ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 18:07:16');
INSERT INTO `audit_logs` VALUES ('80', '1', 'delete_official', 'Deleted official ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 18:21:57');
INSERT INTO `audit_logs` VALUES ('81', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 18:23:20');
INSERT INTO `audit_logs` VALUES ('82', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 19:00:05');
INSERT INTO `audit_logs` VALUES ('83', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 19:21:22');
INSERT INTO `audit_logs` VALUES ('84', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 19:21:32');
INSERT INTO `audit_logs` VALUES ('85', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', '2026-07-14 19:22:03');
INSERT INTO `audit_logs` VALUES ('86', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 19:18:50');
INSERT INTO `audit_logs` VALUES ('87', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 21:00:56');
INSERT INTO `audit_logs` VALUES ('88', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 21:07:22');
INSERT INTO `audit_logs` VALUES ('89', '1', 'delete_announcement', 'Deleted announcement ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:03:23');
INSERT INTO `audit_logs` VALUES ('90', '1', 'delete_announcement', 'Deleted announcement ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:10:46');
INSERT INTO `audit_logs` VALUES ('91', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:11:18');
INSERT INTO `audit_logs` VALUES ('92', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:11:27');
INSERT INTO `audit_logs` VALUES ('93', '1', 'delete_resident', 'Deleted resident ID: 11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:14:53');
INSERT INTO `audit_logs` VALUES ('94', '1', 'delete_resident', 'Deleted resident ID: 11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:14:53');
INSERT INTO `audit_logs` VALUES ('95', '1', 'delete_resident', 'Deleted resident ID: 30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:15:00');
INSERT INTO `audit_logs` VALUES ('96', '1', 'delete_resident', 'Deleted resident ID: 29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:15:06');
INSERT INTO `audit_logs` VALUES ('97', '1', 'delete_resident', 'Deleted resident ID: 27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:15:11');
INSERT INTO `audit_logs` VALUES ('98', '1', 'delete_resident', 'Deleted resident ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:15:19');
INSERT INTO `audit_logs` VALUES ('99', '1', 'delete_official', 'Deleted official ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:17:38');
INSERT INTO `audit_logs` VALUES ('100', '1', 'delete_resident', 'Deleted resident ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:39:46');
INSERT INTO `audit_logs` VALUES ('101', '1', 'delete_resident', 'Deleted resident ID: 25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:39:57');
INSERT INTO `audit_logs` VALUES ('102', '1', 'delete_resident', 'Deleted resident ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:40:12');
INSERT INTO `audit_logs` VALUES ('103', '1', 'delete_resident', 'Deleted resident ID: 18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:40:19');
INSERT INTO `audit_logs` VALUES ('104', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:40:34');
INSERT INTO `audit_logs` VALUES ('105', '1', 'delete_resident', 'Deleted resident ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:41:28');
INSERT INTO `audit_logs` VALUES ('106', '1', 'delete_resident', 'Deleted resident ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:52:52');
INSERT INTO `audit_logs` VALUES ('107', '1', 'delete_resident', 'Deleted resident ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:52:52');
INSERT INTO `audit_logs` VALUES ('108', '1', 'delete_resident', 'Deleted resident ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:52:53');
INSERT INTO `audit_logs` VALUES ('109', '1', 'delete_resident', 'Deleted resident ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:52:53');
INSERT INTO `audit_logs` VALUES ('110', '1', 'delete_resident', 'Deleted resident ID: 8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 22:53:00');
INSERT INTO `audit_logs` VALUES ('111', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-15 23:16:02');
INSERT INTO `audit_logs` VALUES ('112', '1', 'delete_announcement', 'Deleted announcement ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 23:17:06');
INSERT INTO `audit_logs` VALUES ('113', '1', 'create_announcement', 'Created announcement: WeGSdVsdgV', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 23:17:51');
INSERT INTO `audit_logs` VALUES ('114', '1', 'delete_announcement', 'Deleted announcement ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 23:17:51');
INSERT INTO `audit_logs` VALUES ('115', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-15 23:21:46');
INSERT INTO `audit_logs` VALUES ('116', '1', 'change_own_password', 'User changed own password ID: 1', 'unknown', 'unknown', '2026-07-15 23:35:34');
INSERT INTO `audit_logs` VALUES ('117', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 00:10:39');
INSERT INTO `audit_logs` VALUES ('118', '1', 'archive_announcement', 'Archived announcement ID: 11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 01:16:19');
INSERT INTO `audit_logs` VALUES ('119', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-16 01:31:22');
INSERT INTO `audit_logs` VALUES ('120', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-16 01:31:26');
INSERT INTO `audit_logs` VALUES ('121', '1', 'update_user', 'Updated user ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 01:43:54');
INSERT INTO `audit_logs` VALUES ('122', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 09:17:07');
INSERT INTO `audit_logs` VALUES ('123', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 19:03:24');
INSERT INTO `audit_logs` VALUES ('124', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-16 22:09:50');
INSERT INTO `audit_logs` VALUES ('125', '1', 'delete_resident', 'Deleted resident ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 22:38:25');
INSERT INTO `audit_logs` VALUES ('126', '1', 'delete_resident', 'Deleted resident ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 23:00:57');
INSERT INTO `audit_logs` VALUES ('127', '1', 'delete_resident', 'Deleted resident ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 23:06:46');
INSERT INTO `audit_logs` VALUES ('128', '1', 'bulk_delete_resident', 'Bulk deleted 11 residents', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 23:20:48');
INSERT INTO `audit_logs` VALUES ('129', '1', 'bulk_delete_resident', 'Bulk deleted 11 residents', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-16 23:28:18');
INSERT INTO `audit_logs` VALUES ('130', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-16 23:37:23');
INSERT INTO `audit_logs` VALUES ('131', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 00:38:46');
INSERT INTO `audit_logs` VALUES ('132', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 00:46:24');
INSERT INTO `audit_logs` VALUES ('133', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 00:50:32');
INSERT INTO `audit_logs` VALUES ('134', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 00:50:48');
INSERT INTO `audit_logs` VALUES ('135', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 00:51:02');
INSERT INTO `audit_logs` VALUES ('136', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 01:00:31');
INSERT INTO `audit_logs` VALUES ('137', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 01:00:37');
INSERT INTO `audit_logs` VALUES ('138', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 01:00:44');
INSERT INTO `audit_logs` VALUES ('139', '5', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-17 01:07:03');
INSERT INTO `audit_logs` VALUES ('140', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 01:11:01');
INSERT INTO `audit_logs` VALUES ('141', '8', 'update_settings', 'Resident updated preferences', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 22:27:05');
INSERT INTO `audit_logs` VALUES ('142', '8', 'update_profile', 'Resident updated profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 22:28:12');
INSERT INTO `audit_logs` VALUES ('143', '8', 'update_profile', 'Resident updated profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 22:28:18');
INSERT INTO `audit_logs` VALUES ('144', '8', 'update_profile', 'Resident updated profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 22:30:27');
INSERT INTO `audit_logs` VALUES ('145', '8', 'update_settings', 'Resident updated preferences', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 22:42:17');
INSERT INTO `audit_logs` VALUES ('146', '8', 'create_appointment', 'Resident booked appointment for 2026-07-17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-17 22:43:00');
INSERT INTO `audit_logs` VALUES ('147', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 20:26:25');
INSERT INTO `audit_logs` VALUES ('148', '1', 'update_application', 'Updated application ID: 2 status: under_review', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 20:28:15');
INSERT INTO `audit_logs` VALUES ('149', '1', 'update_remarks', 'Updated remarks for application ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 20:39:25');
INSERT INTO `audit_logs` VALUES ('150', '1', 'create_announcement', 'Created announcement: para kay secretary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 22:08:45');
INSERT INTO `audit_logs` VALUES ('151', '1', 'create_project', 'Created project: flood control', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 22:12:08');
INSERT INTO `audit_logs` VALUES ('152', '9', 'create_appointment', 'Resident booked appointment for 2026-07-18', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-18 22:18:16');
INSERT INTO `audit_logs` VALUES ('153', '9', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-18 22:56:54');
INSERT INTO `audit_logs` VALUES ('154', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 23:13:54');
INSERT INTO `audit_logs` VALUES ('155', '9', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-18 23:15:18');
INSERT INTO `audit_logs` VALUES ('156', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 23:23:10');
INSERT INTO `audit_logs` VALUES ('157', '1', 'create_announcement', 'Created announcement: isa pa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 23:34:49');
INSERT INTO `audit_logs` VALUES ('158', '1', 'update_resident', 'Updated resident ID: 36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-18 23:58:03');
INSERT INTO `audit_logs` VALUES ('159', '1', 'update_resident', 'Updated resident ID: 36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-19 00:03:10');
INSERT INTO `audit_logs` VALUES ('160', '1', 'update_resident', 'Updated resident ID: 36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-19 00:07:54');
INSERT INTO `audit_logs` VALUES ('161', '1', 'update_resident', 'Updated resident ID: 36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-19 00:11:30');
INSERT INTO `audit_logs` VALUES ('162', '2', 'delete_announcement', 'Deleted announcement ID: 6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-19 00:16:07');
INSERT INTO `audit_logs` VALUES ('163', '1', 'create_official', 'Created official: buangon (kagawad)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-20 18:59:17');
INSERT INTO `audit_logs` VALUES ('164', '1', 'create_official', 'Created official: buangon (kagawad)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-20 22:40:25');
INSERT INTO `audit_logs` VALUES ('165', '1', 'delete_official', 'Deleted official ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-20 22:40:33');
INSERT INTO `audit_logs` VALUES ('166', '1', 'delete_official', 'Deleted official ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-20 22:41:17');
INSERT INTO `audit_logs` VALUES ('167', '1', 'update_resident', 'Updated resident ID: 37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:26:52');
INSERT INTO `audit_logs` VALUES ('168', '9', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-21 04:02:01');
INSERT INTO `audit_logs` VALUES ('169', '9', 'change_password', 'Resident changed password', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-21 04:02:42');
INSERT INTO `audit_logs` VALUES ('170', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 04:04:11');
INSERT INTO `audit_logs` VALUES ('171', '1', 'update_remarks', 'Updated remarks for application ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 04:06:10');
INSERT INTO `audit_logs` VALUES ('172', '1', 'create_announcement', 'Created announcement: mic test para kay resident', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 04:08:23');
INSERT INTO `audit_logs` VALUES ('173', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:13:35');
INSERT INTO `audit_logs` VALUES ('174', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:13:42');
INSERT INTO `audit_logs` VALUES ('175', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:14:11');
INSERT INTO `audit_logs` VALUES ('176', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 15:14:52');
INSERT INTO `audit_logs` VALUES ('177', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 15:14:56');
INSERT INTO `audit_logs` VALUES ('178', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:33:01');
INSERT INTO `audit_logs` VALUES ('179', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 15:34:40');
INSERT INTO `audit_logs` VALUES ('180', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 15:34:51');
INSERT INTO `audit_logs` VALUES ('181', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:47:24');
INSERT INTO `audit_logs` VALUES ('182', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:47:25');
INSERT INTO `audit_logs` VALUES ('183', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:47:25');
INSERT INTO `audit_logs` VALUES ('184', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:47:40');
INSERT INTO `audit_logs` VALUES ('185', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:47:49');
INSERT INTO `audit_logs` VALUES ('186', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 15:52:30');
INSERT INTO `audit_logs` VALUES ('187', '1', 'create_announcement', 'Created announcement: zrgbzdfb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:53:53');
INSERT INTO `audit_logs` VALUES ('188', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 15:56:25');
INSERT INTO `audit_logs` VALUES ('189', '2', 'update_settings', 'Secretary updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:58:22');
INSERT INTO `audit_logs` VALUES ('190', '2', 'update_settings', 'Secretary updated preferences', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:58:29');
INSERT INTO `audit_logs` VALUES ('191', '2', 'update_settings', 'Secretary updated preferences', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 15:58:58');
INSERT INTO `audit_logs` VALUES ('192', '1', 'create_announcement', 'Created announcement: zrgbzdfb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 17:07:23');
INSERT INTO `audit_logs` VALUES ('193', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 17:10:28');
INSERT INTO `audit_logs` VALUES ('194', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-22 17:10:38');
INSERT INTO `audit_logs` VALUES ('195', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 17:44:52');
INSERT INTO `audit_logs` VALUES ('196', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 17:54:22');
INSERT INTO `audit_logs` VALUES ('197', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 17:54:46');
INSERT INTO `audit_logs` VALUES ('198', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 18:06:54');
INSERT INTO `audit_logs` VALUES ('199', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-22 18:07:00');
INSERT INTO `audit_logs` VALUES ('200', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 04:40:58');
INSERT INTO `audit_logs` VALUES ('201', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 05:21:49');
INSERT INTO `audit_logs` VALUES ('202', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 05:21:57');
INSERT INTO `audit_logs` VALUES ('203', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 05:22:22');
INSERT INTO `audit_logs` VALUES ('204', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 05:23:15');
INSERT INTO `audit_logs` VALUES ('205', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 05:28:07');
INSERT INTO `audit_logs` VALUES ('206', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 05:28:20');
INSERT INTO `audit_logs` VALUES ('207', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 05:28:30');
INSERT INTO `audit_logs` VALUES ('208', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 05:41:05');
INSERT INTO `audit_logs` VALUES ('209', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 05:41:13');
INSERT INTO `audit_logs` VALUES ('210', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 05:41:23');
INSERT INTO `audit_logs` VALUES ('211', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 05:41:30');
INSERT INTO `audit_logs` VALUES ('212', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 18:53:48');
INSERT INTO `audit_logs` VALUES ('213', '1', 'update_settings', 'Updated system settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 18:54:14');
INSERT INTO `audit_logs` VALUES ('214', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 18:54:51');
INSERT INTO `audit_logs` VALUES ('215', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 18:55:37');
INSERT INTO `audit_logs` VALUES ('216', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 18:55:47');
INSERT INTO `audit_logs` VALUES ('217', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 19:18:31');
INSERT INTO `audit_logs` VALUES ('218', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:19:20');
INSERT INTO `audit_logs` VALUES ('219', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:19:30');
INSERT INTO `audit_logs` VALUES ('220', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:19:30');
INSERT INTO `audit_logs` VALUES ('221', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:19:38');
INSERT INTO `audit_logs` VALUES ('222', '2', 'update_settings', 'Secretary updated account settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:31:33');
INSERT INTO `audit_logs` VALUES ('223', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-23 19:32:15');
INSERT INTO `audit_logs` VALUES ('224', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:33:20');
INSERT INTO `audit_logs` VALUES ('225', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:33:20');
INSERT INTO `audit_logs` VALUES ('226', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:33:20');
INSERT INTO `audit_logs` VALUES ('227', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-23 19:33:20');
INSERT INTO `audit_logs` VALUES ('228', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 05:16:03');
INSERT INTO `audit_logs` VALUES ('229', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 05:31:16');
INSERT INTO `audit_logs` VALUES ('230', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 05:38:56');
INSERT INTO `audit_logs` VALUES ('231', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 05:41:36');
INSERT INTO `audit_logs` VALUES ('232', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 05:52:51');
INSERT INTO `audit_logs` VALUES ('233', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 06:01:28');
INSERT INTO `audit_logs` VALUES ('234', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:16:02');
INSERT INTO `audit_logs` VALUES ('235', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:16:08');
INSERT INTO `audit_logs` VALUES ('236', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:16:34');
INSERT INTO `audit_logs` VALUES ('237', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:20:55');
INSERT INTO `audit_logs` VALUES ('238', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:20:55');
INSERT INTO `audit_logs` VALUES ('239', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:21:01');
INSERT INTO `audit_logs` VALUES ('240', '12', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:28:37');
INSERT INTO `audit_logs` VALUES ('241', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:34:50');
INSERT INTO `audit_logs` VALUES ('242', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 06:42:40');
INSERT INTO `audit_logs` VALUES ('243', '1', 'upload_hero_image', 'Uploaded hero background image', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 06:43:20');
INSERT INTO `audit_logs` VALUES ('244', '1', 'create_resident_profile', 'Created resident profile: coby Arguilles Curimao Jr.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 06:48:31');
INSERT INTO `audit_logs` VALUES ('245', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 06:49:48');
INSERT INTO `audit_logs` VALUES ('246', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 06:50:00');
INSERT INTO `audit_logs` VALUES ('247', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 07:07:28');
INSERT INTO `audit_logs` VALUES ('248', '12', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 07:08:04');
INSERT INTO `audit_logs` VALUES ('249', '12', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 07:08:04');
INSERT INTO `audit_logs` VALUES ('250', '12', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 07:08:08');
INSERT INTO `audit_logs` VALUES ('251', '1', 'delete_residents_bulk', 'Deleted 3 residents', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 07:20:39');
INSERT INTO `audit_logs` VALUES ('252', '1', 'update_resident_profile', 'Updated resident profile ID: 39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 07:34:45');
INSERT INTO `audit_logs` VALUES ('253', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 07:48:24');
INSERT INTO `audit_logs` VALUES ('254', '12', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 08:02:12');
INSERT INTO `audit_logs` VALUES ('255', '11', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 08:43:19');
INSERT INTO `audit_logs` VALUES ('256', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 08:51:59');
INSERT INTO `audit_logs` VALUES ('257', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 09:55:19');
INSERT INTO `audit_logs` VALUES ('258', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 09:55:26');
INSERT INTO `audit_logs` VALUES ('259', '1', 'create_user', 'Created user: Kapitan (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 09:59:04');
INSERT INTO `audit_logs` VALUES ('260', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:00:11');
INSERT INTO `audit_logs` VALUES ('261', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:00:27');
INSERT INTO `audit_logs` VALUES ('262', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-24 10:00:55');
INSERT INTO `audit_logs` VALUES ('263', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:08:38');
INSERT INTO `audit_logs` VALUES ('264', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:08:47');
INSERT INTO `audit_logs` VALUES ('265', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:08:51');
INSERT INTO `audit_logs` VALUES ('266', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:16:59');
INSERT INTO `audit_logs` VALUES ('267', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:17:10');
INSERT INTO `audit_logs` VALUES ('268', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:17:36');
INSERT INTO `audit_logs` VALUES ('269', '2', 'update_settings', 'Secretary updated account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:17:40');
INSERT INTO `audit_logs` VALUES ('270', '1', 'update_remarks', 'Updated remarks for application ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:35:02');
INSERT INTO `audit_logs` VALUES ('271', '1', 'update_application', 'Updated application ID: 6 status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:48:34');
INSERT INTO `audit_logs` VALUES ('272', '1', 'update_application', 'Updated application ID: 6 status: ready_for_pickup', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:48:37');
INSERT INTO `audit_logs` VALUES ('273', '1', 'update_application', 'Updated application ID: 6 status: completed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:48:50');
INSERT INTO `audit_logs` VALUES ('274', '1', 'update_remarks', 'Updated remarks for application ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:49:33');
INSERT INTO `audit_logs` VALUES ('275', '1', 'update_application', 'Updated application ID: 5 status: completed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:49:37');
INSERT INTO `audit_logs` VALUES ('276', '1', 'update_application', 'Updated application ID: 3 status: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:49:40');
INSERT INTO `audit_logs` VALUES ('277', '1', 'update_landing_content', 'Updated landing page text content', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:53:49');
INSERT INTO `audit_logs` VALUES ('278', '1', 'backup', 'Database backup created: trace_db_backup_2026-07-24_04-54-26.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 10:54:26');
INSERT INTO `audit_logs` VALUES ('279', '2', 'delete_announcement', 'Deleted announcement ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 11:22:04');
INSERT INTO `audit_logs` VALUES ('280', '2', 'delete_announcement', 'Deleted announcement ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 11:22:10');
INSERT INTO `audit_logs` VALUES ('281', '2', 'delete_announcement', 'Deleted announcement ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 11:22:22');
INSERT INTO `audit_logs` VALUES ('282', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-24 11:56:57');
INSERT INTO `audit_logs` VALUES ('283', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 09:32:47');
INSERT INTO `audit_logs` VALUES ('284', '1', 'update_settings', 'Updated system settings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 09:33:40');
INSERT INTO `audit_logs` VALUES ('285', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 09:57:15');
INSERT INTO `audit_logs` VALUES ('286', '1', 'create_agenda', 'Created agenda: kadi lang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:27:00');
INSERT INTO `audit_logs` VALUES ('287', '1', 'create_agenda', 'Created agenda: kadi lang', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:28:32');
INSERT INTO `audit_logs` VALUES ('288', '1', 'bulk_import_residents', 'Bulk imported 0 residents (0 skipped)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:30:05');
INSERT INTO `audit_logs` VALUES ('289', '1', 'bulk_import_residents', 'Bulk imported 0 residents (14 skipped)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:30:36');
INSERT INTO `audit_logs` VALUES ('290', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:41:26');
INSERT INTO `audit_logs` VALUES ('291', '1', 'upload_hero_image', 'Uploaded hero background image', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:43:11');
INSERT INTO `audit_logs` VALUES ('292', '1', 'delete_announcement', 'Deleted announcement ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:53:25');
INSERT INTO `audit_logs` VALUES ('293', '1', 'delete_announcement', 'Deleted announcement ID: 8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 18:53:32');
INSERT INTO `audit_logs` VALUES ('294', '1', 'delete_announcement', 'Deleted announcement ID: 8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:00:06');
INSERT INTO `audit_logs` VALUES ('295', '2', 'delete_announcement', 'Deleted announcement ID: 14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:04:29');
INSERT INTO `audit_logs` VALUES ('296', '2', 'delete_announcement', 'Deleted announcement ID: 12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:04:33');
INSERT INTO `audit_logs` VALUES ('297', '1', 'delete_announcement', 'Deleted announcement ID: 14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:07:16');
INSERT INTO `audit_logs` VALUES ('298', '1', 'delete_announcement', 'Deleted announcement ID: 13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:07:21');
INSERT INTO `audit_logs` VALUES ('299', '1', 'delete_announcement', 'Deleted announcement ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:07:27');
INSERT INTO `audit_logs` VALUES ('300', '1', 'delete_announcement', 'Deleted announcement ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-25 19:07:30');
INSERT INTO `audit_logs` VALUES ('301', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-26 02:08:28');
INSERT INTO `audit_logs` VALUES ('302', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-26 02:48:38');
INSERT INTO `audit_logs` VALUES ('303', '10', 'update_settings', 'Resident updated preferences', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-07-26 02:48:47');
INSERT INTO `audit_logs` VALUES ('304', '2', 'create_announcement', 'Created announcement: ang lahat ay may ayuda', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-26 03:00:15');
INSERT INTO `audit_logs` VALUES ('305', '1', 'update_settings', 'Updated system settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-26 03:14:44');


CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `disaster_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_number` varchar(100) NOT NULL,
  `control_number` varchar(100) NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `file_path` varchar(255) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `documents` VALUES ('1', '34', 'Custom Certificate', 'CUSTOM CERTIFICATE-20260718-2626', 'CUSTOM CERTIFICATE-2607-046464', '', 'archived', NULL, 'assets/uploads/qr/doc_6a5b8ce678ced.png', '2', NULL, '2026-07-18 22:25:43');
INSERT INTO `documents` VALUES ('2', '38', 'Business Clearance', 'BUSINESS CLEARANCE-20260724-3688', 'BUSINESS CLEARANCE-2607-879832', '', 'issued', NULL, 'assets/uploads/qr/doc_6a62b9952e8c4.png', '2', NULL, '2026-07-24 09:02:14');


CREATE TABLE `download_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `event_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_time` time DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(500) DEFAULT '',
  `event_type` varchar(50) DEFAULT 'general',
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `events` VALUES ('1', 'Monthly Barangay Assembly', '2025-07-15', 'All residents are invited to attend the monthly assembly. Agenda includes Q3 budget review, infrastructure updates, and community program announcements.', '2026-07-09 01:50:03', '15:00:00', NULL, NULL, 'Barangay Hall', 'meeting', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('2', 'Free Medical Mission', '2025-07-20', 'Partnership with the Municipal Health Office — free check-ups, blood pressure monitoring, blood sugar testing, and medicines. Bring PhilHealth or valid ID.', '2026-07-09 01:50:03', '08:00:00', NULL, NULL, 'Barangay Health Center', 'health', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('3', 'Community Clean-Up Drive', '2025-07-05', 'Monthly clean-up drive. Assembly at Barangay Plaza. Gloves and garbage bags will be provided. Let\'s work together for a cleaner barangay!', '2026-07-09 01:50:03', '06:00:00', NULL, NULL, 'Barangay Plaza', 'community', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('4', 'Scholarship Application Deadline', '2025-08-01', 'Last day to submit applications for the SK Educational Assistance Program for SY 2025-2026. Submit requirements at the Barangay Hall.', '2026-07-09 01:50:03', '17:00:00', NULL, NULL, 'Barangay Hall', 'education', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('5', 'Basketball League Opening', '2025-08-05', 'Inter-purok basketball league opening ceremony and first games. All purok teams are expected to register by July 25.', '2026-07-09 01:50:03', '16:00:00', NULL, NULL, 'Barangay Covered Court', 'sports', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('6', 'Livelihood Skills Training', '2025-07-25', 'Free livelihood skills training on food processing and handicraft making. Open to all residents, priority given to indigent families.', '2026-07-09 01:50:03', '09:00:00', NULL, NULL, 'Barangay Training Center', 'livelihood', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('7', 'Feast Day Celebration', '2025-08-15', 'Annual barangay fiesta celebration with parade, cultural shows, and community feast. All residents and visitors are welcome.', '2026-07-09 01:50:03', '07:00:00', NULL, NULL, 'Barangay Plaza & Main Roads', 'celebration', '1', '2026-07-09 01:50:03');
INSERT INTO `events` VALUES ('8', 'Disaster Preparedness Seminar', '2025-07-10', 'Earthquake and typhoon preparedness seminar by BDRRMC. Learn evacuation routes, emergency kits, and family disaster plans.', '2026-07-09 01:50:03', '14:00:00', NULL, NULL, 'Barangay Hall', 'emergency', '1', '2026-07-09 01:50:03');


CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `households` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `household_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `head_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `landing_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `landing_content` VALUES ('1', 'hero', 'A transparent, resilient, and accountable barangay system for every resident.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');
INSERT INTO `landing_content` VALUES ('2', 'mission', 'To deliver responsive public service with integrity and accountability.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');
INSERT INTO `landing_content` VALUES ('3', 'vision', 'A digitally connected barangay that promotes transparency and civic participation.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');
INSERT INTO `landing_content` VALUES ('4', 'objectives', 'To improve accessibility to services, records, and public information.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');
INSERT INTO `landing_content` VALUES ('5', 'history', 'The barangay continues to strengthen governance through innovation and community engagement.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');
INSERT INTO `landing_content` VALUES ('6', 'services', 'Residents can request documents, view announcements, and verify their identity through QR access.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');
INSERT INTO `landing_content` VALUES ('7', 'contact', 'Office: Barangay Hall, Tumalaytay. Contact the barangay office for verification and support.', '2026-07-03 01:04:34', '2026-07-24 10:53:49');
INSERT INTO `landing_content` VALUES ('8', 'footer', 'Thank you for partnering with the barangay in building a stronger community.', '2026-07-03 01:04:34', '2026-07-09 01:14:58');


CREATE TABLE `landing_officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `official_name` varchar(150) NOT NULL,
  `position_title` varchar(150) NOT NULL,
  `position_label` varchar(100) DEFAULT NULL,
  `tier` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `committee` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `landing_officials` VALUES ('1', 'Hon. Juan Dela Cruz', 'Barangay Captain', 'Punong Barangay', 'captain', '1', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');
INSERT INTO `landing_officials` VALUES ('2', 'Maria Santos', 'Secretary', 'Kalihim', 'executive', '1', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');
INSERT INTO `landing_officials` VALUES ('3', 'Roberto Garcia', 'Treasurer', 'Ingat-Yaman', 'executive', '2', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');
INSERT INTO `landing_officials` VALUES ('4', 'Ana Reyes', 'Kagawad', '', 'kagawad', '1', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');
INSERT INTO `landing_officials` VALUES ('5', 'Carlos Mendoza', 'Kagawad', '', 'kagawad', '2', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');
INSERT INTO `landing_officials` VALUES ('6', 'Elena Villanueva', 'Kagawad', '', 'kagawad', '3', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');
INSERT INTO `landing_officials` VALUES ('7', 'Andrei Cruz', 'SK Chairperson', 'Sangguniang Kabataan', 'sk', '1', '1', NULL, '2026-07-09 01:18:36', '2026-07-09 01:36:48', '');


CREATE TABLE `landing_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stat_label` varchar(100) NOT NULL,
  `stat_value` int(11) DEFAULT 0,
  `stat_suffix` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `landing_stats` VALUES ('1', 'Residents', '2450', '+', '1', '1', '2026-07-09 01:18:36', '2026-07-09 01:36:48');
INSERT INTO `landing_stats` VALUES ('2', 'Households', '580', '+', '2', '1', '2026-07-09 01:18:36', '2026-07-09 01:36:48');
INSERT INTO `landing_stats` VALUES ('3', 'Projects', '24', '', '3', '1', '2026-07-09 01:18:36', '2026-07-09 01:36:48');
INSERT INTO `landing_stats` VALUES ('4', 'Transparency Rate', '98', '%', '4', '1', '2026-07-09 01:18:36', '2026-07-09 01:36:48');


CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `link` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifications` VALUES ('3', '8', 'Your Certificate of Indigency request #2 has been submitted. We will notify you of updates.', '0', '/FinalTrace/resident/requests.php', '2026-07-17 22:29:44');
INSERT INTO `notifications` VALUES ('4', '8', 'Your Certificate of Indigency request #2 status is now Under Review.', '0', '/FinalTrace/resident/requests.php', '2026-07-18 20:28:15');
INSERT INTO `notifications` VALUES ('5', '2', 'New announcement for secretaries: para kay secretary', '0', '/FinalTrace/secretary/announcements.php', '2026-07-18 22:08:45');
INSERT INTO `notifications` VALUES ('6', '4', 'New announcement for secretaries: para kay secretary', '0', '/FinalTrace/secretary/announcements.php', '2026-07-18 22:08:45');
INSERT INTO `notifications` VALUES ('7', '8', 'Your Certificate of Indigency request #2 status is now Approved.', '0', '/FinalTrace/resident/requests.php', '2026-07-18 22:24:38');
INSERT INTO `notifications` VALUES ('8', '8', 'Your Certificate of Indigency request #2 status is now Ready For Pickup.', '0', '/FinalTrace/resident/requests.php', '2026-07-18 22:24:43');
INSERT INTO `notifications` VALUES ('9', '8', 'Your Certificate of Indigency request #2 status is now Completed.', '0', '/FinalTrace/resident/requests.php', '2026-07-18 22:24:45');
INSERT INTO `notifications` VALUES ('10', '8', 'New announcement: isa pa', '0', '/FinalTrace/resident/announcements.php', '2026-07-18 23:34:49');
INSERT INTO `notifications` VALUES ('11', '9', 'New announcement: isa pa', '1', '/FinalTrace/resident/announcements.php', '2026-07-18 23:34:49');
INSERT INTO `notifications` VALUES ('12', '8', 'New announcement: mic test para kay resident', '0', '/FinalTrace/resident/announcements.php', '2026-07-21 04:08:23');
INSERT INTO `notifications` VALUES ('13', '9', 'New announcement: mic test para kay resident', '1', '/FinalTrace/resident/announcements.php', '2026-07-21 04:08:23');
INSERT INTO `notifications` VALUES ('14', '10', 'New announcement: mic test para kay resident', '1', '/FinalTrace/resident/announcements.php', '2026-07-21 04:08:23');
INSERT INTO `notifications` VALUES ('15', '11', 'New announcement: mic test para kay resident', '1', '/FinalTrace/resident/announcements.php', '2026-07-21 04:08:23');
INSERT INTO `notifications` VALUES ('16', '9', 'Your Permit request #3 has been submitted. We will notify you of updates.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:22:40');
INSERT INTO `notifications` VALUES ('17', '9', 'Your Barangay Clearance request #4 has been submitted. We will notify you of updates.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:25:23');
INSERT INTO `notifications` VALUES ('18', '9', 'Your Certificate of Residency request #5 has been submitted. We will notify you of updates.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:27:34');
INSERT INTO `notifications` VALUES ('19', '9', 'Your Certificate of Residency request #5 status is now Under Review.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:50:09');
INSERT INTO `notifications` VALUES ('20', '9', 'Your Certificate of Residency request #5 status is now Approved.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:50:17');
INSERT INTO `notifications` VALUES ('21', '9', 'Your Barangay Clearance request #4 status is now Under Review.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:50:21');
INSERT INTO `notifications` VALUES ('22', '9', 'Your Barangay Clearance request #4 status is now Rejected.', '1', '/FinalTrace/resident/requests.php', '2026-07-21 04:50:24');
INSERT INTO `notifications` VALUES ('23', '9', 'Your Permit request #3 status is now Under Review.', '0', '/FinalTrace/resident/requests.php', '2026-07-21 04:51:57');
INSERT INTO `notifications` VALUES ('24', '9', 'Your Certificate of Residency request #5 status is now Ready For Pickup.', '0', '/FinalTrace/resident/requests.php', '2026-07-21 04:52:02');
INSERT INTO `notifications` VALUES ('25', '8', 'New announcement: zrgbzdfb', '0', '/FinalTrace/resident/announcements.php', '2026-07-22 15:53:53');
INSERT INTO `notifications` VALUES ('26', '9', 'New announcement: zrgbzdfb', '0', '/FinalTrace/resident/announcements.php', '2026-07-22 15:53:53');
INSERT INTO `notifications` VALUES ('27', '10', 'New announcement: zrgbzdfb', '1', '/FinalTrace/resident/announcements.php', '2026-07-22 15:53:53');
INSERT INTO `notifications` VALUES ('28', '11', 'New announcement: zrgbzdfb', '1', '/FinalTrace/resident/announcements.php', '2026-07-22 15:53:53');
INSERT INTO `notifications` VALUES ('29', '8', 'New announcement: zrgbzdfb', '0', '/FinalTrace/resident/announcements.php', '2026-07-22 17:07:23');
INSERT INTO `notifications` VALUES ('30', '9', 'New announcement: zrgbzdfb', '0', '/FinalTrace/resident/announcements.php', '2026-07-22 17:07:23');
INSERT INTO `notifications` VALUES ('31', '10', 'New announcement: zrgbzdfb', '1', '/FinalTrace/resident/announcements.php', '2026-07-22 17:07:23');
INSERT INTO `notifications` VALUES ('32', '11', 'New announcement: zrgbzdfb', '1', '/FinalTrace/resident/announcements.php', '2026-07-22 17:07:23');
INSERT INTO `notifications` VALUES ('33', '12', 'Your Barangay Clearance request #6 has been submitted. We will notify you of updates.', '0', '/FinalTrace/resident/requests.php', '2026-07-24 08:19:19');
INSERT INTO `notifications` VALUES ('34', '12', 'Your Barangay Clearance request #6 status is now Under Review.', '0', '/FinalTrace/resident/requests.php', '2026-07-24 08:19:35');
INSERT INTO `notifications` VALUES ('35', '12', 'Your Barangay Clearance request #6 status is now Approved.', '0', '/FinalTrace/resident/requests.php', '2026-07-24 10:48:34');
INSERT INTO `notifications` VALUES ('36', '12', 'Your Barangay Clearance request #6 status is now Ready For Pickup.', '0', '/FinalTrace/resident/requests.php', '2026-07-24 10:48:37');
INSERT INTO `notifications` VALUES ('37', '12', 'Your Barangay Clearance request #6 status is now Completed.', '0', '/FinalTrace/resident/requests.php', '2026-07-24 10:48:50');
INSERT INTO `notifications` VALUES ('38', '10', 'Your Business Clearance request #7 has been submitted. We will notify you of updates.', '1', '/FinalTrace/resident/requests.php', '2026-07-26 02:49:57');
INSERT INTO `notifications` VALUES ('39', '8', 'New announcement: ang lahat ay may ayuda', '0', '/FinalTrace/resident/notifications.php', '2026-07-26 03:00:14');
INSERT INTO `notifications` VALUES ('40', '10', 'New announcement: ang lahat ay may ayuda', '1', '/FinalTrace/resident/notifications.php', '2026-07-26 03:00:14');
INSERT INTO `notifications` VALUES ('41', '11', 'New announcement: ang lahat ay may ayuda', '0', '/FinalTrace/resident/notifications.php', '2026-07-26 03:00:14');
INSERT INTO `notifications` VALUES ('42', '12', 'New announcement: ang lahat ay may ayuda', '0', '/FinalTrace/resident/notifications.php', '2026-07-26 03:00:14');
INSERT INTO `notifications` VALUES ('43', '11', 'Your Permit request #8 has been submitted. We will notify you of updates.', '0', '/FinalTrace/resident/requests.php', '2026-07-26 03:02:10');


CREATE TABLE `officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `position` varchar(100) NOT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `officials` VALUES ('1', 'pogi', 'president', '090990909090', 'assets/uploads/official_6a4e5eab6a7e7_PROFILE.png.jpg', '2026-07-08 22:28:59');
INSERT INTO `officials` VALUES ('5', 'buangon', 'kagawad', '0982102626', 'assets/uploads/official_6a5e33598f26f_cert2.png.jpg', '2026-07-20 22:40:25');


CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `personal_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `education` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `personal_information` VALUES ('3', '4', 'Single', NULL, 'manugkupras', 'harvard graduate', '2026-07-14 00:53:54');
INSERT INTO `personal_information` VALUES ('32', '34', 'Separated', NULL, '', '', '2026-07-17 22:28:12');
INSERT INTO `personal_information` VALUES ('33', '36', 'Married', NULL, '', '', '2026-07-18 23:58:03');
INSERT INTO `personal_information` VALUES ('34', '37', 'Widowed', NULL, '', '', '2026-07-21 00:26:52');


CREATE TABLE `project_budget` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `source` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'allocation',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `project_budget` VALUES ('2', '2', '1000000000.00', 'hatagnigov', 'allocation', 'Initial budget', '2026-07-18 22:12:08');


CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'planned',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `progress_percent` int(11) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `projects` VALUES ('2', 'flood control', 'amigo nga buaya', 'mabaha sa kwarta', 'Infrastructure', 'dapdap', 'ongoing', '2026-07-18', '2034-12-18', '15', '1', '2026-07-18 22:12:08', '2026-07-18 22:12:08');


CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `qr_value` varchar(500) NOT NULL,
  `qr_type` varchar(50) DEFAULT 'resident',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qr_codes` VALUES ('1', '34', 'resident:34:adminako', 'resident', '2026-07-24 08:59:57');


CREATE TABLE `residents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `household_number` varchar(50) DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `education` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resident_type` varchar(50) DEFAULT 'regular',
  `qr_code_path` varchar(255) DEFAULT NULL,
  `qr_code_identifier` varchar(255) DEFAULT NULL,
  `senior_citizen_id` varchar(100) DEFAULT NULL,
  `osca_id` varchar(100) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `is_senior` tinyint(1) NOT NULL DEFAULT 0,
  `philsys_pcn` varchar(50) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthplace` varchar(200) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `purok_sitio_id` int(11) DEFAULT NULL,
  `housing_material` varchar(50) DEFAULT NULL,
  `tenure_status` varchar(50) DEFAULT NULL,
  `drinking_water_source` varchar(50) DEFAULT NULL,
  `toilet_facility_type` varchar(50) DEFAULT NULL,
  `household_members` int(11) DEFAULT 1,
  `employment_status` varchar(50) DEFAULT NULL,
  `monthly_household_income` decimal(10,2) DEFAULT NULL,
  `pwd_disability_type` varchar(100) DEFAULT NULL,
  `is_senior_citizen` tinyint(1) DEFAULT 0,
  `is_pwd` tinyint(1) DEFAULT 0,
  `is_solo_parent` tinyint(1) DEFAULT 0,
  `is_ofw` tinyint(1) DEFAULT 0,
  `is_indigent` tinyint(1) DEFAULT 0,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT NULL,
  `house_number` varchar(50) DEFAULT NULL,
  `street_name` varchar(100) DEFAULT NULL,
  `educational_attainment` varchar(50) DEFAULT NULL,
  `primary_occupation` varchar(100) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code_identifier` (`qr_code_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `residents` VALUES ('4', NULL, 'gorang', '1953-06-14', 'Male', 'dasd', '1243546', '35', 'Single', 'manugkupras', 'harvard graduate', 'N/a', '2026-07-14 00:53:54', 'senior_citizen', NULL, 'RES-2026-00004', NULL, NULL, NULL, NULL, 'active', '2026-07-19 00:49:59', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `residents` VALUES ('34', '8', 'adminako', '2026-07-17', 'Male', 'Pending update', 'Pending update', NULL, NULL, NULL, NULL, '', '2026-07-17 22:23:54', 'regular', NULL, 'RES-2026-00034', NULL, NULL, NULL, NULL, 'active', '2026-07-19 00:49:59', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `residents` VALUES ('36', '10', 'tabudi', NULL, '', 'Pending update', 'Pending update', '', 'Married', '', '', '', '2026-07-18 23:57:09', '4ps', NULL, 'RES-2026-00036', NULL, NULL, NULL, NULL, 'active', '2026-07-19 00:49:59', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `residents` VALUES ('37', '11', 'crisostomo ibarra', '1960-02-29', 'Male', 'Pending update', 'Pending update', '', 'Widowed', '', '', '', '2026-07-21 00:23:37', 'senior_citizen', 'assets/uploads/qr/resident_37_6a5e4c4c30d57.png', 'resident:37', NULL, NULL, NULL, NULL, 'active', '2026-07-21 00:35:57', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `residents` VALUES ('38', '12', 'Arsiel Jey P. Bacatin', NULL, NULL, 'Pending update', 'Pending update', NULL, NULL, NULL, NULL, NULL, '2026-07-24 06:26:49', 'regular', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-24 06:26:49', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '0', '0', '0', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `residents` VALUES ('39', NULL, 'coby Arguilles Curicong Jr.', '1995-03-31', 'Male', NULL, NULL, NULL, 'Widowed', NULL, NULL, NULL, '2026-07-24 06:48:31', '4ps', NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-24 07:34:45', NULL, '0', NULL, 'Jr.', 'masbate city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '0', '0', '0', '0', '0', 'coby', 'Arguilles', 'Curicong', 'filipino', NULL, NULL, NULL, NULL, NULL);


CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` VALUES ('1', 'admin', 'Administrator', '2026-07-03 01:04:34');
INSERT INTO `roles` VALUES ('2', 'secretary', 'Barangay Secretary', '2026-07-03 01:04:34');
INSERT INTO `roles` VALUES ('3', 'resident', 'Resident', '2026-07-03 01:04:34');


CREATE TABLE `scan_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) DEFAULT NULL,
  `qr_code_scanned` varchar(255) NOT NULL,
  `scan_result` enum('success','not_found','inactive','expired','error') NOT NULL,
  `scanned_by_user_id` int(11) NOT NULL,
  `scanned_by_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scan_logs_scanned_at` (`scanned_at`),
  KEY `idx_scan_logs_resident_id` (`resident_id`),
  KEY `idx_scan_logs_result` (`scan_result`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `scan_logs` VALUES ('3', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '10.0.110.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-19 02:10:05');
INSERT INTO `scan_logs` VALUES ('4', NULL, '123', 'not_found', '2', 'Barangay Secretary', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-20 16:30:13');
INSERT INTO `scan_logs` VALUES ('5', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-20 16:42:20');
INSERT INTO `scan_logs` VALUES ('6', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-20 16:42:41');
INSERT INTO `scan_logs` VALUES ('7', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0', '2026-07-20 16:42:46');
INSERT INTO `scan_logs` VALUES ('8', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:13:24');
INSERT INTO `scan_logs` VALUES ('9', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:15:18');
INSERT INTO `scan_logs` VALUES ('10', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:18:44');
INSERT INTO `scan_logs` VALUES ('11', NULL, 'resident:36:tabudi', 'not_found', '2', 'Barangay Secretary', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:20:07');
INSERT INTO `scan_logs` VALUES ('12', NULL, 'resident:37:crisostomo%20ibarra', 'not_found', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:28:20');
INSERT INTO `scan_logs` VALUES ('13', NULL, 'resident:36:tabudi', 'not_found', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 00:31:39');
INSERT INTO `scan_logs` VALUES ('14', '37', 'resident:37:crisostomo%20ibarra', 'success', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 01:40:13');
INSERT INTO `scan_logs` VALUES ('15', '36', 'resident:36:tabudi', 'success', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 01:40:53');
INSERT INTO `scan_logs` VALUES ('16', NULL, 'WIFI:S:MNJICSHome_BURAC-5G;T:WPA;P:march241976;H:false;;', 'not_found', '1', 'Administrator', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 OPR/133.0.0.0', '2026-07-21 01:41:39');


CREATE TABLE `schema_version` (
  `version` int(11) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_version` VALUES ('1', '2026-07-24 09:25:40');


CREATE TABLE `session_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` VALUES ('1', 'barangay_name', 'Tumalaytay pro\'s', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('2', 'barangay_address', 'Barangay Hall, Tumalaytay', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('3', 'maintenance_mode', '1', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('4', 'theme', 'light', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('5', 'email_notifications', '0', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('6', 'sms_notifications', '0', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('7', 'barangay_logo', '', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('8', 'officials_signature', '', '2026-07-03 01:04:34', NULL);
INSERT INTO `settings` VALUES ('18', 'email_smtp_host', '', '2026-07-08 18:47:14', NULL);
INSERT INTO `settings` VALUES ('19', 'email_smtp_port', '', '2026-07-08 18:47:14', NULL);
INSERT INTO `settings` VALUES ('20', 'email_smtp_user', '', '2026-07-08 18:47:14', NULL);
INSERT INTO `settings` VALUES ('21', 'email_smtp_pass', '', '2026-07-08 18:47:14', NULL);
INSERT INTO `settings` VALUES ('32', 'hero_background', 'assets/uploads/hero_6a64933f803c8_Acer_Wallpaper_03_5000x2814.jpg', '2026-07-09 00:49:34', NULL);
INSERT INTO `settings` VALUES ('775', 'theme_admin', 'dark', '2026-07-23 05:21:49', NULL);
INSERT INTO `settings` VALUES ('794', 'theme_resident', 'dark', '2026-07-23 05:22:22', NULL);
INSERT INTO `settings` VALUES ('795', 'theme_secretary', 'dark', '2026-07-23 05:23:15', NULL);


CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_logs` VALUES ('1', 'Database backup created: trace_db_backup_2026-07-24_04-54-26.sql', '2026-07-24 10:54:26');


CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'resident',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme_preference` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1', 'Administrator', 'admin@trace.test', '$2y$10$kRrt/TOtsEbO2wPusa2ITeeGXWg6/fRHV26O1Px.jH0WrFvi7NCD.', 'admin', 'active', '2026-07-03 01:04:34', 'light');
INSERT INTO `users` VALUES ('2', 'Barangay Secretary', 'secretary@trace.test', '$2y$10$iehIRkNnFQc6gRvJedXznOXhseGDqT/SuhdJhbJDkwgOqdhTbVVQG', 'secretary', 'active', '2026-07-03 01:04:34', 'dark');
INSERT INTO `users` VALUES ('3', 'Resident User', 'resident@trace.test', '$2y$10$iaeWZQpM56ahp.PiLZ0EPO5hPrb1g67P4wN5Bm544w.iCloPvjri6', 'resident', 'active', '2026-07-03 01:04:34', NULL);
INSERT INTO `users` VALUES ('4', 'tsss', 'ts@trace.test', '$2y$10$Nm62iWm8vSmR8DCD4nMMku3ccRE9XXq96KZzc8VSLf5d/e5ice/dG', 'secretary', 'active', '2026-07-03 01:27:59', NULL);
INSERT INTO `users` VALUES ('8', 'adminako@', 'admin2@gmail.com', '$2y$10$QS7ZYuQDVGQDPHNIB3zOR.FlUAsHYdINEbe7ed9YlxcEFNwPmocUu', 'resident', 'active', '2026-07-17 22:23:54', NULL);
INSERT INTO `users` VALUES ('9', 'yamyam', 'masilangkieth@gmail.com', '$2y$10$5fxNL09HVAaZl.WEZgWhkegtuUR12SSxWM4REgJBTiz3ZmS3axxLi', 'resident', 'active', '2026-07-18 22:15:04', NULL);
INSERT INTO `users` VALUES ('10', 'tabudi', 'fishball@gmail.com', '$2y$10$9MizPF0.UmfZRGljcXbJAuL6Yxd3xL34HJi6EzZKvd9RD8BZAurMi', 'resident', 'active', '2026-07-18 23:57:09', 'light');
INSERT INTO `users` VALUES ('11', 'crisostomo ibarra', 'ibarra@gmail.com', '$2y$10$GSHT5HLzTBR1j/hBV.CLyuH9exCoyZ0xi0xVFHY1JmbdVY.FmXWcu', 'resident', 'active', '2026-07-21 00:23:37', 'dark');
INSERT INTO `users` VALUES ('12', 'Arsiel Jey P. Bacatin', 'Bacatiten@gmail.com', '$2y$10$uhPmgeI4J5swUO/QEBaAHu79B4/Wtg0xCwCSPrd.TMRc28ChCluPa', 'resident', 'active', '2026-07-24 06:26:49', 'dark');
INSERT INTO `users` VALUES ('13', 'Kapitan', 'captain@gmail.com', '$2y$10$oGbGxDr7tVFc7dvwrMZxwuUA8Suy1uTmQQ1/eFuSqXlUNiyrxzam.', 'admin', 'active', '2026-07-24 09:59:04', NULL);


CREATE TABLE `verification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qr_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

