# Dynamic Salary Structure Module (CodeIgniter 3)

This package provides a dynamic Indian payroll salary structure module for CodeIgniter 3.

## Features

- Dynamic salary components (earning/deduction/employer)
- Fixed, percentage, and formula-based component calculation
- Safe formula parser (no `eval`)
- Conditional rule support (`AND`, `OR`, comparison operators)
- Indian payroll logic support:
  - Basic %
  - HRA
  - Conveyance
  - Medical
  - Special allowance auto-balance
  - PF with eligibility and cap
  - ESIC eligibility and employer/employee rates
  - Gratuity
- Employee-level settings:
  - CTC
  - Basic %
  - PF enabled
  - ESIC enabled

## Folder Structure

Copy files to your CI3 app:

- `application/libraries/SalaryCalculator.php`
- `application/models/Salary_component_model.php`
- `application/controllers/Salary_structure.php`
- `application/views/salary/index.php`
- `application/views/salary/calculate.php`
- `application/config/routes_salary.php` (merge into main routes)
- `database/salary_module.sql` (run once)

## Install

1. Import SQL from `database/salary_module.sql`.
2. Copy `application/*` files into your CI3 project.
3. Merge routes from `application/config/routes_salary.php` to your main `application/config/routes.php`.
4. Open:
   - Admin: `/salary-structure`
   - Calculator: `/salary-structure/calculate`

## Notes

- Values in seeded components are annual.
- Formula variables use uppercase snake-case component names:
  - Example: `BASIC`, `HRA`, `CTC_ANNUAL`, `PF_ENABLED`.
- You can add custom allowances from admin and include them in formulas.
