-- Sample Statutory data (created_by/company = 1)

INSERT INTO statutory_components (name, code, status, created_by, created_at, updated_at)
VALUES
('Employees Provident Fund', 'EPF', 1, 1, NOW(), NOW()),
('Employee State Insurance', 'ESIC', 1, 1, NOW(), NOW()),
('Professional Tax', 'PT', 1, 1, NOW(), NOW()),
('Labour Welfare Fund', 'LWF', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO states (state_name) VALUES ('Maharashtra'), ('Karnataka'), ('Delhi');

-- EPF national rule
INSERT INTO statutory_rules
(component_id, state_id, min_salary, max_salary, employee_contribution_type, employee_value, employer_contribution_type, employer_value, max_limit, frequency, applicable_gender, effective_from, status, created_by, created_at, updated_at)
SELECT sc.id, NULL, 0, 15000, 'percentage', 12.0000, 'percentage', 12.0000, 1800, 'monthly', NULL, '2026-01-01', 1, 1, NOW(), NOW()
FROM statutory_components sc WHERE sc.code = 'EPF' LIMIT 1;

-- ESIC national rule
INSERT INTO statutory_rules
(component_id, state_id, min_salary, max_salary, employee_contribution_type, employee_value, employer_contribution_type, employer_value, max_limit, frequency, applicable_gender, effective_from, status, created_by, created_at, updated_at)
SELECT sc.id, NULL, 0, 21000, 'percentage', 0.7500, 'percentage', 3.2500, NULL, 'monthly', NULL, '2026-01-01', 1, 1, NOW(), NOW()
FROM statutory_components sc WHERE sc.code = 'ESIC' LIMIT 1;

-- PT slab (Maharashtra example)
INSERT INTO statutory_rules
(component_id, state_id, min_salary, max_salary, employee_contribution_type, employee_value, employer_contribution_type, employer_value, max_limit, frequency, applicable_gender, effective_from, status, created_by, created_at, updated_at)
SELECT sc.id, st.id, 0, 15000, 'fixed', 0, 'fixed', 0, NULL, 'monthly', NULL, '2026-01-01', 1, 1, NOW(), NOW()
FROM statutory_components sc, states st WHERE sc.code = 'PT' AND st.state_name='Maharashtra' LIMIT 1;

INSERT INTO statutory_rules
(component_id, state_id, min_salary, max_salary, employee_contribution_type, employee_value, employer_contribution_type, employer_value, max_limit, frequency, applicable_gender, effective_from, status, created_by, created_at, updated_at)
SELECT sc.id, st.id, 15001, 20000, 'fixed', 150, 'fixed', 0, NULL, 'monthly', NULL, '2026-01-01', 1, 1, NOW(), NOW()
FROM statutory_components sc, states st WHERE sc.code = 'PT' AND st.state_name='Maharashtra' LIMIT 1;

INSERT INTO statutory_rules
(component_id, state_id, min_salary, max_salary, employee_contribution_type, employee_value, employer_contribution_type, employer_value, max_limit, frequency, applicable_gender, effective_from, status, created_by, created_at, updated_at)
SELECT sc.id, st.id, 20001, NULL, 'fixed', 200, 'fixed', 0, NULL, 'monthly', NULL, '2026-01-01', 1, 1, NOW(), NOW()
FROM statutory_components sc, states st WHERE sc.code = 'PT' AND st.state_name='Maharashtra' LIMIT 1;

-- LWF half-yearly (June, December handling in calculator)
INSERT INTO statutory_rules
(component_id, state_id, min_salary, max_salary, employee_contribution_type, employee_value, employer_contribution_type, employer_value, max_limit, frequency, applicable_gender, effective_from, status, created_by, created_at, updated_at)
SELECT sc.id, st.id, 0, NULL, 'fixed', 10, 'fixed', 20, NULL, 'half-yearly', NULL, '2026-01-01', 1, 1, NOW(), NOW()
FROM statutory_components sc, states st WHERE sc.code = 'LWF' AND st.state_name='Maharashtra' LIMIT 1;

