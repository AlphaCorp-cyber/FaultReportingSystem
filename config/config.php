<?php
// Start output buffering to prevent header issues
if (!headers_sent() && !ob_get_level()) {
    ob_start();
}

// Start session only if not already started and headers not sent
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', getenv('PGHOST') ?: 'localhost');
define('DB_USERNAME', getenv('PGUSER') ?: 'root');
define('DB_PASSWORD', getenv('PGPASSWORD') ?: '');
define('DB_NAME', getenv('PGDATABASE') ?: 'redcliff_fault_system');

// Application configuration
define('APP_NAME', 'Redcliff Municipality Fault Reporting System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:5000');

// File upload configuration
define('UPLOAD_DIR', 'uploads/evidence/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Security configuration
define('HASH_ALGORITHM', 'sha256');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@redcliff.gov.zw');
define('FROM_NAME', 'Redcliff Municipality');

// System roles
define('ROLE_ADMIN', 'admin');
define('ROLE_RESIDENT', 'resident');

// Fault categories
define('FAULT_CATEGORIES', [
    'water' => 'Water & Sewer',
    'roads' => 'Roads & Transportation',
    'electricity' => 'Electricity & Power',
    'streetlights' => 'Street Lighting',
    'waste' => 'Waste Management',
    'parks' => 'Parks & Recreation',
    'other' => 'Other'
]);

// Fault statuses
define('FAULT_STATUSES', [
    'submitted' => 'Submitted',
    'assigned' => 'Assigned',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
    'rejected' => 'Rejected'
]);

// Departments
define('DEPARTMENTS', [
    'water' => 'Water Department',
    'roads' => 'Roads Department',
    'electricity' => 'Electricity Department',
    'parks' => 'Parks Department',
    'waste' => 'Waste Management',
    'general' => 'General Services'
]);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Harare');

// Include database connection
require_once 'database.php';

// Create auth instance
require_once __DIR__ . '/../includes/auth.php';
$auth = new Auth();
?>
