<?php
$page_title = 'Manage Users';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_user_id = (int)$_POST['user_id'];
    
    switch ($action) {
        case 'activate':
            $updated = $db->update("UPDATE users SET status = 'active' WHERE id = ?", [$target_user_id]);
            if ($updated) {
                logActivity($user['id'], 'user_activated', "Activated user ID: $target_user_id");
                $_SESSION['success'] = 'User activated successfully';
            }
            break;
            
        case 'deactivate':
            $updated = $db->update("UPDATE users SET status = 'inactive' WHERE id = ?", [$target_user_id]);
            if ($updated) {
                logActivity($user['id'], 'user_deactivated', "Deactivated user ID: $target_user_id");
                $_SESSION['success'] = 'User deactivated successfully';
            }
            break;
            
        case 'suspend':
            $updated = $db->update("UPDATE users SET status = 'suspended' WHERE id = ?", [$target_user_id]);
            if ($updated) {
                logActivity($user['id'], 'user_suspended', "Suspended user ID: $target_user_id");
                $_SESSION['success'] = 'User suspended successfully';
            }
            break;
            
        case 'promote':
            $updated = $db->update("UPDATE users SET role = 'admin' WHERE id = ?", [$target_user_id]);
            if ($updated) {
                logActivity($user['id'], 'user_promoted', "Promoted user ID: $target_user_id to admin");
                $_SESSION['success'] = 'User promoted to admin successfully';
            }
            break;
            
        case 'demote':
            $updated = $db->update("UPDATE users SET role = 'resident' WHERE id = ?", [$target_user_id]);
            if ($updated) {
                logActivity($user['id'], 'user_demoted', "Demoted user ID: $target_user_id to resident");
                $_SESSION['success'] = 'User demoted to resident successfully';
            }
            break;
            
        case 'delete':
            // Check if user has any fault reports
            $fault_count = $db->selectOne("SELECT COUNT(*) as count FROM fault_reports WHERE user_id = ?", [$target_user_id]);
            if ($fault_count['count'] > 0) {
                $_SESSION['error'] = 'Cannot delete user with existing fault reports';
            } else {
                $deleted = $db->delete("DELETE FROM users WHERE id = ?", [$target_user_id]);
                if ($deleted) {
                    logActivity($user['id'], 'user_deleted', "Deleted user ID: $target_user_id");
                    $_SESSION['success'] = 'User deleted successfully';
                }
            }
            break;
    }
    
    header('Location: manage_users.php');
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR account_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$total_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$total_result = $db->selectOne($total_query, $params);
$total_users = $total_result['total'];
$total_pages = ceil($total_users / $per_page);

