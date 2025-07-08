<?php
$page_title = 'Admin Dashboard';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Get overall statistics
$stats = $db->selectOne(
    "SELECT 
        COUNT(*) as total_faults,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
    FROM fault_reports"
);

// Get recent fault reports
$recent_faults = $db->select(
    "SELECT fr.*, CONCAT(u.first_name, ' ', u.last_name) as reporter_name, u.email as reporter_email
     FROM fault_reports fr
     JOIN users u ON fr.user_id = u.id
     ORDER BY fr.created_at DESC 
     LIMIT 10"
);

// Get category breakdown
$category_stats = $db->select(
    "SELECT 
        category,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM fault_reports 
    GROUP BY category 
    ORDER BY count DESC"
);

// Get department performance
$department_stats = $db->select(
    "SELECT 
        assigned_department,
        COUNT(*) as total_assigned,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
        AVG(CASE WHEN status = 'resolved' THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_resolution_days
    FROM fault_reports 
    WHERE assigned_department IS NOT NULL
    GROUP BY assigned_department"
);

// Get pending assignments
$pending_assignments = $db->select(
    "SELECT COUNT(*) as count 
     FROM fault_reports 
     WHERE status = 'submitted' OR assigned_to IS NULL"
);

// Get user statistics
$user_stats = $db->selectOne(
    "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'resident' THEN 1 ELSE 0 END) as residents,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
    FROM users"
);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Admin Dashboard</h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                </div>
                <div>
                    <span class="badge bg-primary">Last updated: <?php echo date('M j, Y g:i A'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo $stats['total_faults']; ?></h4>
                            <p class="mb-0">Total Faults</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                    <small class="text-light">
                        <i class="fas fa-arrow-up me-1"></i>
                        <?php echo $stats['last_24h']; ?> new in last 24h
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo $stats['submitted'] + $stats['assigned']; ?></h4>
                            <p class="mb-0">Pending</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                    <small class="text-light">
                        <?php echo $stats['high_priority']; ?> high priority
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo $stats['in_progress']; ?></h4>
                            <p class="mb-0">In Progress</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tools fa-2x"></i>
                        </div>
                    </div>
                    <small class="text-light">
                        Being worked on
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo $stats['resolved']; ?></h4>
                            <p class="mb-0">Resolved</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                    <small class="text-light">
                        <?php echo $stats['total_faults'] > 0 ? round(($stats['resolved'] / $stats['total_faults']) * 100, 1) : 0; ?>% resolution rate
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Fault Reports -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Fault Reports</h5>
                    <a href="manage_faults.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Reporter</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
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
                                            <div>
                                                <strong><?php echo htmlspecialchars($fault['reporter_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($fault['reporter_email']); ?></small>
                                            </div>
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
                                            <small>
                                                <?php echo formatDate($fault['created_at']); ?>
                                                <br>
                                                <span class="text-muted"><?php echo getTimeAgo($fault['created_at']); ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewFaultDetails(<?php echo $fault['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($fault['status'] === 'submitted'): ?>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="assignFault(<?php echo $fault['id']; ?>)">
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
                </div>
            </div>
        </div>

        <!-- Quick Stats and Actions -->
        <div class="col-lg-4">
            <!-- Category Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Category Breakdown</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="manage_faults.php" class="btn btn-primary">
                            <i class="fas fa-tools me-2"></i>Manage Faults
                        </a>
                        <a href="reports.php" class="btn btn-outline-secondary">
                            <i class="fas fa-chart-bar me-2"></i>Generate Reports
                        </a>
                        <a href="analytics.php" class="btn btn-outline-secondary">
                            <i class="fas fa-analytics me-2"></i>View Analytics
                        </a>
                        <a href="manage_users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Users</span>
                            <span class="badge bg-info"><?php echo $user_stats['total_users']; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Active Users</span>
                            <span class="badge bg-success"><?php echo $user_stats['active_users']; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Residents</span>
                            <span class="badge bg-primary"><?php echo $user_stats['residents']; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Administrators</span>
                            <span class="badge bg-warning"><?php echo $user_stats['admins']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fault Details Modal -->
<div class="modal fade" id="faultDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fault Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="faultDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Fault</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="assignmentContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Category Chart
const categoryData = <?php echo json_encode($category_stats); ?>;
const categoryLabels = categoryData.map(item => item.category);
const categoryCounts = categoryData.map(item => item.count);

const ctx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: categoryLabels.map(label => {
            const categories = <?php echo json_encode(FAULT_CATEGORIES); ?>;
            return categories[label] || label;
        }),
        datasets: [{
            data: categoryCounts,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#ffc107',
                '#dc3545',
                '#6c757d',
                '#17a2b8',
                '#fd7e14'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

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

function assignFault(faultId) {
    fetch(`assign_fault.php?id=${faultId}&modal=true`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('assignmentContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading assignment form');
        });
}

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000);
</script>

<?php include '../includes/footer.php'; ?>
