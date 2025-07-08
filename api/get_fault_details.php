<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
requireAuth();

$user = getCurrentUser();
$fault_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$reference_number = isset($_GET['ref']) ? sanitizeInput($_GET['ref']) : '';
$is_tracking = isset($_GET['track']) && $_GET['track'] === 'true';

if (!$fault_id && !$reference_number) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Build query based on parameters
    if ($fault_id) {
        $fault = $db->selectOne(
            "SELECT fr.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                    u.email as reporter_email,
                    u.phone as reporter_phone,
                    u.address as reporter_address,
                    CONCAT(a.first_name, ' ', a.last_name) as assigned_name,
                    a.email as assigned_email
             FROM fault_reports fr
             JOIN users u ON fr.user_id = u.id
             LEFT JOIN users a ON fr.assigned_to = a.id
             WHERE fr.id = ?",
            [$fault_id]
        );
    } else {
        $fault = $db->selectOne(
            "SELECT fr.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                    u.email as reporter_email,
                    u.phone as reporter_phone,
                    u.address as reporter_address,
                    CONCAT(a.first_name, ' ', a.last_name) as assigned_name,
                    a.email as assigned_email
             FROM fault_reports fr
             JOIN users u ON fr.user_id = u.id
             LEFT JOIN users a ON fr.assigned_to = a.id
             WHERE fr.reference_number = ?",
            [$reference_number]
        );
    }
    
    if (!$fault) {
        echo json_encode(['success' => false, 'message' => 'Fault not found']);
        exit();
    }
    
    // Check permissions - residents can only view their own faults
    if ($user['role'] === 'resident' && $fault['user_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Get status history
    $status_history = $db->select(
        "SELECT fsh.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
         FROM fault_status_history fsh
         LEFT JOIN users u ON fsh.changed_by = u.id
         WHERE fsh.fault_id = ?
         ORDER BY fsh.created_at DESC",
        [$fault['id']]
    );
    
    // Parse evidence files
    $evidence_files = [];
    if ($fault['evidence_files']) {
        $evidence_files = json_decode($fault['evidence_files'], true) ?: [];
    }
    
    // Generate HTML content
    if ($is_tracking) {
        $html = generateTrackingHTML($fault, $status_history);
    } else {
        $html = generateFaultDetailsHTML($fault, $status_history, $evidence_files, $user);
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'fault' => $fault
    ]);
    
} catch (Exception $e) {
    error_log("Get fault details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while loading fault details']);
}

function generateFaultDetailsHTML($fault, $status_history, $evidence_files, $user) {
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <!-- Fault Information -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Fault Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Reference Number:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($fault['reference_number']); ?></span>
                        </dd>
                        
                        <dt class="col-sm-4">Title:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($fault['title']); ?></dd>
                        
                        <dt class="col-sm-4">Category:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-light text-dark"><?php echo getFaultCategoryName($fault['category']); ?></span>
                        </dd>
                        
                        <dt class="col-sm-4">Priority:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?php echo $fault['priority'] === 'high' ? 'danger' : ($fault['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($fault['priority']); ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?php echo getStatusBadgeClass($fault['status']); ?>">
                                <?php echo getFaultStatusName($fault['status']); ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-4">Location:</dt>
                        <dd class="col-sm-8">
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            <?php echo htmlspecialchars($fault['location']); ?>
                        </dd>
                        
                        <dt class="col-sm-4">Date Reported:</dt>
                        <dd class="col-sm-8"><?php echo formatDate($fault['created_at']); ?></dd>
                        
                        <dt class="col-sm-4">Last Updated:</dt>
                        <dd class="col-sm-8"><?php echo formatDate($fault['updated_at']); ?></dd>
                        
                        <?php if ($fault['assigned_department']): ?>
                        <dt class="col-sm-4">Department:</dt>
                        <dd class="col-sm-8"><?php echo getDepartmentName($fault['assigned_department']); ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($fault['assigned_name']): ?>
                        <dt class="col-sm-4">Assigned To:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($fault['assigned_name']); ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($fault['estimated_cost']): ?>
                        <dt class="col-sm-4">Estimated Cost:</dt>
                        <dd class="col-sm-8">ZWL <?php echo number_format($fault['estimated_cost'], 2); ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($fault['actual_cost']): ?>
                        <dt class="col-sm-4">Actual Cost:</dt>
                        <dd class="col-sm-8">ZWL <?php echo number_format($fault['actual_cost'], 2); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            
            <!-- Description -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Description</h6>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($fault['description'])); ?></p>
                </div>
            </div>
            
            <?php if ($fault['resolution_notes']): ?>
            <!-- Resolution Notes -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Resolution Notes</h6>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($fault['resolution_notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Evidence Files -->
            <?php if (!empty($evidence_files)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Evidence Files</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($evidence_files as $file): ?>
                            <div class="col-md-4 mb-2">
                                <a href="../uploads/evidence/<?php echo htmlspecialchars($file); ?>" 
                                   target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-file me-2"></i>
                                    <?php echo htmlspecialchars($file); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Reporter Information -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Reporter Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong><?php echo htmlspecialchars($fault['reporter_name']); ?></strong>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-envelope text-muted me-1"></i>
                        <a href="mailto:<?php echo htmlspecialchars($fault['reporter_email']); ?>">
                            <?php echo htmlspecialchars($fault['reporter_email']); ?>
                        </a>
                    </div>
                    <?php if ($fault['reporter_phone']): ?>
                    <div class="mb-2">
                        <i class="fas fa-phone text-muted me-1"></i>
                        <a href="tel:<?php echo htmlspecialchars($fault['reporter_phone']); ?>">
                            <?php echo htmlspecialchars($fault['reporter_phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($fault['reporter_address']): ?>
                    <div class="mb-2">
                        <i class="fas fa-map-marker-alt text-muted me-1"></i>
                        <?php echo htmlspecialchars($fault['reporter_address']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Status History -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Status History</h6>
                </div>
                <div class="card-body">
                    <?php if ($status_history): ?>
                        <div class="timeline">
                            <?php foreach ($status_history as $entry): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?php echo getStatusBadgeClass($entry['new_status']); ?>"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?php echo getFaultStatusName($entry['new_status']); ?></h6>
                                        <p class="text-muted mb-1"><?php echo formatDate($entry['created_at']); ?></p>
                                        <?php if ($entry['changed_by_name']): ?>
                                            <small class="text-muted">by <?php echo htmlspecialchars($entry['changed_by_name']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($entry['notes']): ?>
                                            <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No status history available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($user['role'] === 'admin'): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary" onclick="updateFaultStatus(<?php echo $fault['id']; ?>)">
                    <i class="fas fa-edit me-2"></i>Update Status
                </button>
                <?php if ($fault['status'] === 'submitted'): ?>
                <button type="button" class="btn btn-success" onclick="assignFault(<?php echo $fault['id']; ?>)">
                    <i class="fas fa-user-check me-2"></i>Assign Fault
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <style>
    .timeline {
        position: relative;
        padding-left: 20px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -16px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid #fff;
        top: 0;
    }
    
    .timeline-content {
        padding-left: 20px;
    }
    </style>
    <?php
    return ob_get_clean();
}

function generateTrackingHTML($fault, $status_history) {
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Fault Progress Tracking</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5><?php echo htmlspecialchars($fault['title']); ?></h5>
                        <p class="text-muted">Reference: <?php echo htmlspecialchars($fault['reference_number']); ?></p>
                    </div>
                    
                    <!-- Progress Steps -->
                    <div class="progress-steps mb-4">
                        <?php
                        $steps = [
                            'submitted' => 'Submitted',
                            'assigned' => 'Assigned',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved'
                        ];
                        
                        $current_step = $fault['status'];
                        $step_reached = false;
                        ?>
                        
                        <div class="d-flex justify-content-between">
                            <?php foreach ($steps as $step => $label): ?>
                                <?php
                                $is_current = ($step === $current_step);
                                $is_completed = !$step_reached && !$is_current;
                                if ($is_current) $step_reached = true;
                                ?>
                                <div class="step <?php echo $is_completed ? 'completed' : ($is_current ? 'current' : ''); ?>">
                                    <div class="step-circle">
                                        <?php if ($is_completed): ?>
                                            <i class="fas fa-check"></i>
                                        <?php else: ?>
                                            <?php echo array_search($step, array_keys($steps)) + 1; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-label"><?php echo $label; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Current Status -->
                    <div class="alert alert-info">
                        <h6>Current Status: <?php echo getFaultStatusName($fault['status']); ?></h6>
                        <p class="mb-0">Last updated: <?php echo formatDate($fault['updated_at']); ?></p>
                    </div>
                    
                    <!-- Status History -->
                    <h6>Status History</h6>
                    <div class="timeline">
                        <?php foreach ($status_history as $entry): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-<?php echo getStatusBadgeClass($entry['new_status']); ?>"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1"><?php echo getFaultStatusName($entry['new_status']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo formatDate($entry['created_at']); ?></p>
                                    <?php if ($entry['changed_by_name']): ?>
                                        <small class="text-muted">by <?php echo htmlspecialchars($entry['changed_by_name']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($entry['notes']): ?>
                                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Fault Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Category:</dt>
                        <dd class="col-sm-6"><?php echo getFaultCategoryName($fault['category']); ?></dd>
                        
                        <dt class="col-sm-6">Priority:</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-<?php echo $fault['priority'] === 'high' ? 'danger' : ($fault['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($fault['priority']); ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-6">Location:</dt>
                        <dd class="col-sm-6"><?php echo htmlspecialchars($fault['location']); ?></dd>
                        
                        <dt class="col-sm-6">Reported:</dt>
                        <dd class="col-sm-6"><?php echo formatDate($fault['created_at']); ?></dd>
                        
                        <?php if ($fault['assigned_department']): ?>
                        <dt class="col-sm-6">Department:</dt>
                        <dd class="col-sm-6"><?php echo getDepartmentName($fault['assigned_department']); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .progress-steps .step {
        text-align: center;
        flex: 1;
        position: relative;
    }
    
    .progress-steps .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 50%;
        right: -50%;
        height: 2px;
        background-color: #dee2e6;
        z-index: 1;
    }
    
    .progress-steps .step.completed::after {
        background-color: #28a745;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        position: relative;
        z-index: 2;
        color: #6c757d;
    }
    
    .step.completed .step-circle {
        background-color: #28a745;
        color: white;
    }
    
    .step.current .step-circle {
        background-color: #007bff;
        color: white;
    }
    
    .step-label {
        font-size: 12px;
        color: #6c757d;
    }
    
    .step.completed .step-label {
        color: #28a745;
        font-weight: bold;
    }
    
    .step.current .step-label {
        color: #007bff;
        font-weight: bold;
    }
    
    .timeline {
        position: relative;
        padding-left: 20px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -16px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid #fff;
        top: 0;
    }
    
    .timeline-content {
        padding-left: 20px;
    }
    </style>
    <?php
    return ob_get_clean();
}
?>
