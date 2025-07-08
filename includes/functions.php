<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

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

function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    if ($file_error !== 0) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB'];
    }

    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $new_filename = generateUniqueId('evidence_') . '.' . $file_ext;
    $upload_path = UPLOAD_DIR . $new_filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (move_uploaded_file($file_tmp, $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $upload_path];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

function sendNotification($user_id, $message, $type = 'info') {
    global $db;
    
    try {
        $db->insert(
            "INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, ?, NOW())",
            [$user_id, $message, $type]
        );
        return true;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

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
