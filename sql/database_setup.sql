-- Redcliff Municipality Fault Reporting System Database
-- Create database
CREATE DATABASE IF NOT EXISTS redcliff_fault_system;
USE redcliff_fault_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    account_number VARCHAR(20) UNIQUE,
    id_number VARCHAR(20) UNIQUE,
    role ENUM('admin', 'resident') DEFAULT 'resident',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Payment records table (for verification)
CREATE TABLE payment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(20) NOT NULL,
    id_number VARCHAR(20) NOT NULL,
    account_holder_name VARCHAR(100) NOT NULL,
    property_address TEXT,
    last_payment_date DATE,
    amount_paid DECIMAL(10,2),
    balance DECIMAL(10,2),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account_number (account_number),
    INDEX idx_id_number (id_number)
);

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    head_of_department VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fault reports table
CREATE TABLE fault_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    category ENUM('water', 'roads', 'electricity', 'streetlights', 'waste', 'parks', 'other') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('submitted', 'assigned', 'in_progress', 'resolved', 'closed', 'rejected') DEFAULT 'submitted',
    assigned_department VARCHAR(50),
    assigned_to INT,
    evidence_files JSON,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reference_number (reference_number),
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Fault status history table
CREATE TABLE fault_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fault_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fault_id) REFERENCES fault_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_fault_id (fault_id),
    INDEX idx_created_at (created_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (first_name, last_name, email, password, role, status) 
VALUES ('System', 'Administrator', 'admin@redcliff.gov.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert default departments
INSERT INTO departments (name, description, head_of_department, contact_email, contact_phone, status) VALUES
('Water Department', 'Water supply and sewerage management', 'John Mukamuri', 'water@redcliff.gov.zw', '+263 54 123 4567', 'active'),
('Roads Department', 'Road maintenance and transportation infrastructure', 'Mary Chigwada', 'roads@redcliff.gov.zw', '+263 54 123 4568', 'active'),
('Electricity Department', 'Power supply and electrical infrastructure', 'David Mpofu', 'electricity@redcliff.gov.zw', '+263 54 123 4569', 'active'),
('Parks Department', 'Parks and recreation facilities', 'Grace Ndlovu', 'parks@redcliff.gov.zw', '+263 54 123 4570', 'active'),
('Waste Management', 'Waste collection and disposal services', 'Peter Sibanda', 'waste@redcliff.gov.zw', '+263 54 123 4571', 'active'),
('General Services', 'General municipal services', 'Susan Moyo', 'general@redcliff.gov.zw', '+263 54 123 4572', 'active');

-- Insert sample payment records for testing
INSERT INTO payment_records (account_number, id_number, account_holder_name, property_address, last_payment_date, amount_paid, balance, status) VALUES
('ACC001', '12345678', 'John Doe', '123 Main Street, Redcliff', '2024-12-01', 150.00, 0.00, 'active'),
('ACC002', '87654321', 'Jane Smith', '456 Oak Avenue, Redcliff', '2024-11-15', 200.00, 50.00, 'active'),
('ACC003', '11223344', 'Robert Johnson', '789 Pine Road, Redcliff', '2024-12-05', 175.00, 25.00, 'active'),
('ACC004', '44332211', 'Mary Wilson', '321 Elm Street, Redcliff', '2024-11-30', 180.00, 0.00, 'active'),
('ACC005', '55667788', 'David Brown', '654 Maple Drive, Redcliff', '2024-12-10', 160.00, 15.00, 'active');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('fault_auto_assign', 'true', 'Automatically assign faults to departments based on category'),
('notification_enabled', 'true', 'Enable email notifications for fault updates'),
('max_file_size', '5242880', 'Maximum file size for evidence uploads (in bytes)'),
('maintenance_mode', 'false', 'Enable maintenance mode'),
('fault_retention_days', '365', 'Number of days to retain closed fault reports');

-- Create indexes for better performance
CREATE INDEX idx_fault_reports_composite ON fault_reports(user_id, status, created_at);
CREATE INDEX idx_payment_records_composite ON payment_records(account_number, id_number, status);
CREATE INDEX idx_notifications_composite ON notifications(user_id, is_read, created_at);

-- Create views for common queries
CREATE VIEW active_faults AS
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

CREATE VIEW fault_summary AS
SELECT 
    DATE(created_at) as report_date,
    category,
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_count
FROM fault_reports
GROUP BY DATE(created_at), category;

-- Create triggers for automatic actions
DELIMITER $$

CREATE TRIGGER fault_status_update_trigger
AFTER UPDATE ON fault_reports
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO fault_status_history (fault_id, old_status, new_status, changed_by, notes)
        VALUES (NEW.id, OLD.status, NEW.status, NEW.assigned_to, 'Status automatically updated');
    END IF;
END$$

CREATE TRIGGER fault_assignment_notification
AFTER UPDATE ON fault_reports
FOR EACH ROW
BEGIN
    IF OLD.assigned_to != NEW.assigned_to AND NEW.assigned_to IS NOT NULL THEN
        INSERT INTO notifications (user_id, message, type)
        VALUES (NEW.assigned_to, CONCAT('You have been assigned fault report #', NEW.reference_number), 'info');
    END IF;
END$$

DELIMITER ;
