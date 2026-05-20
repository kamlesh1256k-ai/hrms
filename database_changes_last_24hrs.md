# Database Changes — Recent (14-16 Apr 2026)

> Source: `hrms (12).sql` dump — migrations #180 to #185

---

## PART A: NEW TABLES CREATED (14-15 Apr 2026)

---

### 1. `performance_cycles` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `name` | VARCHAR(255) | — | No |
| `start_date` | DATE | — | No |
| `end_date` | DATE | — | No |
| `goal_deadline` | DATE | NULL | Yes |
| `self_review_start` | DATE | NULL | Yes |
| `self_review_end` | DATE | NULL | Yes |
| `manager_review_start` | DATE | NULL | Yes |
| `manager_review_end` | DATE | NULL | Yes |
| `head_review_start` | DATE | NULL | Yes |
| `head_review_end` | DATE | NULL | Yes |
| `calibration_start` | DATE | NULL | Yes |
| `calibration_end` | DATE | NULL | Yes |
| `status` | ENUM('draft','active','review','calibration','completed') | 'draft' | No |
| `rating_scale` | VARCHAR(20) | '1-5' | No |
| `settings_json` | LONGTEXT (JSON) | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 2. `gr_missions` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `cycle_id` | BIGINT UNSIGNED | — | No |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `title` | VARCHAR(255) | — | No |
| `description` | TEXT | NULL | Yes |
| `kpi` | VARCHAR(255) | NULL | Yes |
| `weightage` | DECIMAL(5,2) | 0.00 | No |
| `deadline` | DATE | NULL | Yes |
| `status` | ENUM('pending','in_progress','completed','cancelled') | 'pending' | No |
| `approval` | ENUM('pending','approved','rejected') | 'pending' | No |
| `approved_by` | BIGINT UNSIGNED | NULL | Yes |
| `approved_at` | TIMESTAMP | NULL | Yes |
| `manager_remarks` | TEXT | NULL | Yes |
| `progress` | INT | 0 | No |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 3. `gr_reviews` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `cycle_id` | BIGINT UNSIGNED | — | No |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `review_type` | ENUM('self','manager','head','management') | — | No |
| `reviewer_id` | BIGINT UNSIGNED | — | No |
| `rating` | DECIMAL(3,1) | NULL | Yes |
| `ratings_json` | LONGTEXT (JSON) | NULL | Yes |
| `strengths` | TEXT | NULL | Yes |
| `improvements` | TEXT | NULL | Yes |
| `comments` | TEXT | NULL | Yes |
| `status` | ENUM('draft','submitted') | 'draft' | No |
| `submitted_at` | TIMESTAMP | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 4. `gr_ratings` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `cycle_id` | BIGINT UNSIGNED | — | No |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `self_rating` | DECIMAL(3,1) | NULL | Yes |
| `manager_rating` | DECIMAL(3,1) | NULL | Yes |
| `head_rating` | DECIMAL(3,1) | NULL | Yes |
| `final_rating` | DECIMAL(3,1) | NULL | Yes |
| `grade` | VARCHAR(20) | NULL | Yes |
| `is_calibrated` | TINYINT(1) | 0 | No |
| `is_frozen` | TINYINT(1) | 0 | No |
| `calibration_notes` | TEXT | NULL | Yes |
| `calibrated_by` | BIGINT UNSIGNED | NULL | Yes |
| `frozen_at` | TIMESTAMP | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 5. `gr_shoutouts` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `from_employee_id` | BIGINT UNSIGNED | — | No |
| `to_employee_id` | BIGINT UNSIGNED | — | No |
| `message` | TEXT | — | No |
| `badge` | VARCHAR(50) | NULL | Yes |
| `cycle_id` | BIGINT UNSIGNED | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 6. `gr_sync_ups` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `cycle_id` | BIGINT UNSIGNED | NULL | Yes |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `manager_id` | BIGINT UNSIGNED | — | No |
| `meeting_date` | DATE | — | No |
| `notes` | TEXT | NULL | Yes |
| `discussion_points` | LONGTEXT (JSON) | NULL | Yes |
| `action_items` | LONGTEXT (JSON) | NULL | Yes |
| `status` | ENUM('scheduled','completed','cancelled') | 'scheduled' | No |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 7. `gr_comeback_plans` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `assigned_by` | BIGINT UNSIGNED | — | No |
| `cycle_id` | BIGINT UNSIGNED | NULL | Yes |
| `title` | VARCHAR(255) | — | No |
| `issues` | TEXT | NULL | Yes |
| `action_steps` | LONGTEXT (JSON) | NULL | Yes |
| `start_date` | DATE | — | No |
| `end_date` | DATE | — | No |
| `status` | ENUM('active','on_track','at_risk','completed','failed') | 'active' | No |
| `final_remarks` | TEXT | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 8. `gr_increments` (Migration #180: `2026_04_14_000001_create_growth_review_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `cycle_id` | BIGINT UNSIGNED | — | No |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `rating_id` | BIGINT UNSIGNED | NULL | Yes |
| `old_ctc` | DECIMAL(12,2) | — | No |
| `new_ctc` | DECIMAL(12,2) | — | No |
| `increment_pct` | DECIMAL(5,2) | 0.00 | No |
| `increment_amount` | DECIMAL(12,2) | 0.00 | No |
| `effective_date` | DATE | — | No |
| `status` | ENUM('proposed','approved','applied','rejected') | 'proposed' | No |
| `approved_by` | BIGINT UNSIGNED | NULL | Yes |
| `synced_to_payroll` | TINYINT(1) | 0 | No |
| `letter_generated` | TINYINT(1) | 0 | No |
| `remarks` | TEXT | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 9. `gr_kpi_generations` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `job_role` | VARCHAR(255) | — | No |
| `department` | VARCHAR(255) | NULL | Yes |
| `company_size` | VARCHAR(50) | NULL | Yes |
| `industry` | VARCHAR(100) | NULL | Yes |
| `city` | VARCHAR(100) | NULL | Yes |
| `country` | VARCHAR(100) | NULL | Yes |
| `seniority_level` | VARCHAR(50) | NULL | Yes |
| `work_model` | VARCHAR(50) | NULL | Yes |
| `company_type` | VARCHAR(100) | NULL | Yes |
| `target_timeframe` | VARCHAR(50) | NULL | Yes |
| `no_of_items` | INT UNSIGNED | 5 | No |
| `content_json` | LONGTEXT | NULL | Yes |
| `pdf_path` | VARCHAR(255) | NULL | Yes |
| `ai_mode` | ENUM('basic','advanced') | 'basic' | No |
| `status` | ENUM('draft','submitted','manager_reviewed','hod_reviewed') | 'draft' | No |
| `submitted_at` | TIMESTAMP | NULL | Yes |
| `manager_reviewed_at` | TIMESTAMP | NULL | Yes |
| `hod_reviewed_at` | TIMESTAMP | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 10. `gr_kpi_company_sizes` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `name` | VARCHAR(150) | — | No |
| `sort_order` | INT | 0 | No |
| `is_active` | TINYINT(1) | 1 | No |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

### 11. `gr_kpi_company_types` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

Same structure as `gr_kpi_company_sizes` (id, name, sort_order, is_active, created_by, created_at, updated_at)

---

### 12. `gr_kpi_industries` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

Same structure as `gr_kpi_company_sizes`

---

### 13. `gr_kpi_seniority_levels` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

Same structure as `gr_kpi_company_sizes`

---

### 14. `gr_kpi_timeframes` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

Same structure as `gr_kpi_company_sizes`

---

### 15. `gr_kpi_work_models` (Migration #181: `2026_04_14_000002_create_gr_kpi_tables`)

Same structure as `gr_kpi_company_sizes`

---

### 16. `gr_kpi_assignments` (Migration #182: `2026_04_15_000001_create_gr_kpi_assignments_table`)

| Column | Type | Default | Nullable |
|--------|------|---------|----------|
| `id` | BIGINT UNSIGNED | AUTO | No |
| `generation_id` | BIGINT UNSIGNED | — | No |
| `employee_id` | BIGINT UNSIGNED | — | No |
| `remarks` | TEXT | NULL | Yes |
| `assigned_by` | BIGINT UNSIGNED | — | No |
| `assigned_at` | TIMESTAMP | NULL | Yes |
| `created_by` | BIGINT UNSIGNED | — | No |
| `created_at` | TIMESTAMP | NULL | Yes |
| `updated_at` | TIMESTAMP | NULL | Yes |

---

## PART B: COLUMN ADDITIONS TO EXISTING TABLES (16 Apr 2026)

---

### Table: `gr_kpi_generations` — New Columns Added

**Migration #183:** `2026_04_16_000001_add_status_to_gr_kpi_generations.php`

| Column | Type | Default | Nullable | Position |
|--------|------|---------|----------|----------|
| `status` | ENUM('draft','submitted') | 'draft' | No | after `ai_mode` |
| `submitted_at` | TIMESTAMP | NULL | Yes | after `status` |

**Migration #184:** `2026_04_16_000002_add_hod_review_to_gr_kpi_generations.php`

| Column | Type | Default | Nullable | Position |
|--------|------|---------|----------|----------|
| `manager_reviewed_at` | TIMESTAMP | NULL | Yes | after `submitted_at` |
| `hod_reviewed_at` | TIMESTAMP | NULL | Yes | after `manager_reviewed_at` |

**Status ENUM extended to:** `ENUM('draft', 'submitted', 'manager_reviewed', 'hod_reviewed')` DEFAULT `'draft'`

---

### Table: `employees` — New Columns Added

**Migration #185:** `2026_04_16_000003_add_hod_and_management_to_employees.php`

| Column | Type | Default | Nullable | Position |
|--------|------|---------|----------|----------|
| `hod_id` | UNSIGNED BIGINT | NULL | Yes | after `reporting_manager_id` |
| `management_id` | UNSIGNED BIGINT | NULL | Yes | after `hod_id` |

---

## SUMMARY

### New Tables Created (16 total)

| # | Table | Migration | Date |
|---|-------|-----------|------|
| 1 | `performance_cycles` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 2 | `gr_missions` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 3 | `gr_reviews` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 4 | `gr_ratings` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 5 | `gr_shoutouts` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 6 | `gr_sync_ups` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 7 | `gr_comeback_plans` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 8 | `gr_increments` | `2026_04_14_000001_create_growth_review_tables` | 14 Apr |
| 9 | `gr_kpi_generations` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 10 | `gr_kpi_company_sizes` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 11 | `gr_kpi_company_types` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 12 | `gr_kpi_industries` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 13 | `gr_kpi_seniority_levels` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 14 | `gr_kpi_timeframes` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 15 | `gr_kpi_work_models` | `2026_04_14_000002_create_gr_kpi_tables` | 14 Apr |
| 16 | `gr_kpi_assignments` | `2026_04_15_000001_create_gr_kpi_assignments_table` | 15 Apr |

### Columns Added to Existing Tables (6 total)

| # | Table | Columns | Migration | Date |
|---|-------|---------|-----------|------|
| 1 | `gr_kpi_generations` | `status`, `submitted_at` | `2026_04_16_000001` | 16 Apr |
| 2 | `gr_kpi_generations` | `manager_reviewed_at`, `hod_reviewed_at` + ENUM extended | `2026_04_16_000002` | 16 Apr |
| 3 | `employees` | `hod_id`, `management_id` | `2026_04_16_000003` | 16 Apr |

### Grand Total
- **16 new tables** created (Growth Review + KPI module)
- **6 new columns** added to existing tables
- **6 migration files** (IDs #180–#185)
