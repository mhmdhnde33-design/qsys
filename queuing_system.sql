-- ============================================================
-- DB_INIT.sql
-- توحيد تهيئة قاعدة البيانات الخاصة بالمشروع
-- الهدف: إنشاء/تهيئة كل ما يحتاجه المشروع داخل قاعدة واحدة queuing_system
-- 
-- التشغيل (على MySQL/MariaDB):
-- 1) ادخل على phpMyAdmin أو من سطر الأوامر كـ root
-- 2) نفّذ هذا الملف
-- ============================================================

-- أنشئ قاعدة البيانات أولاً (لو لم تكن موجودة)
CREATE DATABASE IF NOT EXISTS `queuing_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `queuing_system`;

-- ------------------------------------------------------------
-- 1) جداول النظام الأساسية
-- ------------------------------------------------------------
-- تمت المحافظة على محتوى queuing_system.sql كما هو.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Table structure for table `counters`
CREATE TABLE IF NOT EXISTS `counters` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `service_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`service_types`)),
  `is_online` tinyint(1) DEFAULT 1,
  `current_customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `counters` (`id`, `name`, `service_types`, `is_online`, `current_customer_id`) VALUES
(1, 'Counter 1', '["general", "payment", "inquiry"]', 1, NULL),
(2, 'Counter 2', '["technical", "support"]', 1, NULL),
(3, 'Counter 3', '["general", "payment"]', 1, NULL)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  service_types = VALUES(service_types),
  is_online = VALUES(is_online),
  current_customer_id = VALUES(current_customer_id);

-- Table structure for table `customers`
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL,
  `queue_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `status` enum('waiting','serving','completed','cancelled') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `called_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `display_settings`
CREATE TABLE IF NOT EXISTS `display_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT 'Customer Service',
  `welcome_message` text DEFAULT NULL,
  `refresh_interval` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `display_settings` (`id`, `company_name`, `welcome_message`, `refresh_interval`) VALUES
(1, 'Customer Service Center', 'Welcome to our Service Center', 10)
ON DUPLICATE KEY UPDATE
  company_name = VALUES(company_name),
  welcome_message = VALUES(welcome_message),
  refresh_interval = VALUES(refresh_interval);

-- Indexes
ALTER TABLE `counters`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `counters`
  ADD KEY `current_customer_id` (`current_customer_id`);

ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `customers`
  ADD UNIQUE KEY `queue_number` (`queue_number`);

ALTER TABLE `display_settings`
  ADD PRIMARY KEY (`id`);

-- Auto increment
ALTER TABLE `counters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `display_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign keys
ALTER TABLE `counters`
  ADD CONSTRAINT `counters_ibfk_1` FOREIGN KEY (`current_customer_id`) REFERENCES `customers` (`id`);

COMMIT;

-- ------------------------------------------------------------
-- 2) جدول employees
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `counter_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_employees_username` (`username`),
  KEY `idx_employees_counter_id` (`counter_id`),
  CONSTRAINT `fk_employees_counter` FOREIGN KEY (`counter_id`) REFERENCES `counters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 3) Migration: الدواوين وربطها
-- ------------------------------------------------------------

-- ملاحظة مهمة:
-- الخطأ السابق: Duplicate key name 'code'
-- السبب: تم تعريف UNIQUE مرتين على نفس العمود (مرة ضمن تعريف العمود `code` وأخرى UNIQUE KEY `code`).
-- الحل: نزيل UNIQUE KEY الثانية ونترك UNIQUE داخل تعريف العمود فقط.

CREATE TABLE IF NOT EXISTS `diwan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `name_ar` varchar(100) NOT NULL,
  `name_en` varchar(100),
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `diwan` (`code`, `name_ar`, `name_en`) VALUES
('public_attorney', 'ديوان المحامي العام', 'Public Attorney Office'),
('public_prosecution', 'ديوان الكاتب بالعدل', 'Public Prosecutor Office'),
('sharia', 'ديوان الشرعية', 'Sharia Court'),
('civil_beginning', 'ديوان البداية المدنية', 'Civil Court'),
('investigation', 'ديوان التحقيق', 'Investigation Office'),
('penal_reconciliation', 'ديوان صلح الجزاء', 'Penal Reconciliation Court');

-- إضافة أعمدة الربط في customers
ALTER TABLE `customers` ADD COLUMN IF NOT EXISTS `diwan_code` varchar(50) NULL AFTER `service_type`;
ALTER TABLE `customers` ADD COLUMN IF NOT EXISTS `diwan_name_ar` varchar(100) NULL AFTER `diwan_code`;

-- إضافة أعمدة الربط في counters
ALTER TABLE `counters` ADD COLUMN IF NOT EXISTS `primary_diwan_code` varchar(50) NULL AFTER `service_types`;
ALTER TABLE `counters` ADD COLUMN IF NOT EXISTS `diwan_name_ar` varchar(100) NULL AFTER `primary_diwan_code`;

-- Indexes للأداء
CREATE INDEX IF NOT EXISTS `idx_customers_service_type` ON `customers` (`service_type`);
CREATE INDEX IF NOT EXISTS `idx_customers_diwan_code` ON `customers` (`diwan_code`);
CREATE INDEX IF NOT EXISTS `idx_customers_status` ON `customers` (`status`);
CREATE INDEX IF NOT EXISTS `idx_customers_created_at` ON `customers` (`created_at`);
CREATE INDEX IF NOT EXISTS `idx_counters_primary_diwan` ON `counters` (`primary_diwan_code`);

-- تحديث البيانات الموجودة لربطها بالدواوين
UPDATE `counters` SET `primary_diwan_code` = 'public_attorney', `diwan_name_ar` = 'ديوان المحامي العام' WHERE `id` = 1;
UPDATE `counters` SET `primary_diwan_code` = 'public_prosecution', `diwan_name_ar` = 'ديوان الكاتب بالعدل' WHERE `id` = 2;
UPDATE `counters` SET `primary_diwan_code` = 'sharia', `diwan_name_ar` = 'ديوان الشرعية' WHERE `id` = 3;



