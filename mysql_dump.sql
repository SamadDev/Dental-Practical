-- MySQL dump generated from project migrations + SQLite data
SET FOREIGN_KEY_CHECKS=0;

-- Table: patients
CREATE TABLE IF NOT EXISTS `patients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(255) DEFAULT NULL,
  `appointment_date` TEXT DEFAULT NULL,
  `age` INT UNSIGNED DEFAULT NULL,
  `is_smoker` TINYINT(1) NOT NULL DEFAULT 0,
  `medical_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patients_name_index` (`name`(191)),
  KEY `patients_phone_index` (`phone`(191)),
  KEY `patients_is_smoker_index` (`is_smoker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: aqsat_contracts
CREATE TABLE IF NOT EXISTS `aqsat_contracts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `treatment_name` VARCHAR(255) NOT NULL,
  `total_amount` BIGINT UNSIGNED NOT NULL,
  `remaining_balance` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aqsat_patient_status_idx` (`patient_id`,`status`(191)),
  CONSTRAINT `aqsat_contracts_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: visits
CREATE TABLE IF NOT EXISTS `visits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `aqsat_contract_id` BIGINT UNSIGNED DEFAULT NULL,
  `queue_status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `visit_type` VARCHAR(255) NOT NULL DEFAULT 'walk_in',
  `treatment_notes` TEXT DEFAULT NULL,
  `xray_path` VARCHAR(255) DEFAULT NULL,
  `total_cost` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount_paid` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `short_term_debt` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visits_queue_status_idx` (`queue_status`(191)),
  KEY `visits_visit_type_idx` (`visit_type`(191)),
  KEY `visits_created_at_idx` (`created_at`),
  CONSTRAINT `visits_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visits_aqsat_contract_id_foreign` FOREIGN KEY (`aqsat_contract_id`) REFERENCES `aqsat_contracts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: expenses
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_created_at_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- Data inserts (extracted from SQLite)
-- Done: appended INSERTs
