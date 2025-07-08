<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: resident/dashboard.php');
    }
    exit();
}

include 'includes/header.php';
?>

<div class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold">Redcliff Municipality</h1>
                <h2 class="h3 mb-4">Fault Reporting System</h2>
                <p class="lead mb-4">Report infrastructure issues quickly and efficiently. Track your submissions and get real-time updates on repairs.</p>
                <div class="d-flex gap-3">
                    <a href="auth/login.php" class="btn btn-light btn-lg">Login</a>
                    <a href="auth/register.php" class="btn btn-outline-light btn-lg">Register</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center">
                    <svg width="300" height="200" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
                        <!-- Municipality building illustration -->
                        <rect x="50" y="80" width="200" height="100" fill="#fff" opacity="0.9"/>
                        <rect x="70" y="100" width="30" height="60" fill="#007bff"/>
                        <rect x="120" y="100" width="30" height="60" fill="#007bff"/>
                        <rect x="170" y="100" width="30" height="60" fill="#007bff"/>
                        <polygon points="40,80 150,40 260,80" fill="#fff"/>
                        <circle cx="150" cy="60" r="8" fill="#ffc107"/>
                        <text x="150" y="195" text-anchor="middle" fill="#fff" font-size="14">Municipal Services</text>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Report Faults</h5>
                    <p class="card-text">Quickly report infrastructure issues like burst pipes, potholes, or broken streetlights.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Track Progress</h5>
                    <p class="card-text">Monitor the status of your reports and receive updates on repair progress.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Community Impact</h5>
                    <p class="card-text">Help improve municipal services and contribute to a better living environment.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h3>How It Works</h3>
                <div class="d-flex mb-3">
                    <div class="badge bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">1</div>
                    <div>
                        <h6>Register & Verify</h6>
                        <p class="text-muted">Create an account and verify your municipal payment status.</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <div class="badge bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">2</div>
                    <div>
                        <h6>Submit Report</h6>
                        <p class="text-muted">Fill out the fault report form with details and photos.</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <div class="badge bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">3</div>
                    <div>
                        <h6>Track & Update</h6>
                        <p class="text-muted">Monitor progress and receive notifications on your report.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h3>Contact Information</h3>
                <div class="mb-3">
                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                    <span>Redcliff Municipality, Midlands Province, Zimbabwe</span>
                </div>
                <div class="mb-3">
                    <i class="fas fa-phone text-primary me-2"></i>
                    <span>+263 54 123 4567</span>
                </div>
                <div class="mb-3">
                    <i class="fas fa-envelope text-primary me-2"></i>
                    <span>info@redcliff.gov.zw</span>
                </div>
                <div class="mb-3">
                    <i class="fas fa-clock text-primary me-2"></i>
                    <span>Office Hours: 8:00 AM - 5:00 PM (Monday - Friday)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
