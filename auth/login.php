<?php
$page_title = 'Login';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../resident/dashboard.php');
    }
    exit();
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
        if ($auth->login($email, $password)) {
            $user = getCurrentUser();
            logActivity($user['id'], 'login', 'User logged in successfully');
            
            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../resident/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid resident credentials';
            // Log failed login attempt
            error_log("Failed resident login attempt for email: $email from IP: " . $_SERVER['REMOTE_ADDR']);
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
                        <i class="fas fa-user-circle fa-3x text-primary mb-2"></i>
                        <h4>Resident Login</h4>
                        <p class="text-muted">Access your resident account</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
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
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none">
                            Don't have an account? Register here
                        </a>
                    </div>
                    

                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Only verified residents can register
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Demo Credentials -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Demo Resident Credentials</h6>
                    <div class="text-center">
                        <strong>Email:</strong> john.doe@example.com<br>
                        <strong>Password:</strong> resident123
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
