-- Redcliff Municipality Fault Reporting System Database (PostgreSQL)

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    account_number VARCHAR(20) UNIQUE,
    id_number VARCHAR(20) UNIQUE,
    role VARCHAR(20) DEFAULT 'resident' CHECK (role IN ('admin', 'resident')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Payment records table (for verification)
CREATE TABLE IF NOT EXISTS payment_records (
    id SERIAL PRIMARY KEY,
    account_number VARCHAR(20) NOT NULL,
    id_number VARCHAR(20) NOT NULL,
    account_holder_name VARCHAR(100) NOT NULL,
    property_address TEXT,
    last_payment_date DATE,
    amount_paid DECIMAL(10,2),
    balance DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    head_of_department VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fault reports table
CREATE TABLE IF NOT EXISTS fault_reports (
    id SERIAL PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL CHECK (category IN ('water', 'roads', 'electricity', 'streetlights', 'waste', 'parks', 'other')),
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    priority VARCHAR(20) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
    status VARCHAR(20) DEFAULT 'submitted' CHECK (status IN ('submitted', 'assigned', 'in_progress', 'resolved', 'closed', 'rejected')),
    assigned_department VARCHAR(50),
    assigned_to INT,
    evidence_files JSONB,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Fault status history table
CREATE TABLE IF NOT EXISTS fault_status_history (
    id SERIAL PRIMARY KEY,
    fault_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fault_id) REFERENCES fault_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'info' CHECK (type IN ('info', 'success', 'warning', 'error')),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_payment_records_account_number ON payment_records(account_number);
CREATE INDEX IF NOT EXISTS idx_payment_records_id_number ON payment_records(id_number);
CREATE INDEX IF NOT EXISTS idx_fault_reports_reference_number ON fault_reports(reference_number);
CREATE INDEX IF NOT EXISTS idx_fault_reports_user_id ON fault_reports(user_id);
CREATE INDEX IF NOT EXISTS idx_fault_reports_category ON fault_reports(category);
CREATE INDEX IF NOT EXISTS idx_fault_reports_status ON fault_reports(status);
CREATE INDEX IF NOT EXISTS idx_fault_reports_created_at ON fault_reports(created_at);
CREATE INDEX IF NOT EXISTS idx_fault_status_history_fault_id ON fault_status_history(fault_id);
CREATE INDEX IF NOT EXISTS idx_fault_status_history_created_at ON fault_status_history(created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_fault_reports_composite ON fault_reports(user_id, status, created_at);
CREATE INDEX IF NOT EXISTS idx_payment_records_composite ON payment_records(account_number, id_number, status);
CREATE INDEX IF NOT EXISTS idx_notifications_composite ON notifications(user_id, is_read, created_at);

-- Insert default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password, role, status) 
VALUES ('System', 'Administrator', 'admin@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON CONFLICT (email) DO NOTHING;

-- Insert default departments
INSERT INTO departments (name, description, head_of_department, contact_email, contact_phone, status) VALUES
('Water Department', 'Water supply and sewerage management', 'John Mukamuri', 'water@redcliff.gov.zw', '+263 54 123 4567', 'active'),
('Roads Department', 'Road maintenance and transportation infrastructure', 'Mary Chigwada', 'roads@redcliff.gov.zw', '+263 54 123 4568', 'active'),
('Electricity Department', 'Power supply and electrical infrastructure', 'David Mpofu', 'electricity@redcliff.gov.zw', '+263 54 123 4569', 'active'),
('Parks Department', 'Parks and recreation facilities', 'Grace Ndlovu', 'parks@redcliff.gov.zw', '+263 54 123 4570', 'active'),
('Waste Management', 'Waste collection and disposal services', 'Peter Sibanda', 'waste@redcliff.gov.zw', '+263 54 123 4571', 'active'),
('General Services', 'General municipal services', 'Susan Moyo', 'general@redcliff.gov.zw', '+263 54 123 4572', 'active')
ON CONFLICT (name) DO NOTHING;

-- Insert sample payment records for testing
INSERT INTO payment_records (account_number, id_number, account_holder_name, property_address, last_payment_date, amount_paid, balance, status) VALUES
('ACC001', '12345678', 'John Doe', '123 Main Street, Redcliff', '2024-12-01', 150.00, 0.00, 'active'),
('ACC002', '87654321', 'Jane Smith', '456 Oak Avenue, Redcliff', '2024-11-15', 200.00, 50.00, 'active'),
('ACC003', '11223344', 'Robert Johnson', '789 Pine Road, Redcliff', '2024-12-05', 175.00, 25.00, 'active'),
('ACC004', '44332211', 'Mary Wilson', '321 Elm Street, Redcliff', '2024-11-30', 180.00, 0.00, 'active'),
('ACC005', '55667788', 'David Brown', '654 Maple Drive, Redcliff', '2024-12-10', 160.00, 15.00, 'active')
ON CONFLICT (account_number) DO NOTHING;

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('fault_auto_assign', 'true', 'Automatically assign faults to departments based on category'),
('notification_enabled', 'true', 'Enable email notifications for fault updates'),
('max_file_size', '5242880', 'Maximum file size for evidence uploads (in bytes)'),
('maintenance_mode', 'false', 'Enable maintenance mode'),
('fault_retention_days', '365', 'Number of days to retain closed fault reports')
ON CONFLICT (setting_key) DO NOTHING;

-- Create views for common queries
CREATE OR REPLACE VIEW active_faults AS
SELECT 
    fr.id,
    fr.reference_number,
    fr.title,
    fr.category,
    fr.status,
    fr.priority,
    fr.location,
    fr.created_at,
    CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
    u.email as reporter_email,
    u.phone as reporter_phone
FROM fault_reports fr
JOIN users u ON fr.user_id = u.id
WHERE fr.status NOT IN ('closed', 'rejected');

CREATE OR REPLACE VIEW fault_summary AS
SELECT 
    DATE(created_at) as report_date,
    category,
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_count
FROM fault_reports
GROUP BY DATE(created_at), category;