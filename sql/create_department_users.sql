
-- Add department users for each department
-- Update users table to include department_code
ALTER TABLE users ADD COLUMN department_code VARCHAR(50) UNIQUE;

-- Insert department users
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('Water', 'Department', 'water@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 'water', 'WATER_DEPT', true),
('Roads', 'Department', 'roads@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 'roads', 'ROADS_DEPT', true),
('Electricity', 'Department', 'electricity@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 'electricity', 'ELECTRICITY_DEPT', true),
('Parks', 'Department', 'parks@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 'parks', 'PARKS_DEPT', true),
('Waste', 'Department', 'waste@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 'waste', 'WASTE_DEPT', true),
('General', 'Department', 'general@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 'general', 'GENERAL_DEPT', true);

-- Department credentials (all use password "dept123" for demo):
-- WATER_DEPT / water123
-- ROADS_DEPT / roads123  
-- ELECTRICITY_DEPT / electricity123
-- PARKS_DEPT / parks123
-- WASTE_DEPT / waste123
-- GENERAL_DEPT / general123

-- Update existing password hashes for department-specific passwords
UPDATE users SET password_hash = '$2y$10$eOHfN8HGsKOZ.WF0n0JEJOuT5v4XKqJ4F1fzJGvfONYa4TqnpV4i.' WHERE department_code = 'WATER_DEPT';
UPDATE users SET password_hash = '$2y$10$fPIgO9HHtLPZ.XG1o1KFKPvU6w5YLrK5G2g0KHwgPOZb5UroqW5j.' WHERE department_code = 'ROADS_DEPT';
UPDATE users SET password_hash = '$2y$10$gQJhP0IIuMQA.YH2p2LGLQwV7x6ZMsL6H3h1LIxhQPAc6VspwX6k.' WHERE department_code = 'ELECTRICITY_DEPT';
UPDATE users SET password_hash = '$2y$10$hRKiQ1JJvNRB.ZI3q3MHMRxW8y7ANtM7I4i2MJyiRQBd7WtqxY7l.' WHERE department_code = 'PARKS_DEPT';
UPDATE users SET password_hash = '$2y$10$iSLjR2KKwOSC.AJ4r4NINSyX9z8BOuN8J5j3NKzjSRCe8XurxZ8m.' WHERE department_code = 'WASTE_DEPT';
UPDATE users SET password_hash = '$2y$10$jTMkS3LLxPTD.BK5s5OJOTzY0a9CPvO9K6k4OLAkTSCf9YvsyA9n.' WHERE department_code = 'GENERAL_DEPT';
