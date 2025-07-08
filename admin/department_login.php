
<?php
$page_title = 'Department Login';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'department') {
        header('Location: section_dashboard.php');
    } elseif ($user['role'] === 'admin') {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_code = sanitizeInput($_POST['department_code'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($department_code) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check department credentials
        $department_user = $db->selectOne(
            "SELECT * FROM users WHERE department_code = ? AND role = 'department' AND is_active = true",
            [$department_code]
        );
        
        if ($department_user && password_verify($password, $department_user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $department_user['id'];
            $_SESSION['user_role'] = $department_user['role'];
            $_SESSION['department'] = $department_user['department'];
            
            logActivity($department_user['id'], 'login', 'Department user logged in successfully');
            header('Location: section_dashboard.php');
            exit();
        } else {
            $error = 'Invalid department credentials';
            error_log("Failed department login attempt for code: $department_code from IP: " . $_SERVER['REMOTE_ADDR']);
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
                        <i class="fas fa-building fa-3x text-primary mb-2"></i>
                        <h4>Department Login</h4>
                        <p class="text-muted">Access your department dashboard</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="department_code" class="form-label">Department Code</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-building"></i>
                                </span>
                                <input type="text" class="form-control" id="department_code" name="department_code" 
                                       value="<?php echo isset($_POST['department_code']) ? htmlspecialchars($_POST['department_code']) : ''; ?>" 
                                       placeholder="e.g., WATER_DEPT" required>
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
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-user-shield me-1"></i>
                            Admin Login Portal
                        </a>
                    </div>
                    
                    <div class="text-center mt-2">
                        <a href="../auth/login.php" class="text-decoration-none">
                            <i class="fas fa-user me-1"></i>
                            Resident Login Portal
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Demo Department Credentials -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Demo Department Credentials</h6>
                    <div class="row">
                        <div class="col-6">
                            <strong>Water Dept:</strong><br>
                            Code: WATER_DEPT<br>
                            Pass: water123
                        </div>
                        <div class="col-6">
                            <strong>Roads Dept:</strong><br>
                            Code: ROADS_DEPT<br>
                            Pass: roads123
                        </div>
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
