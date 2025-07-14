<?php
$page_title = 'Verify Users';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Require admin authentication
requireAuth();
requireRole('admin');

$user = getCurrentUser();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = (int)($_POST['request_id'] ?? 0);
    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    if ($action === 'approve') {
        $result = $auth->approveVerificationRequest($request_id, $user['id'], $admin_notes);
        if ($result['success']) {
            $_SESSION['success'] = 'User verification approved successfully';
            logActivity($user['id'], 'verify_user', "Approved verification request ID: $request_id");
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } elseif ($action === 'reject') {
        if (empty($admin_notes)) {
            $_SESSION['error'] = 'Please provide a reason for rejection';
        } else {
            $result = $auth->rejectVerificationRequest($request_id, $user['id'], $admin_notes);
            if ($result['success']) {
                $_SESSION['success'] = 'User verification rejected successfully';
                logActivity($user['id'], 'verify_user', "Rejected verification request ID: $request_id");
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
    }
    
    header('Location: verify_users.php');
    exit();
}

// Get pending verification requests
$pending_requests = $db->select("
    SELECT vr.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.created_at as user_created_at
    FROM user_verification_requests vr
    JOIN users u ON vr.user_id = u.id
    WHERE vr.status = 'pending'
    ORDER BY vr.created_at ASC
");

// Get recent verification history
$recent_verifications = $db->select("
    SELECT vr.*, u.first_name, u.last_name, u.email, 
           admin.first_name as admin_first_name, admin.last_name as admin_last_name
    FROM user_verification_requests vr
    JOIN users u ON vr.user_id = u.id
    LEFT JOIN users admin ON vr.reviewed_by = admin.id
    WHERE vr.status IN ('approved', 'rejected')
    ORDER BY vr.reviewed_at DESC
    LIMIT 10
");

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>User Verification</h2>
                    <p class="text-muted">Review and approve user registration requests</p>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">User Verification</li>
                    </ol>
                </nav>
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

    <div class="row">
        <!-- Pending Requests -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-clock me-2"></i>
                        Pending Verification Requests
                        <span class="badge bg-warning"><?php echo count($pending_requests); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_requests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>No Pending Requests</h5>
                            <p class="text-muted">All user verification requests have been processed.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User Details</th>
                                        <th>Documents</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-primary text-white me-3">
                                                        <?php echo strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($request['email']); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($request['phone']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <a href="<?php echo htmlspecialchars($request['national_id_path']); ?>" 
                                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                                        <i class="fas fa-id-card me-1"></i>National ID
                                                    </a>
                                                    <a href="<?php echo htmlspecialchars($request['photo_path']); ?>" 
                                                       class="btn btn-outline-info btn-sm" target="_blank">
                                                        <i class="fas fa-camera me-1"></i>Photo
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-muted small"><?php echo getTimeAgo($request['created_at']); ?></div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success" onclick="approveUser(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                    <button class="btn btn-danger" onclick="rejectUser(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-times me-1"></i>Reject
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

        <!-- Recent Verifications -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Recent Verifications
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_verifications)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-history fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No recent verifications</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recent_verifications as $verification): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?php echo $verification['status'] === 'approved' ? 'success' : 'danger'; ?>"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($verification['first_name'] . ' ' . $verification['last_name']); ?></h6>
                                        <p class="text-muted small mb-1">
                                            <?php echo ucfirst($verification['status']); ?> by 
                                            <?php echo htmlspecialchars($verification['admin_first_name'] . ' ' . $verification['admin_last_name']); ?>
                                        </p>
                                        <small class="text-muted"><?php echo getTimeAgo($verification['reviewed_at']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve User Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" id="approve_request_id">
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        You are about to approve this user's verification request. They will be able to access the system once approved.
                    </div>
                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" name="admin_notes" id="approve_notes" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject User Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You are about to reject this user's verification request. Please provide a clear reason.
                    </div>
                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">Reason for Rejection *</label>
                        <textarea class="form-control" name="admin_notes" id="reject_notes" rows="3" 
                                  placeholder="Please provide a clear reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveUser(requestId) {
    document.getElementById('approve_request_id').value = requestId;
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function rejectUser(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    document.getElementById('reject_notes').value = '';
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #e9ecef;
}
</style>

<?php include '../includes/footer.php'; ?>