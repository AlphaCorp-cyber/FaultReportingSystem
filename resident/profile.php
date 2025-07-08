<?php
$page_title = 'Profile';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('resident');

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'Please fill in all required fields';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($email !== $user['email']) {
        // Check if new email already exists
        $existing = $db->selectOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
        if ($existing) {
            $error = 'Email address already exists';
        }
    }
    
    // Validate password change if provided
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = 'Please enter your current password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        }
    }
    
    if (empty($error)) {
        try {
            // Update user information
            $update_params = [$first_name, $last_name, $email, $phone, $address, $user['id']];
            $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
            
            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, password = ?, updated_at = NOW() WHERE id = ?";
                array_splice($update_params, 5, 0, [$hashed_password]);
            }
            
            $updated = $db->update($update_query, $update_params);
            
            if ($updated) {
                // Update session data
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                
                logActivity($user['id'], 'profile_updated', 'User updated their profile');
                $success = 'Profile updated successfully';
                
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'An error occurred while updating your profile. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Profile Settings</h4>
                    <p class="text-muted mb-0">Update your personal information and account settings</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <h5>Change Password</h5>
                        <p class="text-muted">Leave password fields blank to keep current password</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Account Number:</strong><br>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['account_number']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>ID Number:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($user['id_number']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Role:</strong><br>
                        <span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong><br>
                        <span class="badge bg-success"><?php echo ucfirst($user['status']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Member Since:</strong><br>
                        <span class="text-muted"><?php echo formatDate($user['created_at']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Last Login:</strong><br>
                        <span class="text-muted">
                            <?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Your Activity</h5>
                </div>
                <div class="card-body">
                    <?php
                    $user_stats = $db->selectOne(
                        "SELECT 
                            COUNT(*) as total_reports,
                            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
                        FROM fault_reports WHERE user_id = ?",
                        [$user['id']]
                    );
                    ?>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="mb-2">
                                <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                            </div>
                            <div class="h4"><?php echo $user_stats['total_reports']; ?></div>
                            <small class="text-muted">Total Reports</small>
                        </div>
                        <div class="col-4">
                            <div class="mb-2">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                            <div class="h4"><?php echo $user_stats['resolved_count']; ?></div>
                            <small class="text-muted">Resolved</small>
                        </div>
                        <div class="col-4">
                            <div class="mb-2">
                                <i class="fas fa-times-circle fa-2x text-dark"></i>
                            </div>
                            <div class="h4"><?php echo $user_stats['closed_count']; ?></div>
                            <small class="text-muted">Closed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('blur', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Show/hide password validation
document.getElementById('new_password').addEventListener('input', function() {
    const currentPassword = document.getElementById('current_password');
    if (this.value.length > 0) {
        currentPassword.required = true;
    } else {
        currentPassword.required = false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