// Get users with fault report statistics
$query = "SELECT u.*, 
                 COUNT(fr.id) as total_reports,
                 SUM(CASE WHEN fr.status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports
          FROM users u
          LEFT JOIN fault_reports fr ON u.id = fr.user_id
          WHERE $where_clause
          GROUP BY u.id
          ORDER BY u.created_at DESC
          LIMIT $per_page OFFSET $offset";
$users = $db->select($query, $params);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Manage Users</h2>
                    <p class="text-muted">View and manage system users</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-2"></i>Add User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php
        $user_summary = $db->selectOne(
            "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                SUM(CASE WHEN role = 'resident' THEN 1 ELSE 0 END) as resident_count,
                SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_active = false THEN 1 ELSE 0 END) as inactive_count
            FROM users"
        );
        ?>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $user_summary['total_users']; ?></h4>
                            <p class="mb-0">Total Users</p>
                        </div>
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $user_summary['active_count']; ?></h4>
                            <p class="mb-0">Active Users</p>
                        </div>
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $user_summary['admin_count']; ?></h4>
                            <p class="mb-0">Administrators</p>
                        </div>
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $user_summary['suspended_count']; ?></h4>
                            <p class="mb-0">Suspended Users</p>
                        </div>
                        <i class="fas fa-user-slash fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, email, or account number">
                </div>
                
                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        <option value="resident" <?php echo ($role_filter === 'resident') ? 'selected' : ''; ?>>Resident</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo ($status_filter === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="manage_users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Users 
                <span class="badge bg-secondary"><?php echo $total_users; ?></span>
            </h5>
            <div>
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_users); ?> of <?php echo $total_users; ?> results
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Account Info</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user_row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3">
                                            <?php echo strtoupper(substr($user_row['first_name'], 0, 1) . substr($user_row['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user_row['first_name'] . ' ' . $user_row['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user_row['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($user_row['phone']): ?>
                                            <i class="fas fa-phone fa-sm text-muted me-1"></i>
                                            <?php echo htmlspecialchars($user_row['phone']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($user_row['address']): ?>
                                            <i class="fas fa-map-marker-alt fa-sm text-muted me-1"></i>
                                            <small><?php echo htmlspecialchars($user_row['address']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user_row['account_number']); ?></span><br>
                                        <small class="text-muted">ID: <?php echo htmlspecialchars($user_row['id_number']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user_row['role'] === 'admin' ? 'warning' : 'info'; ?>">
                                        <?php echo ucfirst($user_row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user_row['status'] === 'active' ? 'success' : ($user_row['status'] === 'suspended' ? 'danger' : 'secondary'); ?>">
                                        <?php echo ucfirst($user_row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <small class="text-muted">
                                            <?php echo $user_row['total_reports']; ?> reports<br>
                                            <?php echo $user_row['resolved_reports']; ?> resolved
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        <?php echo formatDate($user_row['created_at']); ?>
                                        <?php if ($user_row['last_login']): ?>
                                            <br><span class="text-muted">Last: <?php echo getTimeAgo($user_row['last_login']); ?></span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($user_row['status'] === 'active'): ?>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction('deactivate', <?php echo $user_row['id']; ?>, 'deactivate this user')">
                                                    <i class="fas fa-user-times text-warning me-2"></i>Deactivate
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction('suspend', <?php echo $user_row['id']; ?>, 'suspend this user')">
                                                    <i class="fas fa-user-slash text-danger me-2"></i>Suspend
                                                </a></li>
                                            <?php else: ?>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction('activate', <?php echo $user_row['id']; ?>, 'activate this user')">
                                                    <i class="fas fa-user-check text-success me-2"></i>Activate
                                                </a></li>
                                            <?php endif; ?>
                                            
                                            <?php if ($user_row['role'] === 'resident'): ?>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction('promote', <?php echo $user_row['id']; ?>, 'promote this user to admin')">
                                                    <i class="fas fa-user-shield text-primary me-2"></i>Promote to Admin
                                                </a></li>
                                            <?php elseif ($user_row['role'] === 'admin' && $user_row['id'] != $user['id']): ?>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction('demote', <?php echo $user_row['id']; ?>, 'demote this user to resident')">
                                                    <i class="fas fa-user text-info me-2"></i>Demote to Resident
                                                </a></li>
                                            <?php endif; ?>
                                            
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="viewUserDetails(<?php echo $user_row['id']; ?>)">
                                                <i class="fas fa-eye text-info me-2"></i>View Details
                                            </a></li>
                                            
                                            <?php if ($user_row['id'] != $user['id']): ?>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmAction('delete', <?php echo $user_row['id']; ?>, 'permanently delete this user')">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="text" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control" placeholder="Last Name" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="resident">Resident</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <small>User will receive email with login credentials</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Action Form -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="user_id" id="targetUserId">
</form>

<script>
function confirmAction(action, userId, description) {
    if (confirm(`Are you sure you want to ${description}?`)) {
        document.getElementById('actionType').value = action;
        document.getElementById('targetUserId').value = userId;
        document.getElementById('actionForm').submit();
    }
}

function viewUserDetails(userId) {
    fetch(`../api/get_user_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userDetailsContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                modal.show();
            } else {
                alert('Error loading user details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading user details');
        });
}

// Add user form submission
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Implementation for adding new user would go here
    alert('Add user functionality would be implemented here');
});
</script>

<?php include '../includes/footer.php'; ?>
