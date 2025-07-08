<?php
$page_title = 'Departments';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Handle department actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add':
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            $head_of_department = sanitizeInput($_POST['head_of_department']);
            $contact_email = sanitizeInput($_POST['contact_email']);
            $contact_phone = sanitizeInput($_POST['contact_phone']);
            
            if (empty($name)) {
                $_SESSION['error'] = 'Department name is required';
            } else {
                $inserted = $db->insert(
                    "INSERT INTO departments (name, description, head_of_department, contact_email, contact_phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    [$name, $description, $head_of_department, $contact_email, $contact_phone]
                );
                
                if ($inserted) {
                    logActivity($user['id'], 'department_added', "Added new department: $name");
                    $_SESSION['success'] = 'Department added successfully';
                } else {
                    $_SESSION['error'] = 'Failed to add department';
                }
            }
            break;
            
        case 'update':
            $dept_id = (int)$_POST['dept_id'];
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            $head_of_department = sanitizeInput($_POST['head_of_department']);
            $contact_email = sanitizeInput($_POST['contact_email']);
            $contact_phone = sanitizeInput($_POST['contact_phone']);
            
            if (empty($name)) {
                $_SESSION['error'] = 'Department name is required';
            } else {
                $updated = $db->update(
                    "UPDATE departments SET name = ?, description = ?, head_of_department = ?, contact_email = ?, contact_phone = ? WHERE id = ?",
                    [$name, $description, $head_of_department, $contact_email, $contact_phone, $dept_id]
                );
                
                if ($updated) {
                    logActivity($user['id'], 'department_updated', "Updated department: $name");
                    $_SESSION['success'] = 'Department updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update department';
                }
            }
            break;
            
        case 'toggle_status':
            $dept_id = (int)$_POST['dept_id'];
            $current_status = $_POST['current_status'];
            $new_status = $current_status === 'active' ? 'inactive' : 'active';
            
            $updated = $db->update("UPDATE departments SET status = ? WHERE id = ?", [$new_status, $dept_id]);
            
            if ($updated) {
                logActivity($user['id'], 'department_status_changed', "Changed department status to: $new_status");
                $_SESSION['success'] = 'Department status updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update department status';
            }
            break;
    }
    
    header('Location: departments.php');
    exit();
}

