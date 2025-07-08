<?php
$page_title = 'Dashboard';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('resident');

$user = getCurrentUser();

// Get user's fault statistics
$stats = $db->selectOne(
    "SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM fault_reports WHERE user_id = ?",
    [$user['id']]
);

// Get recent fault reports
$recent_faults = $db->select(
    "SELECT * FROM fault_reports 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 5",
    [$user['id']]
);

// Get unread notifications
$notifications = $db->select(
    "SELECT * FROM notifications 
     WHERE user_id = ? AND is_read = false 
     ORDER BY created_at DESC 
     LIMIT 5",
    [$user['id']]
);

include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                    <p class="text-muted">Manage your fault reports and track their progress</p>
                </div>
                <div>
                    <a href="submit_fault.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Report New Fault
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                    <h5 class="card-title"><?php echo $stats['total_reports']; ?></h5>
                    <p class="card-text text-muted">Total Reports</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-secondary mb-2"></i>
                    <h5 class="card-title"><?php echo $stats['submitted']; ?></h5>
                    <p class="card-text text-muted">Submitted</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-user-check fa-2x text-info mb-2"></i>
                    <h5 class="card-title"><?php echo $stats['assigned']; ?></h5>
                    <p class="card-text text-muted">Assigned</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                    <h5 class="card-title"><?php echo $stats['in_progress']; ?></h5>
                    <p class="card-text text-muted">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h5 class="card-title"><?php echo $stats['resolved']; ?></h5>
                    <p class="card-text text-muted">Resolved</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle fa-2x text-dark mb-2"></i>
                    <h5 class="card-title"><?php echo $stats['closed']; ?></h5>
                    <p class="card-text text-muted">Closed</p>
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
                    <a href="my_faults.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_faults)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No fault reports yet</p>
                            <a href="submit_fault.php" class="btn btn-primary">Report Your First Fault</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Title</th>
                                        <th>Category</th>
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
                                                <strong><?php echo htmlspecialchars($fault['title']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo getFaultCategoryName($fault['category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($fault['status']); ?>">
                                                    <?php echo getFaultStatusName($fault['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($fault['created_at']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewFaultDetails(<?php echo $fault['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
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

        <!-- Notifications -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Notifications</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No new notifications</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted"><?php echo getTimeAgo($notification['created_at']); ?></small>
                                        <span class="badge bg-<?php echo $notification['type']; ?>">
                                            <?php echo ucfirst($notification['type']); ?>
                                        </span>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="submit_fault.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Report New Fault
                        </a>
                        <a href="my_faults.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>View All Reports
                        </a>
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user me-2"></i>Update Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Emergency Contacts</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <i class="fas fa-phone text-danger me-2"></i>
                        <strong>Emergency:</strong> 999
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-tint text-primary me-2"></i>
                        <strong>Water:</strong> +263 54 123 4567
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        <strong>Electricity:</strong> +263 54 123 4569
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-road text-secondary me-2"></i>
                        <strong>Roads:</strong> +263 54 123 4568
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

// Mark notifications as read when clicked
document.querySelectorAll('.list-group-item').forEach(item => {
    item.addEventListener('click', function() {
        // Mark notification as read
        this.style.opacity = '0.7';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
