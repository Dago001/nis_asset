-- =====================================================================
--  Security hardening migration — 2026-08-27
--  Safe to run repeatedly (uses IF NOT EXISTS / IF EXISTS where supported
--  by MariaDB 10.3+ / MySQL 8.0+).
-- =====================================================================

-- 1. TOTP replay protection: remember the last accepted time-slice.
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `two_factor_last_slice` BIGINT NOT NULL DEFAULT 0
    AFTER `two_factor_enabled`;

-- 2. Saved reports (previously created at runtime by ReportController).
CREATE TABLE IF NOT EXISTS `saved_reports` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `report_name` VARCHAR(255) NOT NULL,
    `report_type` VARCHAR(50)  NOT NULL,
    `parameters`  TEXT,
    `created_by`  INT NOT NULL,
    `created_at`  DATETIME NOT NULL,
    INDEX `idx_saved_reports_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Request log used by the unauthenticated rate limiter.
CREATE TABLE IF NOT EXISTS `request_log` (
    `id`         BIGINT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `url`        VARCHAR(255) DEFAULT NULL,
    `method`     VARCHAR(10)  DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_request_log_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Optional IP allow-list (checked by Middleware when enabled).
CREATE TABLE IF NOT EXISTS `ip_whitelist` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(64) NOT NULL,
    `label`      VARCHAR(120) DEFAULT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Make sure the maintenance / whitelist toggles exist (off by default).
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `data_type`, `setting_group`, `description`)
VALUES
    ('maintenance_mode', '0', 'boolean', 'system', 'When on, only Super Admin / admin can use the site'),
    ('ip_whitelist_enabled', '0', 'boolean', 'system', 'Restrict access to addresses in ip_whitelist');
