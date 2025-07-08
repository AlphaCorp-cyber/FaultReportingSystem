
<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$fault_id = (int)($input['fault_id'] ?? 0);

if (!$fault_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid fault ID']);
    exit();
}

try {
    // Get fault details and verify it's unassigned
    $fault = $db->selectOne(
        "SELECT id, reference_number, assigned_to, assigned_department, status FROM fault_reports WHERE id = ?",
        [$fault_id]
    );
    
    if (!$fault) {
        echo json_encode(['success' => false, 'message' => 'Fault not found']);
        exit();
    }
    
    if ($fault['assigned_to']) {
        echo json_encode(['success' => false, 'message' => 'Fault already assigned to someone']);
        exit();
    }
    
    if ($fault['status'] !== 'assigned') {
        echo json_encode(['success' => false, 'message' => 'Fault is not available for assignment']);
        exit();
    }
    
    // Assign fault to current user and update status
    $updated = $db->update(
        "UPDATE fault_reports SET assigned_to = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?",
        [$user['id'], $fault_id]
    );
    
    if ($updated) {
        // Log the assignment
        logActivity($user['id'], 'fault_assignment_taken', "Took assignment for fault #{$fault['reference_number']}");
        
        // Send notification to reporter
        $reporter = $db->selectOne("SELECT user_id FROM fault_reports WHERE id = ?", [$fault_id]);
        if ($reporter) {
            sendNotification(
                $reporter['user_id'],
                "Your fault report #{$fault['reference_number']} has been assigned to a technician and is now in progress",
                'info'
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Assignment taken successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to take assignment']);
    }
    
} catch (Exception $e) {
    error_log("Take assignment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
