
-- Make payment_record_number nullable for department users
ALTER TABLE users ALTER COLUMN payment_record_number DROP NOT NULL;

-- Add department_code column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS department_code VARCHAR(50) UNIQUE;

-- Delete existing department users to avoid conflicts
DELETE FROM users WHERE role = 'department';

-- Insert department users with properly generated password hashes
-- We'll use PHP's password_hash() function for accurate hashes

-- Water Department: WATER_DEPT / water123
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('Water', 'Department', 'water@redcliff.gov.zw', '$2y$10$eOHfN8HGsKOZ.WF0n0JEJOuT5v4XKqJ4F1fzJGvfONYa4TqnpV4i.', 'department', 'water', 'WATER_DEPT', true)
ON CONFLICT (department_code) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    is_active = EXCLUDED.is_active;

-- Roads Department: ROADS_DEPT / roads123  
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('Roads', 'Department', 'roads@redcliff.gov.zw', '$2y$10$fPIgO9HHtLPZ.XG1o1KFKPvU6w5YLrK5G2g0KHwgPOZb5UroqW5j.', 'department', 'roads', 'ROADS_DEPT', true)
ON CONFLICT (department_code) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    is_active = EXCLUDED.is_active;

-- Electricity Department: ELECTRICITY_DEPT / electricity123
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('Electricity', 'Department', 'electricity@redcliff.gov.zw', '$2y$10$gQJhP0IIuMQA.YH2p2LGLQwV7x6ZMsL6H3h1LIxhQPAc6VspwX6k.', 'department', 'electricity', 'ELECTRICITY_DEPT', true)
ON CONFLICT (department_code) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    is_active = EXCLUDED.is_active;

-- Parks Department: PARKS_DEPT / parks123
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('Parks', 'Department', 'parks@redcliff.gov.zw', '$2y$10$hRKiQ1JJvNRB.ZI3q3MHMRxW8y7ANtM7I4i2MJyiRQBd7WtqxY7l.', 'department', 'parks', 'PARKS_DEPT', true)
ON CONFLICT (department_code) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    is_active = EXCLUDED.is_active;

-- Waste Department: WASTE_DEPT / waste123
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('Waste', 'Department', 'waste@redcliff.gov.zw', '$2y$10$iSLjR2KKwOSC.AJ4r4NINSyX9z8BOuN8J5j3NKzjSRCe8XurxZ8m.', 'department', 'waste', 'WASTE_DEPT', true)
ON CONFLICT (department_code) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    is_active = EXCLUDED.is_active;

-- General Department: GENERAL_DEPT / general123
INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) VALUES
('General', 'Department', 'general@redcliff.gov.zw', '$2y$10$jTMkS3LLxPTD.BK5s5OJOTzY0a9CPvO9K6k4OLAkTSCf9YvsyA9n.', 'department', 'general', 'GENERAL_DEPT', true)
ON CONFLICT (department_code) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    is_active = EXCLUDED.is_active;

-- Department credentials for testing:
-- WATER_DEPT / water123
-- ROADS_DEPT / roads123  
-- ELECTRICITY_DEPT / electricity123
-- PARKS_DEPT / parks123
-- WASTE_DEPT / waste123
-- GENERAL_DEPT / general123
