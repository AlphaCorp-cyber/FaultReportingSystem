
<?php
$page_title = 'Section Dashboard';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Get user's department or allow department selection for admins
$user_department = null;
$selected_department = isset($_GET['dept']) ? $_GET['dept'] : null;

// If user has a specific department assigned, use that
// Otherwise, allow department selection (for super admins)
if ($selected_department && isValidDepartment($selected_department)) {
    $user_department = $selected_department;
} else {
    // Default to first available department or allow selection
    $departments_list = array_keys(DEPARTMENTS);
    $user_department = $departments_list[0] ?? 'general';
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $fault_id = (int)$_POST['fault_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update_status':
            $new_status = $_POST['status'];
            $notes = sanitizeInput($_POST['notes']);
            
            // Verify this fault belongs to the current department
            $fault_check = $db->selectOne(
                "SELECT id FROM fault_reports WHERE id = ? AND assigned_department = ?",
                [$fault_id, $user_department]
            );
            
            if ($fault_check) {
                $updated = $db->update(
                    "UPDATE fault_reports SET status = ?, resolution_notes = ?, updated_at = NOW() WHERE id = ?",
                    [$new_status, $notes, $fault_id]
                );
                
                if ($updated) {
                    // Log status change
                    $db->insert(
                        "INSERT INTO fault_status_history (fault_id, old_status, new_status, changed_by, notes, created_at) 
                         SELECT id, status, ?, ?, ?, NOW() FROM fault_reports WHERE id = ?",
                        [$new_status, $user['id'], $notes, $fault_id]
                    );
                    
                    // Send notification to user
                    $fault = $db->selectOne("SELECT * FROM fault_reports WHERE id = ?", [$fault_id]);
                    if ($fault) {
                        sendNotification(
                            $fault['user_id'],
                            "Your fault report #{$fault['reference_number']} status has been updated to " . getFaultStatusName($new_status),
                            'info'
                        );
                    }
                    
                    logActivity($user['id'], 'fault_status_updated', "Updated fault #$fault_id status to $new_status");
                    $_SESSION['success'] = 'Fault status updated successfully';
                }
            } else {
                $_SESSION['error'] = 'Unauthorized action';
            }
            break;
    }
    
    header('Location: section_dashboard.php?dept=' . $user_department);
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

// Get pending assignments (faults assigned to this department but not yet taken by specific user)
$pending_faults = $db->select(
    "SELECT fr.*, 
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     WHERE fr.assigned_department = ? AND fr.status = 'assigned' AND fr.assigned_to IS NULL
     ORDER BY fr.priority DESC, fr.created_at ASC",
    [$user_department]
);

// Get my active faults (if user has specific assignments)
$my_faults = $db->select(
    "SELECT fr.*, 
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     WHERE fr.assigned_to = ? AND fr.status NOT IN ('resolved', 'closed', 'rejected')
     ORDER BY fr.priority DESC, fr.created_at ASC",
    [$user['id']]
);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Department Selection -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><?php echo getDepartmentName($user_department); ?> Section Dashboard</h2>
                    <p class="text-muted">Manage faults assigned to your department section</p>
                </div>
                <div>
                    <select class="form-select" onchange="changeDepartment(this.value)" style="width: auto;">
                        <?php foreach (DEPARTMENTS as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($key === $user_department) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

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
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Pending Assignments 
                        <span class="badge bg-warning"><?php echo count($pending_faults); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_faults)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No pending assignments</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Reporter</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_faults as $fault): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($fault['reference_number']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fault['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo getFaultCategoryName($fault['category']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getPriorityBadgeClass($fault['priority']); ?>">
                                                    <?php echo ucfirst($fault['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($fault['reporter_name']); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo formatDate($fault['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewFaultDetails(<?php echo $fault['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="takeAssignment(<?php echo $fault['id']; ?>)"
                                                        title="Take Assignment">
                                                    <i class="fas fa-hand-paper"></i>
                                                </button>
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
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        My Active Faults 
                        <span class="badge bg-info"><?php echo count($my_faults); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($my_faults)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active assignments</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_faults as $fault): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($fault['reference_number']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fault['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo getFaultCategoryName($fault['category']); ?></small>
                                            </td>
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
                                                <small><?php echo formatDate($fault['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewFaultDetails(<?php echo $fault['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="updateStatus(<?php echo $fault['id']; ?>, '<?php echo $fault['status']; ?>')"
                                                        title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Department Faults</h5>
                    <a href="manage_faults.php?department=<?php echo $user_department; ?>" class="btn btn-sm btn-primary">
                        View All Department Faults
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Title</th>
                                    <th>Reporter</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_faults as $fault): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($fault['reference_number']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($fault['title']); ?></strong><br>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($fault['location']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($fault['reporter_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($fault['reporter_email']); ?></small>
                                            </div>
                                        </td>
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
                                        <td>
                                            <?php if ($fault['assigned_to']): ?>
                                                <?php 
                                                $assigned_user = $db->selectOne("SELECT first_name, last_name FROM users WHERE id = ?", [$fault['assigned_to']]);
                                                echo htmlspecialchars($assigned_user['first_name'] . ' ' . $assigned_user['last_name']);
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo formatDate($fault['created_at']); ?><br>
                                                <span class="text-muted"><?php echo getTimeAgo($fault['created_at']); ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewFaultDetails(<?php echo $fault['id']; ?>)"
                                                    title="View Details">
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

<!-- Modals -->
<div class="modal fade" id="faultDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fault Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="faultDetailsContent"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statusForm" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="fault_id" id="statusFaultId">
                    
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label">Status</label>
                        <select class="form-select" id="statusSelect" name="status" required>
                            <?php foreach (FAULT_STATUSES as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="statusNotes" name="notes" rows="3" 
                                  placeholder="Add any notes about this status change..."></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function changeDepartment(department) {
    window.location.href = 'section_dashboard.php?dept=' + department;
}

function viewFaultDetails(faultId) {
    fetch(`../api/get_fault_details.php?id=${faultId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('faultDetailsContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('faultDetailsModal'));
                modal.show();
            } else {
                alert('Error loading fault details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading fault details');
        });
}

function updateStatus(faultId, currentStatus) {
    document.getElementById('statusFaultId').value = faultId;
    document.getElementById('statusSelect').value = currentStatus;
    
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function takeAssignment(faultId) {
    if (confirm('Are you sure you want to take this assignment?')) {
        fetch('../api/take_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fault_id: faultId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error taking assignment: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error taking assignment');
        });
    }
}

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000);
</script>

<?php include '../includes/footer.php'; ?>
