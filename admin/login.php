<?php
$page_title = 'Admin Login';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in as admin
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: dashboard.php');
        exit();
    } else {
        // If logged in as resident, logout first
        session_destroy();
        session_start();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if user exists and is admin
        $user = $db->selectOne(
            "SELECT * FROM users WHERE email = ? AND is_active = true AND role = 'admin'", 
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            try {
                $db->update(
                    "UPDATE users SET updated_at = NOW() WHERE id = ?",
                    [$user['id']]
                );
            } catch (Exception $e) {
                // Continue if update fails
            }
            
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['login_time'] = time();
            
            logActivity($user['id'], 'admin_login', 'Admin user logged in successfully');
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid admin credentials';
            // Log failed login attempt
            error_log("Failed admin login attempt for email: $email from IP: " . $_SERVER['REMOTE_ADDR']);
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-shield fa-3x text-danger mb-2"></i>
                        <h4>Admin Login</h4>
                        <p class="text-muted">Administrative Access Portal</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Admin Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="../auth/login.php" class="text-decoration-none">
                            <i class="fas fa-users me-1"></i>
                            Resident Login Portal
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Demo Admin Credentials -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Demo Admin Credentials</h6>
                    <div class="text-center">
                        <strong>Email:</strong> admin@redcliff.gov.zw<br>
                        <strong>Password:</strong> admin123
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php include '../includes/footer.php'; ?>