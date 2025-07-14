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
    if ($input === null) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . '_' . time();
}

function formatDate($date) {
    if (empty($date)) {
        return 'N/A';
    }
    return date('M j, Y g:i A', strtotime($date));
}

function getTimeAgo($datetime) {
    if (empty($datetime)) {
        return 'N/A';
    }

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

function getPriorityBadgeClass($priority) {
    $classes = [
        'low' => 'bg-success',
        'medium' => 'bg-warning',
        'high' => 'bg-danger',
        'urgent' => 'bg-danger'
    ];
    return isset($classes[$priority]) ? $classes[$priority] : 'bg-secondary';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function isValidFaultCategory($category) {
    return array_key_exists($category, FAULT_CATEGORIES);
}

function isValidFaultStatus($status) {
    return array_key_exists($status, FAULT_STATUSES);
}

function isValidDepartment($department) {
    return array_key_exists($department, DEPARTMENTS);
}

function getPriorityLevel($category, $description) {
    $high_priority_keywords = ['emergency', 'urgent', 'broken', 'burst', 'flooding', 'dangerous', 'blocked', 'outage'];
    $description_lower = strtolower($description);

    // Check for emergency keywords
    foreach ($high_priority_keywords as $keyword) {
        if (strpos($description_lower, $keyword) !== false) {
            return 'high';
        }
    }

    // Category-based priority
    $high_priority_categories = ['water', 'electricity'];
    if (in_array($category, $high_priority_categories)) {
        return 'medium';
    }

    return 'low';
}

/**
 * Get approximate coordinates for a location
 * This is a simple implementation that could be enhanced with actual geocoding service
 */
function getLocationCoordinates($location) {
    // Default coordinates for Redcliff, Zimbabwe
    $default_coordinates = [
        'latitude' => -18.1833,
        'longitude' => 29.7667
    ];

    // Simple location mapping for common areas in Redcliff
    $location_map = [
        'town center' => ['latitude' => -18.1833, 'longitude' => 29.7667],
        'central' => ['latitude' => -18.1833, 'longitude' => 29.7667],
        'industrial area' => ['latitude' => -18.1900, 'longitude' => 29.7600],
        'residential area' => ['latitude' => -18.1800, 'longitude' => 29.7700],
        'main street' => ['latitude' => -18.1830, 'longitude' => 29.7665],
        'market area' => ['latitude' => -18.1840, 'longitude' => 29.7670],
        'school area' => ['latitude' => -18.1820, 'longitude' => 29.7680],
        'clinic area' => ['latitude' => -18.1825, 'longitude' => 29.7675],
        'north' => ['latitude' => -18.1800, 'longitude' => 29.7667],
        'south' => ['latitude' => -18.1866, 'longitude' => 29.7667],
        'east' => ['latitude' => -18.1833, 'longitude' => 29.7700],
        'west' => ['latitude' => -18.1833, 'longitude' => 29.7634]
    ];

    $location_lower = strtolower(trim($location));

    // Check for matches in the location map
    foreach ($location_map as $area => $coords) {
        if (strpos($location_lower, $area) !== false) {
            return $coords;
        }
    }

    // If no specific match found, add small random offset to default coordinates
    // to distinguish different reports
    $lat_offset = (mt_rand(-50, 50) / 10000); // ±0.005 degrees
    $lng_offset = (mt_rand(-50, 50) / 10000); // ±0.005 degrees

    return [
        'latitude' => $default_coordinates['latitude'] + $lat_offset,
        'longitude' => $default_coordinates['longitude'] + $lng_offset
    ];
}
?>