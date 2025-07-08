<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Create database tables
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            payment_record_number VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'resident',
            department VARCHAR(50),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS fault_reports (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            reference_number VARCHAR(20) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(20) DEFAULT 'submitted',
            location VARCHAR(255) NOT NULL,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
            assigned_department VARCHAR(50),
            evidence_files TEXT,
            estimated_cost DECIMAL(10, 2),
            actual_cost DECIMAL(10, 2),
            resolution_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS fault_status_history (
            id SERIAL PRIMARY KEY,
            fault_id INTEGER REFERENCES fault_reports(id) ON DELETE CASCADE,
            old_status VARCHAR(20) NOT NULL,
            new_status VARCHAR(20) NOT NULL,
            changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS notifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(20) DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS activity_log (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->query("
        CREATE TABLE IF NOT EXISTS system_settings (
            id SERIAL PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $db->query("
        INSERT INTO users (first_name, last_name, email, phone, address, payment_record_number, password_hash, role, department, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (email) DO NOTHING
    ", [
        'System', 'Administrator', 'admin@redcliff.gov.zw', 
        '+263-4-2500-000', 'Redcliff Municipality, Midlands Province', 
        'ADMIN-001', $admin_password, 'admin', 'administration', true
    ]);

    // Insert sample payment records for testing
    $resident_password = password_hash('resident123', PASSWORD_DEFAULT);
    $db->query("
        INSERT INTO users (first_name, last_name, email, phone, address, payment_record_number, password_hash, role, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (email) DO NOTHING
    ", [
        'John', 'Doe', 'john.doe@example.com', 
        '+263-77-123-4567', '123 Main Street, Redcliff', 
        'PAY-001', $resident_password, 'resident', true
    ]);

    $db->query("
        INSERT INTO users (first_name, last_name, email, phone, address, payment_record_number, password_hash, role, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (email) DO NOTHING
    ", [
        'Jane', 'Smith', 'jane.smith@example.com', 
        '+263-77-987-6543', '456 Oak Avenue, Redcliff', 
        'PAY-002', $resident_password, 'resident', true
    ]);

    // Insert system settings
    $settings = [
        ['municipality_name', 'Redcliff Municipality', 'Name of the municipality'],
        ['contact_email', 'info@redcliff.gov.zw', 'Primary contact email'],
        ['contact_phone', '+263-4-2500-000', 'Primary contact phone'],
        ['address', 'Redcliff Municipality, Midlands Province, Zimbabwe', 'Municipality address'],
        ['auto_assign_faults', 'true', 'Automatically assign faults to departments'],
        ['notification_email', 'notifications@redcliff.gov.zw', 'Email for system notifications'],
        ['max_file_size', '5242880', 'Maximum file upload size in bytes'],
        ['allowed_file_types', 'jpg,jpeg,png,gif,pdf', 'Allowed file extensions for uploads']
    ];

    foreach ($settings as $setting) {
        $db->query("
            INSERT INTO system_settings (setting_key, setting_value, description)
            VALUES (?, ?, ?)
            ON CONFLICT (setting_key) DO NOTHING
        ", $setting);
    }

    // Create indexes for better performance
    $db->query("CREATE INDEX IF NOT EXISTS idx_fault_reports_user_id ON fault_reports(user_id)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_fault_reports_status ON fault_reports(status)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_fault_reports_category ON fault_reports(category)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_fault_reports_reference ON fault_reports(reference_number)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_fault_reports_created_at ON fault_reports(created_at)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_activity_log_user_id ON activity_log(user_id)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_activity_log_created_at ON activity_log(created_at)");

    echo "Database setup completed successfully!\n";
    echo "Default admin user created:\n";
    echo "Email: admin@redcliff.gov.zw\n";
    echo "Password: admin123\n\n";
    echo "Sample resident users created:\n";
    echo "Email: john.doe@example.com, Password: resident123\n";
    echo "Email: jane.smith@example.com, Password: resident123\n";

} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
}
?>