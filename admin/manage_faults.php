<?php
$page_title = 'Manage Faults';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $fault_id = (int)$_POST['fault_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update_status':
            $new_status = $_POST['status'];
            $notes = sanitizeInput($_POST['notes']);
            
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
            break;
            
        case 'assign_fault':
            $assigned_to = $_POST['assigned_to'];
            $department = $_POST['department'];
            
            $updated = $db->update(
                "UPDATE fault_reports SET assigned_to = ?, assigned_department = ?, status = 'assigned', updated_at = NOW() WHERE id = ?",
                [$assigned_to, $department, $fault_id]
            );
            
            if ($updated) {
                // Send notification to assigned user
                $fault = $db->selectOne("SELECT * FROM fault_reports WHERE id = ?", [$fault_id]);
                if ($fault) {
                    sendNotification(
                        $assigned_to,
                        "You have been assigned fault report #{$fault['reference_number']}",
                        'info'
                    );
                }
                
                logActivity($user['id'], 'fault_assigned', "Assigned fault #$fault_id to user $assigned_to");
                $_SESSION['success'] = 'Fault assigned successfully';
            }
            break;
    }
    
    header('Location: manage_faults.php');
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "fr.status = ?";
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "fr.category = ?";
    $params[] = $category_filter;
}

if (!empty($priority_filter)) {
    $where_conditions[] = "fr.priority = ?";
    $params[] = $priority_filter;
}

if (!empty($department_filter)) {
    $where_conditions[] = "fr.assigned_department = ?";
    $params[] = $department_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(fr.title LIKE ? OR fr.description LIKE ? OR fr.reference_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$total_query = "SELECT COUNT(*) as total FROM fault_reports fr JOIN users u ON fr.user_id = u.id WHERE $where_clause";
$total_result = $db->selectOne($total_query, $params);
$total_faults = $total_result['total'];
$total_pages = ceil($total_faults / $per_page);

// Get fault reports
$query = "SELECT fr.*, 
                 CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                 u.email as reporter_email,
                 u.phone as reporter_phone,
                 CONCAT(a.first_name, ' ', a.last_name) as assigned_name
          FROM fault_reports fr
          JOIN users u ON fr.user_id = u.id
          LEFT JOIN users a ON fr.assigned_to = a.id
          WHERE $where_clause
          ORDER BY fr.created_at DESC
          LIMIT $per_page OFFSET $offset";
$faults = $db->select($query, $params);

// Get admin users for assignment
$admin_users = $db->select("SELECT id, first_name, last_name FROM users WHERE role = 'admin' AND is_active = true");

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Manage Fault Reports</h2>
                    <p class="text-muted">View, assign, and update fault reports</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="exportData('csv')">
                        <i class="fas fa-download me-2"></i>Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-2">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search...">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach (FAULT_STATUSES as $key => $name): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo ($status_filter === $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach (FAULT_CATEGORIES as $key => $name): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo ($category_filter === $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">All Priorities</option>
                        <option value="high" <?php echo ($priority_filter === 'high') ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo ($priority_filter === 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo ($priority_filter === 'low') ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach (DEPARTMENTS as $key => $name): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo ($department_filter === $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="manage_faults.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Fault Reports 
                <span class="badge bg-secondary"><?php echo $total_faults; ?></span>
            </h5>
            <div>
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_faults); ?> of <?php echo $total_faults; ?> results
            </div>
        </div>
        
        <div class="card-body">
            <?php if (empty($faults)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No fault reports found</h4>
                    <p class="text-muted">Try adjusting your filters or search terms.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Reporter</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faults as $fault): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($fault['reference_number']); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($fault['reporter_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($fault['reporter_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($fault['title']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($fault['location']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo getFaultCategoryName($fault['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $fault['priority'] === 'high' ? 'danger' : ($fault['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($fault['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($fault['status']); ?>">
                                            <?php echo getFaultStatusName($fault['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fault['assigned_name']): ?>
                                            <small><?php echo htmlspecialchars($fault['assigned_name']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo formatDate($fault['created_at']); ?>
                                            <br>
                                            <span class="text-muted"><?php echo getTimeAgo($fault['created_at']); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
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
                                            <?php if ($fault['status'] === 'submitted' || !$fault['assigned_to']): ?>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="assignFault(<?php echo $fault['id']; ?>)"
                                                        title="Assign">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>&priority=<?php echo $priority_filter; ?>&department=<?php echo $department_filter; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>&priority=<?php echo $priority_filter; ?>&department=<?php echo $department_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>&priority=<?php echo $priority_filter; ?>&department=<?php echo $department_filter; ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
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

<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Fault</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignForm" method="POST">
                    <input type="hidden" name="action" value="assign_fault">
                    <input type="hidden" name="fault_id" id="assignFaultId">
                    
                    <div class="mb-3">
                        <label for="assignTo" class="form-label">Assign To</label>
                        <select class="form-select" id="assignTo" name="assigned_to" required>
                            <option value="">Select administrator...</option>
                            <?php foreach ($admin_users as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>">
                                    <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignDepartment" class="form-label">Department</label>
                        <select class="form-select" id="assignDepartment" name="department" required>
                            <option value="">Select department...</option>
                            <?php foreach (DEPARTMENTS as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Fault</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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

function assignFault(faultId) {
    document.getElementById('assignFaultId').value = faultId;
    
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}

function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    const exportUrl = `../api/export_faults.php?${params.toString()}`;
    window.open(exportUrl, '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>
