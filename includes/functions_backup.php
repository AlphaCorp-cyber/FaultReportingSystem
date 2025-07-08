<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

function getCurrentUser() {
    global $auth;
    return $auth->getCurrentUser();
}

function hasRole($role) {
    global $auth;
    return $auth->hasRole($role);
}

function requireAuth() {
    global $auth;
    $auth->requireAuth();
}

function requireRole($role) {
    global $auth;
    $auth->requireRole($role);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . '_' . time();
}

function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function generateReferenceNumber() {
    return 'FLT' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function logActivity($user_id, $action, $description = '') {
    global $db;
    try {
        $db->insert(
            "INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
            [$user_id, $action, $description, $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

function sendNotification($user_id, $title, $message, $type = 'info') {
    global $db;
    try {
        $db->insert(
            "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)",
            [$user_id, $title, $message, $type]
        );
        return true;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

function uploadFile($file, $upload_dir = UPLOAD_DIR) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $filename = generateUniqueId() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename, 'path' => $target_path];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

// sendNotification function already defined above

function getFaultCategoryName($category) {
    $categories = FAULT_CATEGORIES;
    return isset($categories[$category]) ? $categories[$category] : 'Unknown';
}

function getFaultStatusName($status) {
    $statuses = FAULT_STATUSES;
    return isset($statuses[$status]) ? $statuses[$status] : 'Unknown';
}

function getDepartmentName($department) {
    $departments = DEPARTMENTS;
    return isset($departments[$department]) ? $departments[$department] : 'Unknown';
}

function getStatusBadgeClass($status) {
    $classes = [
        'submitted' => 'bg-secondary',
        'assigned' => 'bg-info',
        'in_progress' => 'bg-warning',
        'resolved' => 'bg-success',
        'closed' => 'bg-dark',
        'rejected' => 'bg-danger'
    ];
    return isset($classes[$status]) ? $classes[$status] : 'bg-secondary';
}

function logActivity($user_id, $action, $details = '') {
    global $db;
    
    try {
        $db->insert(
            "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())",
            [$user_id, $action, $details]
        );
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

function generateReport($type, $params = []) {
    global $db;
    
    switch ($type) {
        case 'monthly_summary':
            return $db->select(
                "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as total_faults,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_faults,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_faults
                FROM fault_reports 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC"
            );
            
        case 'department_performance':
            return $db->select(
                "SELECT 
                    assigned_department,
                    COUNT(*) as total_assigned,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                    AVG(DATEDIFF(updated_at, created_at)) as avg_resolution_days
                FROM fault_reports 
                WHERE assigned_department IS NOT NULL
                GROUP BY assigned_department"
            );
            
        case 'category_breakdown':
            return $db->select(
                "SELECT 
                    category,
                    COUNT(*) as fault_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
                FROM fault_reports 
                GROUP BY category 
                ORDER BY fault_count DESC"
            );
            
        default:
            return [];
    }
}

function getPriorityLevel($category, $description) {
    $high_priority_keywords = ['emergency', 'urgent', 'dangerous', 'burst', 'flood', 'fire'];
    $description_lower = strtolower($description);
    
    foreach ($high_priority_keywords as $keyword) {
        if (strpos($description_lower, $keyword) !== false) {
            return 'high';
        }
    }
    
    $high_priority_categories = ['water', 'electricity'];
    if (in_array($category, $high_priority_categories)) {
        return 'medium';
    }
    
    return 'low';
}

function getLocationCoordinates($address) {
    // This would integrate with a geocoding service
    // For now, return dummy coordinates for Redcliff
    return [
        'latitude' => -19.0389,
        'longitude' => 29.7868
    ];
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
}

function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>
