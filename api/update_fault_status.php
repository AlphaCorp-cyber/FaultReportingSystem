<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user = getCurrentUser();
$fault_id = (int)$_POST['fault_id'];
$new_status = sanitizeInput($_POST['status']);
$notes = sanitizeInput($_POST['notes']);

// Validate inputs
if (!$fault_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Validate status
$valid_statuses = array_keys(FAULT_STATUSES);
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Get current fault
    $fault = $db->selectOne("SELECT * FROM fault_reports WHERE id = ?", [$fault_id]);
    
    if (!$fault) {
        echo json_encode(['success' => false, 'message' => 'Fault not found']);
        exit();
    }
    
    $old_status = $fault['status'];
    
    // Update fault status
    $updated = $db->update(
        "UPDATE fault_reports SET status = ?, resolution_notes = ?, updated_at = NOW() WHERE id = ?",
        [$new_status, $notes, $fault_id]
    );
    
    if ($updated) {
        // Add to status history
        $db->insert(
            "INSERT INTO fault_status_history (fault_id, old_status, new_status, changed_by, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$fault_id, $old_status, $new_status, $user['id'], $notes]
        );
        
        // Send notification to user
        $status_message = "Your fault report #{$fault['reference_number']} status has been updated to " . getFaultStatusName($new_status);
        if ($notes) {
            $status_message .= ". Note: " . $notes;
        }
        
        sendNotification($fault['user_id'], $status_message, 'info');
        
        // Log activity
        logActivity($user['id'], 'fault_status_updated', "Updated fault #{$fault['reference_number']} status from $old_status to $new_status");
        
        echo json_encode([
            'success' => true,
            'message' => 'Fault status updated successfully',
            'new_status' => $new_status,
            'status_name' => getFaultStatusName($new_status),
            'status_badge_class' => getStatusBadgeClass($new_status)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update fault status']);
    }
    
} catch (Exception $e) {
    error_log("Update fault status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the fault status']);
}
?>
