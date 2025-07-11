
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

<!-- Hero Section with Enhanced Design -->
<div class="hero-section bg-gradient-primary text-white position-relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); opacity: 0.9;"></div>
    <div class="position-absolute top-0 start-0 w-100 h-100">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
            <div class="shape shape-5"></div>
        </div>
    </div>
    
    <div class="container position-relative py-5">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="hero-content animate-on-scroll" data-animation="fadeInUp">
                    <div class="badge bg-light text-primary mb-3 px-3 py-2">
                        <i class="fas fa-shield-alt me-2"></i>Trusted by 10,000+ Residents
                    </div>
                    <h1 class="display-3 fw-bold mb-4 text-white">
                        Report. Track. 
                        <span class="text-gradient">Resolve.</span>
                    </h1>
                    <p class="lead mb-4 text-white-75">
                        Join thousands of residents making Redcliff a better place to live. 
                        Report infrastructure issues instantly and track their resolution in real-time.
                    </p>
                    <div class="hero-stats mb-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="h2 fw-bold text-white mb-1">2.5k+</h3>
                                    <p class="small text-white-75 mb-0">Issues Resolved</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="h2 fw-bold text-white mb-1">48h</h3>
                                    <p class="small text-white-75 mb-0">Avg Response</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="h2 fw-bold text-white mb-1">95%</h3>
                                    <p class="small text-white-75 mb-0">Success Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-buttons d-flex flex-wrap gap-3 mb-4">
                        <a href="auth/login.php" class="btn btn-light btn-lg px-4 py-3 rounded-pill shadow-lg">
                            <i class="fas fa-user me-2"></i>Get Started
                        </a>
                        <a href="auth/register.php" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">
                            <i class="fas fa-user-plus me-2"></i>Join Community
                        </a>
                    </div>
                    <div class="hero-features">
                        <div class="d-flex flex-wrap gap-4 text-white-75">
                            <div class="feature-item">
                                <i class="fas fa-check-circle me-2"></i>Free to Use
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-clock me-2"></i>24/7 Available
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-mobile-alt me-2"></i>Mobile Friendly
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-illustration animate-on-scroll" data-animation="fadeInRight">
                    <div class="illustration-container position-relative">
                        <!-- Main Illustration -->
                        <svg width="100%" height="500" viewBox="0 0 600 500" class="main-illustration">
                            <!-- Background Elements -->
                            <defs>
                                <linearGradient id="buildingGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#4facfe"/>
                                    <stop offset="100%" style="stop-color:#00f2fe"/>
                                </linearGradient>
                                <linearGradient id="roadGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#667eea"/>
                                    <stop offset="100%" style="stop-color:#764ba2"/>
                                </linearGradient>
                                <filter id="glow">
                                    <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                    <feMerge>
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>
                            
                            <!-- City Background -->
                            <rect x="0" y="350" width="600" height="150" fill="url(#roadGradient)" opacity="0.3"/>
                            
                            <!-- Buildings -->
                            <rect x="50" y="200" width="80" height="150" fill="url(#buildingGradient)" rx="5"/>
                            <rect x="150" y="150" width="90" height="200" fill="url(#buildingGradient)" rx="5"/>
                            <rect x="260" y="180" width="70" height="170" fill="url(#buildingGradient)" rx="5"/>
                            <rect x="350" y="120" width="100" height="230" fill="url(#buildingGradient)" rx="5"/>
                            <rect x="470" y="160" width="80" height="190" fill="url(#buildingGradient)" rx="5"/>
                            
                            <!-- Windows -->
                            <rect x="65" y="220" width="15" height="20" fill="#fff" opacity="0.8"/>
                            <rect x="100" y="220" width="15" height="20" fill="#fff" opacity="0.8"/>
                            <rect x="65" y="260" width="15" height="20" fill="#fff" opacity="0.8"/>
                            <rect x="100" y="260" width="15" height="20" fill="#fff" opacity="0.8"/>
                            
                            <!-- Municipality Building (Center) -->
                            <rect x="200" y="250" width="200" height="100" fill="#fff" rx="10"/>
                            <polygon points="190,250 300,200 410,250" fill="#fff"/>
                            <circle cx="300" cy="225" r="12" fill="#ffd700"/>
                            <rect x="280" y="280" width="40" height="60" fill="#667eea"/>
                            <rect x="220" y="280" width="25" height="35" fill="#667eea"/>
                            <rect x="355" y="280" width="25" height="35" fill="#667eea"/>
                            
                            <!-- Floating Icons -->
                            <g class="floating-icon" style="animation: float 3s ease-in-out infinite;">
                                <circle cx="100" cy="100" r="25" fill="#ff6b6b" opacity="0.9"/>
                                <text x="100" y="110" text-anchor="middle" fill="#fff" font-size="20">🚰</text>
                            </g>
                            <g class="floating-icon" style="animation: float 3s ease-in-out infinite 0.5s;">
                                <circle cx="450" cy="80" r="25" fill="#4ecdc4" opacity="0.9"/>
                                <text x="450" y="90" text-anchor="middle" fill="#fff" font-size="20">🛣️</text>
                            </g>
                            <g class="floating-icon" style="animation: float 3s ease-in-out infinite 1s;">
                                <circle cx="80" cy="300" r="25" fill="#ffe66d" opacity="0.9"/>
                                <text x="80" y="310" text-anchor="middle" fill="#fff" font-size="20">💡</text>
                            </g>
                            <g class="floating-icon" style="animation: float 3s ease-in-out infinite 1.5s;">
                                <circle cx="500" cy="300" r="25" fill="#a8e6cf" opacity="0.9"/>
                                <text x="500" y="310" text-anchor="middle" fill="#fff" font-size="20">🗑️</text>
                            </g>
                            
                            <!-- Connection Lines -->
                            <line x1="100" y1="125" x2="300" y2="225" stroke="#fff" stroke-width="2" opacity="0.6" stroke-dasharray="5,5">
                                <animate attributeName="stroke-dashoffset" values="0;-10" dur="1s" repeatCount="indefinite"/>
                            </line>
                            <line x1="450" y1="105" x2="300" y2="225" stroke="#fff" stroke-width="2" opacity="0.6" stroke-dasharray="5,5">
                                <animate attributeName="stroke-dashoffset" values="0;-10" dur="1s" repeatCount="indefinite"/>
                            </line>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<section class="features-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">Why Choose Our Platform?</h2>
                <p class="lead text-muted">Everything you need to make your community better, all in one place.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <div class="icon-circle bg-primary">
                            <i class="fas fa-bolt text-white"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Lightning Fast</h5>
                    <p class="card-text text-muted">Submit reports in under 2 minutes. Our streamlined process gets your issues logged instantly.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <div class="icon-circle bg-success">
                            <i class="fas fa-map-marked-alt text-white"></i>
                        </div>
                    </div>
                    <h5 class="card-title">GPS Tracking</h5>
                    <p class="card-text text-muted">Precise location detection ensures your reports reach the right department quickly.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <div class="icon-circle bg-info">
                            <i class="fas fa-bell text-white"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Real-time Updates</h5>
                    <p class="card-text text-muted">Stay informed with instant notifications about your report's progress.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <div class="icon-circle bg-warning">
                            <i class="fas fa-camera text-white"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Photo Evidence</h5>
                    <p class="card-text text-muted">Attach photos to your reports for faster resolution and better communication.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <div class="icon-circle bg-danger">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Secure & Private</h5>
                    <p class="card-text text-muted">Your data is protected with bank-level security and privacy measures.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <div class="icon-circle bg-secondary">
                            <i class="fas fa-users text-white"></i>
                        </div>
                    </div>
                    <h5 class="card-title">Community Driven</h5>
                    <p class="card-text text-muted">Join a community of residents working together to improve our municipality.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">How It Works</h2>
                <p class="lead text-muted">Three simple steps to report and resolve issues in your community.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 text-center">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-illustration mb-4">
                        <svg width="150" height="150" viewBox="0 0 150 150">
                            <circle cx="75" cy="75" r="70" fill="#e3f2fd" stroke="#2196f3" stroke-width="2"/>
                            <rect x="45" y="45" width="60" height="40" fill="#2196f3" rx="5"/>
                            <rect x="50" y="50" width="50" height="30" fill="#fff" rx="3"/>
                            <circle cx="75" cy="100" r="15" fill="#4caf50"/>
                            <path d="M70 100 L73 103 L80 96" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-3">Report Issue</h5>
                    <p class="text-muted">Create an account, describe the problem, add photos, and submit your report.</p>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-illustration mb-4">
                        <svg width="150" height="150" viewBox="0 0 150 150">
                            <circle cx="75" cy="75" r="70" fill="#fff3e0" stroke="#ff9800" stroke-width="2"/>
                            <rect x="35" y="45" width="80" height="60" fill="#ff9800" rx="5"/>
                            <rect x="40" y="50" width="70" height="15" fill="#fff" rx="2"/>
                            <rect x="40" y="70" width="50" height="8" fill="#fff" rx="2"/>
                            <rect x="40" y="82" width="60" height="8" fill="#fff" rx="2"/>
                            <circle cx="100" cy="95" r="8" fill="#4caf50"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-3">Get Assigned</h5>
                    <p class="text-muted">Your report is automatically assigned to the relevant department for action.</p>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-illustration mb-4">
                        <svg width="150" height="150" viewBox="0 0 150 150">
                            <circle cx="75" cy="75" r="70" fill="#e8f5e8" stroke="#4caf50" stroke-width="2"/>
                            <path d="M50 75 L70 90 L100 60" stroke="#4caf50" stroke-width="4" fill="none"/>
                            <circle cx="75" cy="40" r="8" fill="#ffc107"/>
                            <rect x="70" y="48" width="10" height="15" fill="#ffc107"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-3">Track Progress</h5>
                    <p class="text-muted">Monitor the status of your report and receive updates until it's resolved.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">Report Any Issue</h2>
                <p class="lead text-muted">We handle all types of municipal infrastructure problems.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="category-card text-center p-4 rounded shadow-sm bg-white h-100">
                    <div class="category-icon mb-3">
                        <i class="fas fa-tint text-primary fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Water Issues</h6>
                    <p class="small text-muted mb-0">Burst pipes, leaks, water quality</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="category-card text-center p-4 rounded shadow-sm bg-white h-100">
                    <div class="category-icon mb-3">
                        <i class="fas fa-road text-warning fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Roads</h6>
                    <p class="small text-muted mb-0">Potholes, cracks, damaged roads</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="category-card text-center p-4 rounded shadow-sm bg-white h-100">
                    <div class="category-icon mb-3">
                        <i class="fas fa-bolt text-danger fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Electricity</h6>
                    <p class="small text-muted mb-0">Power outages, faulty wiring</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="category-card text-center p-4 rounded shadow-sm bg-white h-100">
                    <div class="category-icon mb-3">
                        <i class="fas fa-lightbulb text-success fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Street Lights</h6>
                    <p class="small text-muted mb-0">Broken lights, dark areas</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="category-card text-center p-4 rounded shadow-sm bg-white h-100">
                    <div class="category-icon mb-3">
                        <i class="fas fa-trash text-info fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Waste</h6>
                    <p class="small text-muted mb-0">Collection issues, illegal dumping</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="category-card text-center p-4 rounded shadow-sm bg-white h-100">
                    <div class="category-icon mb-3">
                        <i class="fas fa-tree text-success fa-2x"></i>
                    </div>
                    <h6 class="fw-bold">Parks</h6>
                    <p class="small text-muted mb-0">Damaged equipment, maintenance</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">What Residents Say</h2>
                <p class="lead text-muted">Real feedback from our community members.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="testimonial-card card border-0 shadow-sm h-100 p-4">
                    <div class="testimonial-content mb-4">
                        <div class="stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted">"Reported a water leak on Monday, and it was fixed by Thursday! The updates kept me informed throughout the process."</p>
                    </div>
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar me-3">
                            <div class="avatar-circle bg-primary">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0">Sarah Johnson</h6>
                            <small class="text-muted">Hillside Resident</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="testimonial-card card border-0 shadow-sm h-100 p-4">
                    <div class="testimonial-content mb-4">
                        <div class="stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted">"Easy to use platform. The photo upload feature helped the team understand the pothole issue immediately."</p>
                    </div>
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar me-3">
                            <div class="avatar-circle bg-success">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0">Michael Chen</h6>
                            <small class="text-muted">Downtown Area</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="testimonial-card card border-0 shadow-sm h-100 p-4">
                    <div class="testimonial-content mb-4">
                        <div class="stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="text-muted">"Finally, a way to report issues that actually works! The transparency and communication are excellent."</p>
                    </div>
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar me-3">
                            <div class="avatar-circle bg-info">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0">Emma Williams</h6>
                            <small class="text-muted">Riverside Community</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="display-5 fw-bold mb-3">Ready to Make a Difference?</h2>
                <p class="lead mb-0">Join thousands of residents who are already improving their community.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="auth/register.php" class="btn btn-light btn-lg px-4 py-3 rounded-pill">
                    <i class="fas fa-rocket me-2"></i>Get Started Today
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4">Contact Information</h3>
                <div class="contact-info">
                    <div class="contact-item d-flex align-items-center mb-3">
                        <div class="contact-icon me-3">
                            <div class="icon-circle bg-primary">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1">Address</h6>
                            <p class="text-muted mb-0">Redcliff Municipality, Midlands Province, Zimbabwe</p>
                        </div>
                    </div>
                    <div class="contact-item d-flex align-items-center mb-3">
                        <div class="contact-icon me-3">
                            <div class="icon-circle bg-success">
                                <i class="fas fa-phone text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1">Phone</h6>
                            <p class="text-muted mb-0">+263 54 123 4567</p>
                        </div>
                    </div>
                    <div class="contact-item d-flex align-items-center mb-3">
                        <div class="contact-icon me-3">
                            <div class="icon-circle bg-info">
                                <i class="fas fa-envelope text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1">Email</h6>
                            <p class="text-muted mb-0">info@redcliff.gov.zw</p>
                        </div>
                    </div>
                    <div class="contact-item d-flex align-items-center">
                        <div class="contact-icon me-3">
                            <div class="icon-circle bg-warning">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1">Office Hours</h6>
                            <p class="text-muted mb-0">8:00 AM - 5:00 PM (Monday - Friday)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="municipality-info">
                    <h3 class="fw-bold mb-4">Municipality Information</h3>
                    <p class="text-muted mb-4">For general inquiries, visit our offices during business hours or contact us through the channels above.</p>
                    <div class="info-box bg-light p-3 rounded">
                        <h6 class="fw-bold mb-2">Important Notice</h6>
                        <p class="small text-muted mb-0">Only verified residents with valid payment records can submit fault reports. Make sure your municipal account is up to date.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
