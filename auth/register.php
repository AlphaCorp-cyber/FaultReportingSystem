<?php
$page_title = 'Register';
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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'email' => sanitizeInput($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'phone' => sanitizeInput($_POST['phone']),
        'address' => sanitizeInput($_POST['address']),
        'account_number' => sanitizeInput($_POST['account_number']),
        'id_number' => sanitizeInput($_POST['id_number'])
    ];
    
    // Validation
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || 
        empty($data['password']) || empty($data['account_number']) || empty($data['id_number'])) {
        $error = 'Please fill in all required fields';
    } elseif (!validateEmail($data['email'])) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($data);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now login.';
            logActivity($result['user_id'], 'register', 'New user registered successfully');
        } else {
            $error = $result['message'];
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-success mb-2"></i>
                        <h4>Register</h4>
                        <p class="text-muted">Create your account to report faults</p>
                    </div>
                    
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
                    
                    <form method="POST" action="" id="registrationForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   placeholder="+263 xx xxx xxxx">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2" 
                                      placeholder="Your residential address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Verification Required:</strong> Please provide your municipal account details for verification.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Municipal Account Number *</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="<?php echo isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number *</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number" 
                                           value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">
                            Already have an account? Login here
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptance of Terms</h6>
                <p>By registering for the Redcliff Municipality Fault Reporting System, you agree to these terms and conditions.</p>
                
                <h6>2. User Responsibilities</h6>
                <ul>
                    <li>Provide accurate information when reporting faults</li>
                    <li>Use the system only for legitimate municipal service requests</li>
                    <li>Maintain confidentiality of your account credentials</li>
                    <li>Report only faults within Redcliff Municipality boundaries</li>
                </ul>
                
                <h6>3. Privacy Policy</h6>
                <p>Your personal information will be used solely for municipal service delivery and will not be shared with third parties without your consent.</p>
                
                <h6>4. System Usage</h6>
                <p>The system is available 24/7, but response times may vary depending on the nature and priority of the fault reported.</p>
                
                <h6>5. Liability</h6>
                <p>Redcliff Municipality is not liable for any damages resulting from the use of this system or delays in fault resolution.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

// Password matching validation
document.getElementById('confirm_password').addEventListener('blur', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form validation
document.getElementById('registrationForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