// Get departments with fault statistics
$departments = $db->select(
    "SELECT d.*, 
            COUNT(fr.id) as total_faults,
            SUM(CASE WHEN fr.status = 'assigned' THEN 1 ELSE 0 END) as assigned_faults,
            SUM(CASE WHEN fr.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_faults,
            SUM(CASE WHEN fr.status = 'resolved' THEN 1 ELSE 0 END) as resolved_faults,
            AVG(CASE WHEN fr.status = 'resolved' THEN EXTRACT(EPOCH FROM (fr.updated_at - fr.created_at))/86400 ELSE NULL END) as avg_resolution_time
     FROM departments d
     LEFT JOIN fault_reports fr ON d.name = fr.assigned_department
     GROUP BY d.id
     ORDER BY d.name ASC"
);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Department Management</h2>
                    <p class="text-muted">Manage municipal departments and their performance</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                        <i class="fas fa-plus me-2"></i>Add Department
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Overview Cards -->
    <div class="row mb-4">
        <?php
        $dept_summary = $db->selectOne(
            "SELECT 
                COUNT(*) as total_departments,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_departments,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_departments
            FROM departments"
        );
        
        $total_workload = $db->selectOne(
            "SELECT 
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as total_resolved,
                AVG(CASE WHEN status = 'resolved' THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_resolution_time
            FROM fault_reports 
            WHERE assigned_department IS NOT NULL"
        );
        ?>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $dept_summary['total_departments']; ?></h4>
                            <p class="mb-0">Total Departments</p>
                        </div>
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $dept_summary['active_departments']; ?></h4>
                            <p class="mb-0">Active Departments</p>
                        </div>
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $total_workload['total_assigned']; ?></h4>
                            <p class="mb-0">Total Assigned Faults</p>
                        </div>
                        <i class="fas fa-tasks fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $total_workload['avg_resolution_time'] ? round($total_workload['avg_resolution_time'], 1) : 0; ?></h4>
                            <p class="mb-0">Avg Resolution Days</p>
                        </div>
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Departments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Head of Department</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                            <th>Workload</th>
                            <th>Performance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                                        <?php if ($dept['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($dept['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($dept['head_of_department']): ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($dept['head_of_department']); ?></strong>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($dept['contact_email']): ?>
                                            <i class="fas fa-envelope fa-sm text-muted me-1"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($dept['contact_email']); ?>">
                                                <?php echo htmlspecialchars($dept['contact_email']); ?>
                                            </a><br>
                                        <?php endif; ?>
                                        <?php if ($dept['contact_phone']): ?>
                                            <i class="fas fa-phone fa-sm text-muted me-1"></i>
                                            <a href="tel:<?php echo htmlspecialchars($dept['contact_phone']); ?>">
                                                <?php echo htmlspecialchars($dept['contact_phone']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $dept['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($dept['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="mb-1">
                                            <span class="badge bg-primary"><?php echo $dept['total_faults']; ?></span> Total
                                        </div>
                                        <div class="mb-1">
                                            <span class="badge bg-warning"><?php echo $dept['assigned_faults']; ?></span> Assigned
                                        </div>
                                        <div class="mb-1">
                                            <span class="badge bg-info"><?php echo $dept['in_progress_faults']; ?></span> In Progress
                                        </div>
                                        <div>
                                            <span class="badge bg-success"><?php echo $dept['resolved_faults']; ?></span> Resolved
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($dept['total_faults'] > 0): ?>
                                            <?php $resolution_rate = round(($dept['resolved_faults'] / $dept['total_faults']) * 100, 1); ?>
                                            <div class="mb-1">
                                                <small class="text-muted">Resolution Rate:</small>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $resolution_rate >= 80 ? 'success' : ($resolution_rate >= 60 ? 'warning' : 'danger'); ?>" 
                                                         style="width: <?php echo $resolution_rate; ?>%"></div>
                                                </div>
                                                <small><?php echo $resolution_rate; ?>%</small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($dept['avg_resolution_time']): ?>
                                            <div>
                                                <small class="text-muted">Avg Time: <?php echo round($dept['avg_resolution_time'], 1); ?> days</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editDepartment(<?php echo $dept['id']; ?>)"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-<?php echo $dept['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                                onclick="toggleStatus(<?php echo $dept['id']; ?>, '<?php echo $dept['status']; ?>')"
                                                title="<?php echo $dept['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $dept['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewDepartmentDetails(<?php echo $dept['id']; ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="head_of_department" class="form-label">Head of Department</label>
                        <input type="text" class="form-control" id="head_of_department" name="head_of_department">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="dept_id" id="editDeptId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editHeadOfDepartment" class="form-label">Head of Department</label>
                        <input type="text" class="form-control" id="editHeadOfDepartment" name="head_of_department">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editContactEmail" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="editContactEmail" name="contact_email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editContactPhone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="editContactPhone" name="contact_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Department Details Modal -->
<div class="modal fade" id="departmentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Department Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="departmentDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Action Form -->
<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="dept_id" id="statusDeptId">
    <input type="hidden" name="current_status" id="currentStatus">
</form>

<script>
function editDepartment(deptId) {
    // Get department data
    const departments = <?php echo json_encode($departments); ?>;
    const dept = departments.find(d => d.id == deptId);
    
    if (dept) {
        document.getElementById('editDeptId').value = dept.id;
        document.getElementById('editName').value = dept.name;
        document.getElementById('editDescription').value = dept.description || '';
        document.getElementById('editHeadOfDepartment').value = dept.head_of_department || '';
        document.getElementById('editContactEmail').value = dept.contact_email || '';
        document.getElementById('editContactPhone').value = dept.contact_phone || '';
        
        const modal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
        modal.show();
    }
}

function toggleStatus(deptId, currentStatus) {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    if (confirm(`Are you sure you want to ${action} this department?`)) {
        document.getElementById('statusDeptId').value = deptId;
        document.getElementById('currentStatus').value = currentStatus;
        document.getElementById('statusForm').submit();
    }
}

function viewDepartmentDetails(deptId) {
    fetch(`../api/get_department_details.php?id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('departmentDetailsContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('departmentDetailsModal'));
                modal.show();
            } else {
                alert('Error loading department details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading department details');
        });
}
</script>

<?php include '../includes/footer.php'; ?>
