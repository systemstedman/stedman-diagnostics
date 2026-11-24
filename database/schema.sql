-- ============================================================================
-- Stedman Pharmaceuticals Pvt Ltd (Diagnostics Division)
-- Complete MySQL Schema for Bluehost phpMyAdmin Import
-- Compatible with MySQL 5.7+, MySQL 8.0+, and MariaDB 10.3+
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- ----------------------------------------------------------------------------
-- 1. Users Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(200) NOT NULL,
    `role` ENUM('Admin', 'Service Person') NOT NULL DEFAULT 'Service Person',
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Lab Master Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lab_master` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `diagnostic_lab_name` VARCHAR(255) NOT NULL,
    `lab_incharge_contact` VARCHAR(200) DEFAULT NULL,
    `mobile_number` VARCHAR(50) DEFAULT NULL,
    `email_address` VARCHAR(200) DEFAULT NULL,
    `city_location` VARCHAR(150) DEFAULT NULL,
    `full_address` TEXT DEFAULT NULL,
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lab_status_name` (`status`, `diagnostic_lab_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Instrument Master Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `instrument_master` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lab_id` INT UNSIGNED NOT NULL,
    `instrument_name` VARCHAR(255) NOT NULL,
    `manufacturer` VARCHAR(200) DEFAULT NULL,
    `model_number` VARCHAR(150) DEFAULT NULL,
    `serial_number` VARCHAR(150) NOT NULL,
    `installation_date` DATE DEFAULT NULL,
    `warranty_status` VARCHAR(100) DEFAULT NULL,
    `warranty_expiry_date` DATE DEFAULT NULL,
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_instrument_serial` (`serial_number`),
    KEY `idx_instrument_lab_status` (`lab_id`, `status`),
    CONSTRAINT `fk_instrument_lab`
        FOREIGN KEY (`lab_id`) REFERENCES `lab_master` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Service Calls Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_calls` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `call_id` VARCHAR(100) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `lab_id` INT UNSIGNED NOT NULL,
    `instrument_id` INT UNSIGNED DEFAULT NULL,
    `call_type` VARCHAR(50) NOT NULL,
    `problem_reported` TEXT NOT NULL,
    `contact_person` VARCHAR(200) NOT NULL,
    `mobile` VARCHAR(50) NOT NULL,
    `assigned_engineer` VARCHAR(200) DEFAULT NULL,
    `logged_datetime` DATETIME NOT NULL,
    `logged_by` VARCHAR(200) DEFAULT NULL,
    `visit_in_time` DATETIME DEFAULT NULL,
    `visit_out_time` DATETIME DEFAULT NULL,
    `observations` TEXT DEFAULT NULL,
    `actions_taken` TEXT DEFAULT NULL,
    `spares_required` ENUM('Yes', 'No') DEFAULT NULL,
    `spares_replaced` ENUM('Yes', 'No') DEFAULT NULL,
    `spares_status` VARCHAR(100) DEFAULT NULL,
    `spares_details` TEXT DEFAULT NULL,
    `signed_report_url` VARCHAR(500) DEFAULT NULL,
    `closed_by` VARCHAR(200) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_service_calls_call_id` (`call_id`),
    KEY `idx_sc_status` (`status`),
    KEY `idx_sc_call_type_status` (`call_type`, `status`),
    KEY `idx_sc_logged_datetime` (`logged_datetime`),
    KEY `idx_sc_lab_id` (`lab_id`),
    KEY `idx_sc_instrument_id` (`instrument_id`),
    CONSTRAINT `fk_service_calls_lab`
        FOREIGN KEY (`lab_id`) REFERENCES `lab_master` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_service_calls_instrument`
        FOREIGN KEY (`instrument_id`) REFERENCES `instrument_master` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
