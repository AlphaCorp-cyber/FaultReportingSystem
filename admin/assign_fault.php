<?php
$page_title = 'Assign Fault';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();
$fault_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

if (!$fault_id) {
    if ($is_modal) {
        echo '<div class="alert alert-danger">Invalid fault ID</div>';
        exit();
    } else {
        $_SESSION['error'] = 'Invalid fault ID';
        header('Location: manage_faults.php');
        exit();
    }
}

// Get fault details
$fault = $db->selectOne(
    "SELECT fr.*, CONCAT(u.first_name, ' ', u.last_name) as reporter_name, u.email as reporter_email
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     WHERE fr.id = ?",
    [$fault_id]
);

if (!$fault) {
    if ($is_modal) {
        echo '<div class="alert alert-danger">Fault not found</div>';
        exit();
    } else {
        $_SESSION['error'] = 'Fault not found';
        header('Location: manage_faults.php');
        exit();
    }
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assigned_to = (int)$_POST['assigned_to'];
    $department = sanitizeInput($_POST['department']);
    $priority = sanitizeInput($_POST['priority']);
    $estimated_cost = !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null;
    $notes = sanitizeInput($_POST['notes']);
    
    if (!$assigned_to || !$department) {
        $error = 'Please select an administrator and department';
    } else {
        try {
            // Update fault assignment
            $updated = $db->update(
                "UPDATE fault_reports SET 
                    assigned_to = ?, 
                    assigned_department = ?, 
                    priority = ?, 
                    estimated_cost = ?, 
                    status = 'assigned', 
                    updated_at = NOW() 
                WHERE id = ?",
                [$assigned_to, $department, $priority, $estimated_cost, $fault_id]
            );
            
            if ($updated) {
                // Add status history
                $db->insert(
                    "INSERT INTO fault_status_history (fault_id, old_status, new_status, changed_by, notes, created_at) 
                     VALUES (?, ?, 'assigned', ?, ?, NOW())",
                    [$fault_id, $fault['status'], $user['id'], $notes]
                );
                
                // Send notification to assigned user
                sendNotification(
                    $assigned_to,
                    "You have been assigned fault report #{$fault['reference_number']} - {$fault['title']}",
                    'info'
                );
                
                // Send notification to reporter
                sendNotification(
                    $fault['user_id'],
                    "Your fault report #{$fault['reference_number']} has been assigned to our team for resolution",
                    'info'
                );
                
                logActivity($user['id'], 'fault_assigned', "Assigned fault #{$fault['reference_number']} to user $assigned_to");
                
                if ($is_modal) {
                    echo '<div class="alert alert-success">Fault assigned successfully</div>';
                    echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
                } else {
                    $_SESSION['success'] = 'Fault assigned successfully';
                    header('Location: manage_faults.php');
                }
                exit();
            } else {
                $error = 'Failed to assign fault. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Fault assignment error: " . $e->getMessage());
            $error = 'An error occurred while assigning the fault. Please try again.';
        }
    }
}

// Get admin users for assignment
$admin_users = $db->select(
    "SELECT id, first_name, last_name, email FROM users WHERE role = 'admin' AND status = 'active' ORDER BY first_name, last_name"
);

if (!$is_modal) {
    include '../includes/header.php';
}
?>

<?php if (!$is_modal): ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
<?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Assign Fault Report</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Fault Summary -->
                    <div class="alert alert-info">
                        <h6>Fault Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Reference:</strong> <?php echo htmlspecialchars($fault['reference_number']); ?><br>
                                <strong>Title:</strong> <?php echo htmlspecialchars($fault['title']); ?><br>
                                <strong>Category:</strong> <?php echo getFaultCategoryName($fault['category']); ?><br>
                                <strong>Priority:</strong> <span class="badge bg-<?php echo $fault['priority'] === 'high' ? 'danger' : ($fault['priority'] === 'medium' ? 'warning' : 'info'); ?>"><?php echo ucfirst($fault['priority']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Reporter:</strong> <?php echo htmlspecialchars($fault['reporter_name']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($fault['reporter_email']); ?><br>
                                <strong>Location:</strong> <?php echo htmlspecialchars($fault['location']); ?><br>
                                <strong>Date:</strong> <?php echo formatDate($fault['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Assign To *</label>
                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                <option value="">Select administrator...</option>
                                <?php foreach ($admin_users as $admin): ?>
                                    <option value="<?php echo $admin['id']; ?>" 
                                            <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $admin['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                        (<?php echo htmlspecialchars($admin['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label">Department *</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select department...</option>
                                <?php foreach (DEPARTMENTS as $key => $name): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo (isset($_POST['department']) && $_POST['department'] === $key) ? 'selected' : ''; ?>
                                            <?php echo ($fault['assigned_department'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority Level</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?php echo ($fault['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo ($fault['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo ($fault['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                            </select>
                            <small class="form-text text-muted">You can adjust the priority level based on your assessment</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="estimated_cost" class="form-label">Estimated Cost (ZWL)</label>
                            <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" 
                                   min="0" step="0.01" placeholder="0.00">
                            <small class="form-text text-muted">Optional: Provide an estimated cost for the repair</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Assignment Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Add any notes or instructions for the assigned administrator..."></textarea>
                        </div>
                        
                        <div class="<?php echo $is_modal ? 'text-end' : 'd-flex justify-content-between'; ?>">
                            <?php if (!$is_modal): ?>
                                <a href="manage_faults.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Manage Faults
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-check me-2"></i>Assign Fault
                            </button>
                        </div>
                    </form>
                </div>
            </div>

<?php if (!$is_modal): ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Fault Description -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Fault Description</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($fault['description'])); ?></p>
                </div>
            </div>
            
            <!-- Evidence Files -->
            <?php if (!empty($fault['evidence_files'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Evidence Files</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $evidence_files = json_decode($fault['evidence_files'], true);
                        if ($evidence_files):
                        ?>
                            <div class="row">
                                <?php foreach ($evidence_files as $file): ?>
                                    <div class="col-12 mb-2">
                                        <a href="../uploads/evidence/<?php echo htmlspecialchars($file); ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                            <i class="fas fa-file me-2"></i>
                                            <?php echo htmlspecialchars($file); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Assignment History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Status History</h5>
                </div>
                <div class="card-body">
                    <?php
                    $history = $db->select(
                        "SELECT fsh.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                         FROM fault_status_history fsh
                         LEFT JOIN users u ON fsh.changed_by = u.id
                         WHERE fsh.fault_id = ?
                         ORDER BY fsh.created_at DESC",
                        [$fault_id]
                    );
                    
                    if ($history):
                    ?>
                        <div class="timeline">
                            <?php foreach ($history as $entry): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">
                                            <?php echo getFaultStatusName($entry['new_status']); ?>
                                        </h6>
                                        <p class="text-muted mb-1">
                                            <?php echo formatDate($entry['created_at']); ?>
                                        </p>
                                        <?php if ($entry['changed_by_name']): ?>
                                            <small class="text-muted">
                                                by <?php echo htmlspecialchars($entry['changed_by_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($entry['notes']): ?>
                                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></p>
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
</div>
<?php endif; ?>

<script>
// Auto-select department based on category
document.getElementById('department').addEventListener('change', function() {
    const category = '<?php echo $fault['category']; ?>';
    const department = this.value;
    
    // Auto-adjust priority based on department
    const highPriorityDepts = ['water', 'electricity'];
    const prioritySelect = document.getElementById('priority');
    
    if (highPriorityDepts.includes(department)) {
        prioritySelect.value = 'medium';
    }
});

// Pre-select department based on category
document.addEventListener('DOMContentLoaded', function() {
    const category = '<?php echo $fault['category']; ?>';
    const departmentSelect = document.getElementById('department');
    
    // Auto-select department based on category
    const categoryToDepartment = {
        'water': 'water',
        'roads': 'roads',
        'electricity': 'electricity',
        'streetlights': 'electricity',
        'waste': 'waste',
        'parks': 'parks',
        'other': 'general'
    };
    
    if (categoryToDepartment[category] && !departmentSelect.value) {
        departmentSelect.value = categoryToDepartment[category];
    }
});
</script>

<?php if (!$is_modal) include '../includes/footer.php'; ?>
