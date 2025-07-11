<?php
$page_title = 'Profile';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Require authentication
requireAuth();

$user = getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $_SESSION['error'] = 'Please fill in all required fields';
    } elseif (!validateEmail($email)) {
        $_SESSION['error'] = 'Please enter a valid email address';
    } else {
        // Check if email is already taken by another user
        $existing_user = $db->selectOne(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$email, $user['id']]
        );
        
        if ($existing_user) {
            $_SESSION['error'] = 'Email address is already in use';
        } else {
            // Update profile
            $updated = $db->update(
                "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$first_name, $last_name, $email, $phone, $user['id']]
            );
            
            if ($updated) {
                // Update session
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                
                logActivity($user['id'], 'profile_updated', 'User updated profile information');
                $_SESSION['success'] = 'Profile updated successfully';
                
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $_SESSION['error'] = 'Failed to update profile';
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Please fill in all password fields';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long';
    } elseif (!password_verify($current_password, $user['password_hash'])) {
        $_SESSION['error'] = 'Current password is incorrect';
    } else {
        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $updated = $db->update(
            "UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$new_password_hash, $user['id']]
        );
        
        if ($updated) {
            logActivity($user['id'], 'password_changed', 'User changed password');
            $_SESSION['success'] = 'Password changed successfully';
        } else {
            $_SESSION['error'] = 'Failed to change password';
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Profile Settings</h2>
                    <p class="text-muted">Manage your account information and preferences</p>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
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
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Profile Information
                    </h5>
                </div>
                <div class="card-body">
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Created</label>
                            <input type="text" class="form-control" value="<?php echo formatDate($user['created_at']); ?>" readonly>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>
                        Security Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 6 characters long</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-warning w-100">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Account Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Last Login:</strong><br>
                        <small class="text-muted"><?php echo $user['last_login'] ? getTimeAgo($user['last_login']) : 'N/A'; ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Account Status:</strong><br>
                        <span class="badge bg-success">Active</span>
                    </div>
                    
                    <?php if ($user['role'] === 'department' && $user['department_code']): ?>
                    <div class="mb-3">
                        <strong>Department Code:</strong><br>
                        <code><?php echo htmlspecialchars($user['department_code']); ?></code>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>