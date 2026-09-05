-- phpMyAdmin SQL Dump
-- Database: `nis_ams`
-- Generation Time: Mar 03, 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =============================================
-- USER MANAGEMENT (COMPLETE RBAC)
-- =============================================

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `nis_number` varchar(50) DEFAULT NULL,
  `rank` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nis_number` (`nis_number`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system_role` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions (junction table)
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  KEY `role_id` (`role_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Roles (junction table)
CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Sessions table
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_record` (`table_name`, `record_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SYSTEM SETTINGS
-- =============================================

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `data_type` varchar(20) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- LOCATION REFERENCE TABLES
-- =============================================

CREATE TABLE `zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_code` varchar(10) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zone_code` (`zone_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(100) NOT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `state_name` (`state_name`),
  KEY `zone_id` (`zone_id`),
  CONSTRAINT `states_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lgas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lga_name` varchar(100) NOT NULL,
  `state_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lga_state` (`lga_name`, `state_id`),
  KEY `state_id` (`state_id`),
  CONSTRAINT `lgas_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `command_name` varchar(200) NOT NULL,
  `command_type` enum('State Command','Zonal Command','Formation','Directorate','Training School','Border Command','Airport Command','Seaport Command') DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_officer` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `command_name` (`command_name`),
  KEY `zone_id` (`zone_id`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  CONSTRAINT `commands_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `commands_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `commands_ibfk_3` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ASSET CATEGORIES
-- =============================================

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `asset_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `asset_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DOCUMENTS TABLE (Centralized)
-- =============================================

CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_type` varchar(50) NOT NULL COMMENT 'land, building, ict, vehicle, etc.',
  `asset_id` int(11) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_mime` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_type_id` (`asset_type`, `asset_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- LAND ASSETS
-- =============================================

CREATE TABLE `land_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `ownership_type` enum('FGN','State','Private') NOT NULL,
  `title_holder` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `size` decimal(10,2) DEFAULT NULL,
  `size_unit` enum('m²','square-feet','hectares','acre') DEFAULT NULL,
  `survey_plan_no` varchar(100) DEFAULT NULL,
  `certificate_of_occupancy_no` varchar(100) DEFAULT NULL,
  `purpose_use` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `encumbrance` text DEFAULT NULL,
  `status` enum('Developed','Undeveloped','Fenced','Not Fenced','Under Litigation') DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `land_assets_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `land_assets_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `land_assets_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `land_assets_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `land_assets_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- BUILDING ASSETS
-- =============================================

CREATE TABLE `building_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `building_name` varchar(255) NOT NULL,
  `building_type` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `ownership_type` enum('FGN','State','Private') NOT NULL,
  `purpose_function` varchar(100) DEFAULT NULL,
  `land_id` int(11) DEFAULT NULL COMMENT 'Link to land asset if on owned land',
  `construction_contractor` varchar(255) DEFAULT NULL,
  `contract_sum` decimal(15,2) DEFAULT NULL,
  `date_awarded` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `date_occupied` date DEFAULT NULL,
  `condition_status` varchar(50) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `floor_count` int(11) DEFAULT NULL,
  `total_area` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `land_id` (`land_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `building_assets_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `building_assets_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `building_assets_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `building_assets_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `building_assets_ibfk_5` FOREIGN KEY (`land_id`) REFERENCES `land_assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `building_assets_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- RENTED PROPERTIES
-- =============================================

CREATE TABLE `rented_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `property_address` text NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `owner_lessor_name` varchar(255) NOT NULL,
  `owner_contact` varchar(100) DEFAULT NULL,
  `owner_phone` varchar(20) DEFAULT NULL,
  `owner_email` varchar(100) DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `annual_rent` decimal(15,2) DEFAULT NULL,
  `funding_source` varchar(100) DEFAULT NULL,
  `lease_agreement_ref` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `rented_properties_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rented_properties_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rented_properties_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rented_properties_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rented_properties_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ONGOING PROJECTS
-- =============================================

CREATE TABLE `ongoing_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `project_type` varchar(100) NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `contractor` varchar(255) DEFAULT NULL,
  `contract_sum` decimal(15,2) DEFAULT NULL,
  `date_awarded` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `physical_progress` decimal(5,2) DEFAULT 0.00,
  `financial_progress` decimal(5,2) DEFAULT 0.00,
  `source_funding` varchar(100) NOT NULL,
  `supervising_officer` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'In Progress',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `ongoing_projects_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ongoing_projects_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ongoing_projects_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ongoing_projects_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ongoing_projects_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- MOVABLE ASSETS
-- =============================================

CREATE TABLE `movable_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `asset_type` varchar(100) NOT NULL,
  `make_model` varchar(255) DEFAULT NULL,
  `capacity_specification` text DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_value` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `condition_status` varchar(50) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `custodian_name` varchar(255) DEFAULT NULL,
  `custodian_rank` varchar(50) DEFAULT NULL,
  `custodian_nis` varchar(50) DEFAULT NULL,
  `warranty_info` text DEFAULT NULL,
  `maintenance_schedule` text DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `movable_assets_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movable_assets_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movable_assets_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movable_assets_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movable_assets_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ICT ASSETS
-- =============================================

CREATE TABLE `ict_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `asset_description` varchar(255) NOT NULL,
  `asset_category` enum('Hardware','Software','Network','Server','Peripheral','Other') DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `model_version` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_value` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ownership_type` enum('FGN','Donor','Leased') DEFAULT NULL,
  `current_status` varchar(50) DEFAULT NULL,
  `warranty_period` varchar(50) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `maintenance_provider` varchar(255) DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `responsible_officer` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `software_license` varchar(100) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `ict_assets_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ict_assets_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ict_assets_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ict_assets_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ict_assets_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FLEET MANAGEMENT - VEHICLES
-- =============================================

CREATE TABLE `vehicle_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `use_purpose` varchar(100) DEFAULT NULL,
  `ownership_type` enum('FGN-Owned','Donor','Leased') DEFAULT NULL,
  `vehicle_type` varchar(100) DEFAULT NULL,
  `make_manufacturer` varchar(100) DEFAULT NULL,
  `model_year` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `vin_chassis_number` varchar(100) DEFAULT NULL,
  `engine_number` varchar(100) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `engine_capacity` varchar(50) DEFAULT NULL,
  `fuel_type` enum('Petrol','Diesel') DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `acquisition_type` enum('Purchase','Transfer','Donation','Lease') DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `purchase_value` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `assigned_officer` varchar(255) DEFAULT NULL,
  `assigned_rank` varchar(50) DEFAULT NULL,
  `assigned_nis` varchar(50) DEFAULT NULL,
  `operational_status` enum('Active','In Repair','Grounded','Awaiting Disposal') DEFAULT NULL,
  `condition` enum('Excellent','Good','Fair','Poor') DEFAULT NULL,
  `insurance_status` enum('Valid','Expired','Not Insured') DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `last_maintenance_cost` decimal(10,2) DEFAULT NULL,
  `maintenance_vendor` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `vehicle_assets_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_assets_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_assets_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_assets_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_assets_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Similar tables for aircraft, marine, motorcycles (structure condensed for brevity)

CREATE TABLE `aircraft_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) NOT NULL,
  `use_purpose` varchar(100) DEFAULT NULL,
  `ownership_type` enum('FGN-Owned','Donor','Leased') DEFAULT NULL,
  `aircraft_type` varchar(100) DEFAULT NULL,
  `model_manufacturer` varchar(100) DEFAULT NULL,
  `year_manufacture` int(4) DEFAULT NULL,
  `tail_number` varchar(50) DEFAULT NULL,
  `chassis_serial` varchar(100) DEFAULT NULL,
  `engine_type` varchar(100) DEFAULT NULL,
  `flight_hours` decimal(10,1) DEFAULT 0,
  `operational_status` enum('Operational','Maintenance','Grounded') DEFAULT NULL,
  `acquisition_type` enum('Purchase','Transfer','Donation','Lease') DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `capital_value` decimal(15,2) DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `assigned_unit_pilot` varchar(255) DEFAULT NULL,
  `insurance_type` varchar(50) DEFAULT NULL,
  `insurance_status` enum('Valid','Expired','Not Insured') DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_overhaul` date DEFAULT NULL,
  `installed_equipment` text DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `lga_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `command_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `state_id` (`state_id`),
  KEY `lga_id` (`lga_id`),
  KEY `zone_id` (`zone_id`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `aircraft_assets_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aircraft_assets_ibfk_2` FOREIGN KEY (`lga_id`) REFERENCES `lgas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aircraft_assets_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aircraft_assets_ibfk_4` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aircraft_assets_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- WEAPONS MANAGEMENT
-- =============================================

CREATE TABLE `weapon_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_calibre` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `weapon_calibres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calibre_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calibre_name` (`calibre_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `weapons_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weapon_id` varchar(50) NOT NULL,
  `weapon_type_id` int(11) DEFAULT NULL,
  `weapon_type_other` varchar(100) DEFAULT NULL,
  `make_model` varchar(255) NOT NULL,
  `serial_no` varchar(100) NOT NULL,
  `calibre_id` int(11) DEFAULT NULL,
  `calibre_other` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `condition` varchar(100) DEFAULT NULL,
  `current_location` varchar(100) DEFAULT NULL,
  `current_location_other` varchar(100) DEFAULT NULL,
  `custodian` varchar(255) DEFAULT NULL,
  `custodian_rank` varchar(50) DEFAULT NULL,
  `custodian_nis` varchar(50) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `weapon_id` (`weapon_id`),
  UNIQUE KEY `serial_no` (`serial_no`),
  KEY `weapon_type_id` (`weapon_type_id`),
  KEY `calibre_id` (`calibre_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `weapons_inventory_ibfk_1` FOREIGN KEY (`weapon_type_id`) REFERENCES `weapon_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `weapons_inventory_ibfk_2` FOREIGN KEY (`calibre_id`) REFERENCES `weapon_calibres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `weapons_inventory_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- AMMUNITION MANAGEMENT
-- =============================================

CREATE TABLE `ammunition_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ammo_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ammo_type` (`ammo_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ammunition_calibres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calibre` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `rounds_per_unit` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calibre` (`calibre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ammunition_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ammo_id` varchar(50) NOT NULL,
  `ammo_type_id` int(11) DEFAULT NULL,
  `ammo_type_other` varchar(100) DEFAULT NULL,
  `calibre_id` int(11) DEFAULT NULL,
  `calibre_other` varchar(50) DEFAULT NULL,
  `storage_form` varchar(100) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `storage_location_other` varchar(100) DEFAULT NULL,
  `quantity_received` int(11) DEFAULT 0,
  `quantity_issued` int(11) DEFAULT 0,
  `balance` int(11) DEFAULT 0,
  `condition` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `date_manufactured` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ammo_id` (`ammo_id`),
  KEY `ammo_type_id` (`ammo_type_id`),
  KEY `calibre_id` (`calibre_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_expiry` (`expiry_date`),
  KEY `idx_balance` (`balance`),
  CONSTRAINT `ammunition_inventory_ibfk_1` FOREIGN KEY (`ammo_type_id`) REFERENCES `ammunition_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ammunition_inventory_ibfk_2` FOREIGN KEY (`calibre_id`) REFERENCES `ammunition_calibres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ammunition_inventory_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- REQUISITIONS
-- =============================================

CREATE TABLE `requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_number` varchar(50) NOT NULL,
  `requisition_date` date NOT NULL,
  `requisition_type` enum('Weapon','Ammunition','Both') DEFAULT NULL,
  `priority_level` enum('Low','Medium','High','Urgent') NOT NULL,
  `requesting_officer_id` int(11) DEFAULT NULL,
  `requesting_officer_name` varchar(255) NOT NULL,
  `requesting_rank` varchar(50) NOT NULL,
  `requesting_nis` varchar(50) NOT NULL,
  `requesting_phone` varchar(20) NOT NULL,
  `requesting_command_id` int(11) DEFAULT NULL,
  `justification` text NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','Rejected','Issued','Partially Issued','Completed') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `approval_remarks` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issue_date` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requisition_number` (`requisition_number`),
  KEY `requesting_officer_id` (`requesting_officer_id`),
  KEY `requesting_command_id` (`requesting_command_id`),
  KEY `approved_by` (`approved_by`),
  KEY `issued_by` (`issued_by`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  CONSTRAINT `requisitions_ibfk_1` FOREIGN KEY (`requesting_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisitions_ibfk_2` FOREIGN KEY (`requesting_command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisitions_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisitions_ibfk_4` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisitions_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `item_type` enum('Weapon','Ammunition','Non-Lethal') NOT NULL,
  `weapon_type_id` int(11) DEFAULT NULL,
  `weapon_type_other` varchar(100) DEFAULT NULL,
  `ammo_type_id` int(11) DEFAULT NULL,
  `ammo_type_other` varchar(100) DEFAULT NULL,
  `calibre_id` int(11) DEFAULT NULL,
  `calibre_other` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `purpose_other` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `weapon_type_id` (`weapon_type_id`),
  KEY `ammo_type_id` (`ammo_type_id`),
  KEY `calibre_id` (`calibre_id`),
  CONSTRAINT `requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requisition_items_ibfk_2` FOREIGN KEY (`weapon_type_id`) REFERENCES `weapon_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisition_items_ibfk_3` FOREIGN KEY (`ammo_type_id`) REFERENCES `ammunition_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requisition_items_ibfk_4` FOREIGN KEY (`calibre_id`) REFERENCES `ammunition_calibres` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ISSUE LOGS
-- =============================================

CREATE TABLE `weapon_issue_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) DEFAULT NULL,
  `weapon_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `officer_name` varchar(100) NOT NULL,
  `officer_rank` varchar(50) NOT NULL,
  `officer_nis` varchar(50) DEFAULT NULL,
  `unit` varchar(100) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `approved_by` varchar(100) NOT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `return_condition` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Issued',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `weapon_id` (`weapon_id`),
  KEY `issued_by` (`issued_by`),
  KEY `idx_status` (`status`),
  CONSTRAINT `weapon_issue_log_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `weapon_issue_log_ibfk_2` FOREIGN KEY (`weapon_id`) REFERENCES `weapons_inventory` (`id`) ON DELETE CASCADE,
  CONSTRAINT `weapon_issue_log_ibfk_3` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ammunition_issue_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) DEFAULT NULL,
  `ammo_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `units_issued` int(11) NOT NULL,
  `rounds_issued` int(11) NOT NULL,
  `officer_name` varchar(100) NOT NULL,
  `officer_rank` varchar(50) NOT NULL,
  `officer_nis` varchar(50) DEFAULT NULL,
  `unit` varchar(100) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `approved_by` varchar(100) NOT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `ammo_id` (`ammo_id`),
  KEY `issued_by` (`issued_by`),
  CONSTRAINT `ammunition_issue_log_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ammunition_issue_log_ibfk_2` FOREIGN KEY (`ammo_id`) REFERENCES `ammunition_inventory` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ammunition_issue_log_ibfk_3` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- RETURNS
-- =============================================

CREATE TABLE `returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) NOT NULL,
  `return_date` date NOT NULL,
  `return_type` enum('Weapon','Ammunition','Both') DEFAULT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `returning_officer_name` varchar(100) NOT NULL,
  `returning_rank` varchar(50) NOT NULL,
  `returning_nis` varchar(50) NOT NULL,
  `returning_unit` varchar(100) NOT NULL,
  `received_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Processed','Verified','Completed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `requisition_id` (`requisition_id`),
  KEY `received_by` (`received_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `return_weapons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `weapon_id` int(11) NOT NULL,
  `weapon_type` varchar(100) DEFAULT NULL,
  `condition` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `weapon_id` (`weapon_id`),
  CONSTRAINT `return_weapons_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_weapons_ibfk_2` FOREIGN KEY (`weapon_id`) REFERENCES `weapons_inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `return_ammunition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `ammo_id` int(11) NOT NULL,
  `rounds_returned` int(11) DEFAULT 0,
  `rounds_used` int(11) DEFAULT 0,
  `rounds_balance` int(11) DEFAULT 0,
  `condition` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `ammo_id` (`ammo_id`),
  CONSTRAINT `return_ammunition_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_ammunition_ibfk_2` FOREIGN KEY (`ammo_id`) REFERENCES `ammunition_inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUARTERLY AUDITS
-- =============================================

CREATE TABLE `quarterly_audits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_number` varchar(50) NOT NULL,
  `audit_date` date NOT NULL,
  `quarter` varchar(10) NOT NULL,
  `year` int(4) NOT NULL,
  `audit_officer` varchar(100) NOT NULL,
  `auditor_rank` varchar(50) NOT NULL,
  `auditor_nis` varchar(50) NOT NULL,
  `unit` varchar(100) NOT NULL,
  `audit_location` varchar(200) NOT NULL,
  `command_id` int(11) DEFAULT NULL,
  `audit_remarks` text DEFAULT NULL,
  `total_weapons_audited` int(11) DEFAULT 0,
  `total_ammunition_audited` int(11) DEFAULT 0,
  `weapons_with_variance` int(11) DEFAULT 0,
  `ammunition_with_variance` int(11) DEFAULT 0,
  `total_missing_weapons` int(11) DEFAULT 0,
  `audit_conclusion` text DEFAULT NULL,
  `recommending_officer` varchar(100) DEFAULT NULL,
  `approving_officer` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Submitted','Reviewed','Approved') DEFAULT 'Submitted',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_number` (`audit_number`),
  KEY `command_id` (`command_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `quarterly_audits_ibfk_1` FOREIGN KEY (`command_id`) REFERENCES `commands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quarterly_audits_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_weapons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `weapon_id` int(11) NOT NULL,
  `weapon_type` varchar(100) DEFAULT NULL,
  `make_model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `system_status` varchar(50) DEFAULT NULL,
  `physical_status` varchar(50) DEFAULT NULL,
  `variance` varchar(20) DEFAULT '0',
  `variance_value` int(11) DEFAULT 0,
  `condition` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  KEY `weapon_id` (`weapon_id`),
  CONSTRAINT `audit_weapons_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `quarterly_audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_weapons_ibfk_2` FOREIGN KEY (`weapon_id`) REFERENCES `weapons_inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_ammunition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `ammo_id` int(11) NOT NULL,
  `ammo_type` varchar(100) DEFAULT NULL,
  `calibre` varchar(50) DEFAULT NULL,
  `system_units` int(11) DEFAULT 0,
  `physical_units` int(11) DEFAULT 0,
  `variance` varchar(20) DEFAULT '0',
  `variance_value` int(11) DEFAULT 0,
  `condition` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  KEY `ammo_id` (`ammo_id`),
  CONSTRAINT `audit_ammunition_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `quarterly_audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_ammunition_ibfk_2` FOREIGN KEY (`ammo_id`) REFERENCES `ammunition_inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_missing_weapons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `arm_type` varchar(100) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `last_known_location` varchar(200) DEFAULT NULL,
  `date_missing` date DEFAULT NULL,
  `reported_by` varchar(100) DEFAULT NULL,
  `investigation_status` varchar(50) DEFAULT 'Reported',
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`),
  CONSTRAINT `audit_missing_weapons_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `quarterly_audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- REPORTS
-- =============================================

CREATE TABLE `saved_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `file_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `saved_reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Insert default zones
INSERT INTO `zones` (`zone_code`, `zone_name`) VALUES
('HQ', 'Headquarters'),
('A', 'Zone A - Lagos'),
('B', 'Zone B - Kaduna'),
('C', 'Zone C - Bauchi'),
('D', 'Zone D - Minna'),
('E', 'Zone E - Owerri'),
('F', 'Zone F - Ibadan'),
('G', 'Zone G - Benin City'),
('H', 'Zone H - Makurdi');

-- Insert default permissions
INSERT INTO `permissions` (`permission_key`, `module`, `description`) VALUES
-- Dashboard
('dashboard.view', 'Dashboard', 'View Dashboard'),
-- Land Assets
('land.view', 'Land Assets', 'View Land Assets'),
('land.create', 'Land Assets', 'Create Land Assets'),
('land.edit', 'Land Assets', 'Edit Land Assets'),
('land.delete', 'Land Assets', 'Delete Land Assets'),
-- Buildings
('building.view', 'Buildings', 'View Buildings'),
('building.create', 'Buildings', 'Create Buildings'),
('building.edit', 'Buildings', 'Edit Buildings'),
('building.delete', 'Buildings', 'Delete Buildings'),
-- Rented Properties
('rented.view', 'Rented Properties', 'View Rented Properties'),
('rented.create', 'Rented Properties', 'Create Rented Properties'),
('rented.edit', 'Rented Properties', 'Edit Rented Properties'),
('rented.delete', 'Rented Properties', 'Delete Rented Properties'),
-- Movable Assets
('movable.view', 'Movable Assets', 'View Movable Assets'),
('movable.create', 'Movable Assets', 'Create Movable Assets'),
('movable.edit', 'Movable Assets', 'Edit Movable Assets'),
('movable.delete', 'Movable Assets', 'Delete Movable Assets'),
-- ICT Assets
('ict.view', 'ICT Assets', 'View ICT Assets'),
('ict.create', 'ICT Assets', 'Create ICT Assets'),
('ict.edit', 'ICT Assets', 'Edit ICT Assets'),
('ict.delete', 'ICT Assets', 'Delete ICT Assets'),
-- Fleet
('fleet.view', 'Fleet', 'View Fleet Assets'),
('fleet.create', 'Fleet', 'Create Fleet Assets'),
('fleet.edit', 'Fleet', 'Edit Fleet Assets'),
('fleet.delete', 'Fleet', 'Delete Fleet Assets'),
-- Weapons
('weapons.view', 'Weapons', 'View Weapons Inventory'),
('weapons.create', 'Weapons', 'Add Weapons'),
('weapons.edit', 'Weapons', 'Edit Weapons'),
('weapons.delete', 'Weapons', 'Delete Weapons'),
-- Ammunition
('ammunition.view', 'Ammunition', 'View Ammunition'),
('ammunition.create', 'Ammunition', 'Add Ammunition'),
('ammunition.edit', 'Ammunition', 'Edit Ammunition'),
('ammunition.delete', 'Ammunition', 'Delete Ammunition'),
-- Requisitions
('requisition.view', 'Requisitions', 'View Requisitions'),
('requisition.create', 'Requisitions', 'Create Requisitions'),
('requisition.edit', 'Requisitions', 'Edit Requisitions'),
('requisition.approve', 'Requisitions', 'Approve Requisitions'),
-- Audit
('audit.view', 'Audit', 'View Audits'),
('audit.create', 'Audit', 'Create Audits'),
('audit.approve', 'Audit', 'Approve Audits'),
-- Reports
('reports.view', 'Reports', 'View Reports'),
('reports.export', 'Reports', 'Export Reports'),
-- Admin
('users.manage', 'Admin', 'Manage Users'),
('roles.manage', 'Admin', 'Manage Roles'),
('settings.manage', 'Admin', 'Manage System Settings');

-- Insert default roles
INSERT INTO `roles` (`id`, `role_name`, `description`, `is_system_role`) VALUES
(1, 'Super Admin Officer', 'Overall System Administrator', 1),
(2, 'HQ Sectional Supervisor', 'Sectional Heads at Headquarters', 1),
(3, 'HQ Vetting Officer', 'Data Vetting Officers at HQ', 1),
(4, 'Command Approval Officer', 'Heads of various commands', 1),
(5, 'Command Data Entry Officer', 'Officers at command/unit level', 1),
(6, 'Armorer', 'Officer managing weapons and ammunition inventory', 1),
(7, 'Command Armorer', 'Armorer restricted to their command weapons and ammunition', 1),
(8, 'HQ Armorer', 'HQ Armorer with visibility of all weapons and ammunition service-wide', 1),
(10, 'admin', 'System Administrator with limited privileges (no User Management)', 0),
(11, 'CGIS', 'Comptroller General - Analytical Dashboard Access Only', 0);

-- Insert default admin user (password: Admin@123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `nis_number`, `rank`, `is_active`) VALUES
('admin', 'admin@immigration.gov.ng', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'NIS0001', 'ACI', 1);

-- Assign super admin role to admin user
INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_by`) VALUES (1, 1, 1);

-- Insert default weapon types
INSERT INTO `weapon_types` (`type_name`) VALUES
('G3 Rifle'), ('AR70 Rifle'), ('AK-47 Rifle'), ('Galil Rifle'), ('LAR Rifle'),
('SMG'), ('FN Rifle'), ('AK 81-1 Rifle'), ('GF98-9 Rifle'), ('AK 103 Rifle'),
('X95 TAVOR'), ('Pistol Baretta'), ('Dicon Pistol'), ('Stone Pistol'), ('HP Pistol'),
('CZ Pistol'), ('CF Pistol'), ('Shotgun'), ('Machine Gun'), ('Grenade Launcher'),
('Sniper Rifle');

-- Insert default calibres
INSERT INTO `weapon_calibres` (`calibre_name`) VALUES
('7.62x51mm'), ('7.62x39mm'), ('5.56x45mm'), ('9x19mm'), ('12 Gauge'),
('.45 ACP'), ('5.7x28mm'), ('.22 LR'), ('7.62x54mmR'), ('.50 BMG');

-- Insert default ammunition types
INSERT INTO `ammunition_types` (`ammo_type`) VALUES
('Live'), ('Blank'), ('Tracer'), ('Armor Piercing'), ('Training'),
('Rubber Bullet'), ('Bean Bag'), ('Pepper Ball'), ('Paint Marker');

-- Insert default ammunition calibres
INSERT INTO `ammunition_calibres` (`calibre`, `rounds_per_unit`) VALUES
('7.62x51mm', 30), ('7.62x39mm', 30), ('5.56x45mm', 30), ('9x19mm', 30),
('12 Gauge', 25), ('.45 ACP', 30), ('5.7x28mm', 30), ('37mm', 5), ('40mm', 5);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;