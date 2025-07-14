<?php
$page_title = 'Section Dashboard';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Require authentication
requireAuth();

$user = getCurrentUser();

// Check if user is department or admin
if ($user['role'] !== 'department' && $user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get user's department
$user_department = null;

if ($user['role'] === 'department') {
    // Department users can only see their own department
    $user_department = $user['department'];
} else {
    // Admin users can view any department
    $selected_department = isset($_GET['dept']) ? $_GET['dept'] : null;
    if ($selected_department && isValidDepartment($selected_department)) {
        $user_department = $selected_department;
    } else {
        $departments_list = array_keys(DEPARTMENTS);
        $user_department = $departments_list[0] ?? 'general';
    }
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $fault_id = intval($_POST['fault_id'] ?? 0);

    switch ($action) {
        case 'take_assignment':
            $result = $db->update(
                "UPDATE fault_reports SET assigned_to = ?, status = 'in_progress', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND assigned_department = ?",
                [$user['id'], $fault_id, $user_department]
            );

            if ($result) {
                logActivity($user['id'], 'fault_assignment_taken', "Took assignment for fault ID: $fault_id");
                sendNotification($user['id'], 'Assignment Taken', "You have taken assignment for fault #$fault_id");
                $_SESSION['success'] = 'Assignment taken successfully';
            } else {
                $_SESSION['error'] = 'Failed to take assignment';
            }
            break;

        case 'update_status':
            $new_status = $_POST['status'] ?? '';
            $notes = $_POST['notes'] ?? '';

            if (isValidFaultStatus($new_status)) {
                $result = $db->update(
                    "UPDATE fault_reports SET status = ?, resolution_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND assigned_to = ?",
                    [$new_status, $notes, $fault_id, $user['id']]
                );

                if ($result) {
                    // Add progress update for resident
                    $progress_message = '';
                    switch ($new_status) {
                        case 'in_progress':
                            $progress_message = 'Work has started on your fault report. Our team is actively working on resolving the issue.';
                            break;
                        case 'resolved':
                            $progress_message = 'Your fault report has been resolved. ' . ($notes ? 'Resolution details: ' . $notes : '');
                            break;
                        case 'closed':
                            $progress_message = 'Your fault report has been closed. ' . ($notes ? 'Final notes: ' . $notes : '');
                            break;
                        case 'on_hold':
                            $progress_message = 'Your fault report is temporarily on hold. ' . ($notes ? 'Reason: ' . $notes : '');
                            break;
                        default:
                            $progress_message = 'Status updated to ' . getFaultStatusName($new_status) . ($notes ? '. Notes: ' . $notes : '');
                    }
                    
                    $db->insert(
                        "INSERT INTO fault_progress_updates (fault_id, status, message, created_by) VALUES (?, ?, ?, ?)",
                        [$fault_id, $new_status, $progress_message, $user['id']]
                    );

                    logActivity($user['id'], 'fault_status_updated', "Updated fault status to: $new_status");
                    $_SESSION['success'] = 'Status updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update status';
                }
            }
            break;

        case 'transfer_department':
            $new_department = $_POST['new_department'] ?? '';
            $transfer_reason = $_POST['transfer_reason'] ?? '';
            
            if (isValidDepartment($new_department)) {
                $current_fault = $db->selectOne("SELECT * FROM fault_reports WHERE id = ?", [$fault_id]);
                
                if ($current_fault) {
                    $result = $db->update(
                        "UPDATE fault_reports SET assigned_department = ?, assigned_to = NULL, status = 'assigned', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$new_department, $fault_id]
                    );

                    if ($result) {
                        // Log department transfer
                        $db->insert(
                            "INSERT INTO fault_department_transfers (fault_id, from_department, to_department, reason, transferred_by) VALUES (?, ?, ?, ?, ?)",
                            [$fault_id, $current_fault['assigned_department'], $new_department, $transfer_reason, $user['id']]
                        );

                        // Add progress update for resident
                        $dept_name = getDepartmentName($new_department);
                        $progress_message = "Your fault report has been transferred to the $dept_name for proper handling. " . ($transfer_reason ? "Reason: $transfer_reason" : '');
                        
                        $db->insert(
                            "INSERT INTO fault_progress_updates (fault_id, status, message, created_by) VALUES (?, 'transferred', ?, ?)",
                            [$fault_id, $progress_message, $user['id']]
                        );

                        logActivity($user['id'], 'fault_transferred', "Transferred fault to $new_department");
                        $_SESSION['success'] = 'Fault transferred successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to transfer fault';
                    }
                }
            }
            break;
    }

    header('Location: section_dashboard.php');
    exit();
}

// Get department statistics
$dept_stats = $db->selectOne(
    "SELECT 
        COUNT(*) as total_faults,
        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_faults,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_faults,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_faults,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_faults,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_faults,
        AVG(CASE WHEN status = 'resolved' THEN EXTRACT(EPOCH FROM (updated_at - created_at))/86400 ELSE NULL END) as avg_resolution_time
    FROM fault_reports 
    WHERE assigned_department = ?",
    [$user_department]
);

// Get pending assignments (faults assigned to this department but not yet taken by specific user)
$pending_faults = $db->select(
    "SELECT fr.*, 
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
            u.email as reporter_email,
            u.phone as reporter_phone
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     WHERE fr.assigned_department = ? AND fr.status = 'assigned' AND fr.assigned_to IS NULL
     ORDER BY fr.priority DESC, fr.created_at ASC",
    [$user_department]
);

// Get my active faults (if user has specific assignments)
$my_faults = $db->select(
    "SELECT fr.*, 
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
            u.email as reporter_email,
            u.phone as reporter_phone
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     WHERE fr.assigned_to = ? AND fr.status NOT IN ('resolved', 'closed', 'rejected')
     ORDER BY fr.priority DESC, fr.created_at ASC",
    [$user['id']]
);

// Get recent faults for this department
$recent_faults = $db->select(
    "SELECT fr.*, 
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
            u.email as reporter_email,
            u.phone as reporter_phone
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     WHERE fr.assigned_department = ?
     ORDER BY fr.created_at DESC
     LIMIT 10",
    [$user_department]
);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Department Selection (Only for Admins) -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><?php echo getDepartmentName($user_department); ?> Section Dashboard</h2>
                    <p class="text-muted">
                        <?php if ($user['role'] === 'department'): ?>
                            Welcome to your department dashboard
                        <?php else: ?>
                            Manage faults assigned to this department section
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($user['role'] === 'admin'): ?>
                <div>
                    <select class="form-select" onchange="changeDepartment(this.value)" style="width: auto;">
                        <?php foreach (DEPARTMENTS as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($key === $user_department) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Department Statistics -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $dept_stats['total_faults']; ?></h3>
                    <p class="mb-0">Total Faults</p>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3><?php echo $dept_stats['assigned_faults']; ?></h3>
                    <p class="mb-0">Assigned</p>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo $dept_stats['in_progress_faults']; ?></h3>
                    <p class="mb-0">In Progress</p>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo $dept_stats['resolved_faults']; ?></h3>
                    <p class="mb-0">Resolved</p>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?php echo $dept_stats['high_priority_faults']; ?></h3>
                    <p class="mb-0">High Priority</p>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $dept_stats['avg_resolution_time'] ? round($dept_stats['avg_resolution_time'], 1) : '0'; ?></h3>
                    <p class="mb-0">Avg Days</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Assignments -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Pending Assignments (<?php echo count($pending_faults); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_faults)): ?>
                        <p class="text-muted text-center py-3">No pending assignments</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ref#</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Reporter</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_faults as $fault): ?>
                                        <tr>
                                            <td><?php echo $fault['reference_number']; ?></td>
                                            <td><?php echo htmlspecialchars($fault['title']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getPriorityBadgeClass($fault['priority']); ?>">
                                                    <?php echo ucfirst($fault['priority']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($fault['reporter_name']); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="take_assignment">
                                                    <input type="hidden" name="fault_id" value="<?php echo $fault['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-hand-paper"></i> Take
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Active Faults -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-2"></i>
                        My Active Faults (<?php echo count($my_faults); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($my_faults)): ?>
                        <p class="text-muted text-center py-3">No active assignments</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ref#</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_faults as $fault): ?>
                                        <tr>
                                            <td><?php echo $fault['reference_number']; ?></td>
                                            <td><?php echo htmlspecialchars($fault['title']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($fault['status']); ?>">
                                                    <?php echo getFaultStatusName($fault['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getPriorityBadgeClass($fault['priority']); ?>">
                                                    <?php echo ucfirst($fault['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $fault['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="transferDepartment(<?php echo $fault['id']; ?>)">
                                                        <i class="fas fa-exchange-alt"></i> Transfer
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Department Faults -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Recent Department Faults
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Title</th>
                                    <th>Reporter</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_faults as $fault): ?>
                                    <tr>
                                        <td><?php echo $fault['reference_number']; ?></td>
                                        <td><?php echo htmlspecialchars($fault['title']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($fault['reporter_name']); ?><br>
                                            <small class="text-muted"><?php echo $fault['reporter_email']; ?></small>
                                        </td>
                                        <td><?php echo getFaultCategoryName($fault['category']); ?></td>
                                        <td>
                                            <span class="badge <?php echo getPriorityBadgeClass($fault['priority']); ?>">
                                                <?php echo ucfirst($fault['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($fault['status']); ?>">
                                                <?php echo getFaultStatusName($fault['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo getTimeAgo($fault['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewFaultDetails(<?php echo $fault['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Fault Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="fault_id" id="statusFaultId">

                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" required>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add resolution notes or comments"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
<?php if ($user['role'] === 'admin'): ?>
function changeDepartment(department) {
    window.location.href = 'section_dashboard.php?dept=' + department;
}
<?php endif; ?>

function updateStatus(faultId) {
    document.getElementById('statusFaultId').value = faultId;
    const modal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
    modal.show();
}

function viewFaultDetails(faultId) {
    // Implementation for viewing fault details
    window.location.href = 'manage_faults.php?view=' + faultId;
}

// Auto refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php include '../includes/footer.php'; ?>