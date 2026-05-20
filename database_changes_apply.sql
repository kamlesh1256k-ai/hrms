-- ============================================================
-- HRMS Database Changes — 14-16 Apr 2026 (Missing items only)
-- ============================================================

-- 1. Create gr_kpi_assignments table
CREATE TABLE IF NOT EXISTS `gr_kpi_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `generation_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `remarks` text DEFAULT NULL,
  `assigned_by` bigint(20) UNSIGNED NOT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add status & submitted_at to gr_kpi_generations
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hrms' AND TABLE_NAME='gr_kpi_generations' AND COLUMN_NAME='status');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `gr_kpi_generations` ADD COLUMN `status` enum('draft','submitted','manager_reviewed','hod_reviewed') NOT NULL DEFAULT 'draft' AFTER `ai_mode`", "SELECT 'status already exists'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hrms' AND TABLE_NAME='gr_kpi_generations' AND COLUMN_NAME='submitted_at');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `gr_kpi_generations` ADD COLUMN `submitted_at` timestamp NULL DEFAULT NULL AFTER `status`", "SELECT 'submitted_at already exists'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Add manager_reviewed_at & hod_reviewed_at to gr_kpi_generations
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hrms' AND TABLE_NAME='gr_kpi_generations' AND COLUMN_NAME='manager_reviewed_at');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `gr_kpi_generations` ADD COLUMN `manager_reviewed_at` timestamp NULL DEFAULT NULL AFTER `submitted_at`", "SELECT 'manager_reviewed_at already exists'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hrms' AND TABLE_NAME='gr_kpi_generations' AND COLUMN_NAME='hod_reviewed_at');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `gr_kpi_generations` ADD COLUMN `hod_reviewed_at` timestamp NULL DEFAULT NULL AFTER `manager_reviewed_at`", "SELECT 'hod_reviewed_at already exists'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Add hod_id & management_id to employees
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hrms' AND TABLE_NAME='employees' AND COLUMN_NAME='hod_id');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `employees` ADD COLUMN `hod_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `reporting_manager_id`", "SELECT 'hod_id already exists'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hrms' AND TABLE_NAME='employees' AND COLUMN_NAME='management_id');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `employees` ADD COLUMN `management_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `hod_id`", "SELECT 'management_id already exists'");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Insert migration records (if not already present)
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2026_04_15_000001_create_gr_kpi_assignments_table', 1),
('2026_04_16_000001_add_status_to_gr_kpi_generations', 1),
('2026_04_16_000002_add_hod_review_to_gr_kpi_generations', 1),
('2026_04_16_000003_add_hod_and_management_to_employees', 1);

SELECT 'All changes applied successfully!' AS result;
