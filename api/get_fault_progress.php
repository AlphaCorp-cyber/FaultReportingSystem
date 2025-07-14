<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/functions.php';

// Require authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$fault_id = intval($_GET['fault_id'] ?? 0);
$user = getCurrentUser();

if (!$fault_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid fault ID']);
    exit;
}

try {
    // Get fault details
    $fault = $db->selectOne(
        "SELECT fr.*, 
                CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                u.email as reporter_email
         FROM fault_reports fr
         JOIN users u ON fr.user_id = u.id
         WHERE fr.id = ? AND (fr.user_id = ? OR ? = 'admin' OR (? = 'department' AND fr.assigned_department = ?))",
        [$fault_id, $user['id'], $user['role'], $user['role'], $user['department'] ?? '']
    );

    if (!$fault) {
        echo json_encode(['success' => false, 'message' => 'Fault not found or access denied']);
        exit;
    }

    // Get progress updates
    $updates = $db->select(
        "SELECT fpu.*, 
                CONCAT(u.first_name, ' ', u.last_name) as updated_by_name,
                u.role as updated_by_role
         FROM fault_progress_updates fpu
         LEFT JOIN users u ON fpu.created_by = u.id
         WHERE fpu.fault_id = ? AND fpu.is_visible_to_resident = TRUE
         ORDER BY fpu.created_at DESC",
        [$fault_id]
    );

    // Format the response
    $fault_data = [
        'id' => $fault['id'],
        'reference_number' => $fault['reference_number'],
        'title' => $fault['title'],
        'status' => $fault['status'],
        'status_name' => getFaultStatusName($fault['status']),
        'department' => $fault['assigned_department'],
        'department_name' => getDepartmentName($fault['assigned_department'] ?? 'general'),
        'created_at' => $fault['created_at'],
        'updated_at' => $fault['updated_at']
    ];

    $progress_updates = [];
    foreach ($updates as $update) {
        $progress_updates[] = [
            'id' => $update['id'],
            'status' => $update['status'],
            'status_name' => getFaultStatusName($update['status']),
            'message' => $update['message'],
            'created_at' => $update['created_at'],
            'updated_by' => $update['updated_by_name'] ?? 'System',
            'updated_by_role' => $update['updated_by_role'] ?? 'system'
        ];
    }

    echo json_encode([
        'success' => true,
        'fault' => $fault_data,
        'updates' => $progress_updates
    ]);

} catch (Exception $e) {
    error_log("Error in get_fault_progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>