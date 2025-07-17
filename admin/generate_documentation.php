
<?php
$page_title = 'Generate System Documentation';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Require admin authentication
requireRole('admin');

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_docs'])) {
    try {
        // Check if PHPWord is available
        if (!file_exists('../vendor/autoload.php')) {
            throw new Exception('PHPWord library not found. Please install it first.');
        }
        
        // Generate documentation
        ob_start();
        include '../generate_system_documentation.php';
        $output = ob_get_clean();
        
        $success_message = 'Documentation generated successfully! Check the file explorer for the DOCX file.';
        
    } catch (Exception $e) {
        $error_message = 'Error generating documentation: ' . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-file-alt me-2"></i>Generate System Documentation</h2>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">System Documentation Generator</h5>
                    <p class="card-text">
                        Generate a comprehensive DOCX document containing all system information, 
                        including user guides, technical specifications, diagrams, and more.
                    </p>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Document Contents:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>System Overview</li>
                                <li><i class="fas fa-check text-success me-2"></i>Architecture Design</li>
                                <li><i class="fas fa-check text-success me-2"></i>User Guides</li>
                                <li><i class="fas fa-check text-success me-2"></i>Login Credentials</li>
                                <li><i class="fas fa-check text-success me-2"></i>Technical Diagrams</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Additional Information:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Database Design (ER Diagram)</li>
                                <li><i class="fas fa-check text-success me-2"></i>API Documentation</li>
                                <li><i class="fas fa-check text-success me-2"></i>Installation Guide</li>
                                <li><i class="fas fa-check text-success me-2"></i>Troubleshooting Guide</li>
                                <li><i class="fas fa-check text-success me-2"></i>System Flowcharts</li>
                            </ul>
                        </div>
                    </div>

                    <form method="POST">
                        <button type="submit" name="generate_docs" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-download me-2"></i>Generate Documentation
                        </button>
                    </form>

                    <div class="mt-4">
                        <h6>Prerequisites:</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            The PHPWord library is required to generate DOCX files. 
                            If not installed, run: <code>composer require phpoffice/phpword</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information Cards -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h6>User Accounts</h6>
                            <p class="text-muted">Admin and resident login credentials included</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-2x text-info mb-2"></i>
                            <h6>Database Schema</h6>
                            <p class="text-muted">Complete ER diagram and table structures</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-cogs fa-2x text-success mb-2"></i>
                            <h6>Technical Specs</h6>
                            <p class="text-muted">Architecture and implementation details</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
