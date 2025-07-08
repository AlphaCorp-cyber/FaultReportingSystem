<?php
$page_title = 'Submit Fault Report';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('resident');

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $category = sanitizeInput($_POST['category']);
    $description = sanitizeInput($_POST['description']);
    $location = sanitizeInput($_POST['location']);
    $priority = getPriorityLevel($category, $description);
    
    // Validate inputs
    if (empty($title) || empty($category) || empty($description) || empty($location)) {
        $error = 'Please fill in all required fields';
    } elseif (!array_key_exists($category, FAULT_CATEGORIES)) {
        $error = 'Invalid category selected';
    } else {
        try {
            // Generate reference number
            $reference_number = 'FR' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Check if reference number already exists
            $existing = $db->selectOne("SELECT id FROM fault_reports WHERE reference_number = ?", [$reference_number]);
            while ($existing) {
                $reference_number = 'FR' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $existing = $db->selectOne("SELECT id FROM fault_reports WHERE reference_number = ?", [$reference_number]);
            }
            
            // Handle file uploads
            $evidence_files = [];
            if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
                for ($i = 0; $i < count($_FILES['evidence']['name']); $i++) {
                    if ($_FILES['evidence']['error'][$i] === 0) {
                        $file = [
                            'name' => $_FILES['evidence']['name'][$i],
                            'tmp_name' => $_FILES['evidence']['tmp_name'][$i],
                            'size' => $_FILES['evidence']['size'][$i],
                            'error' => $_FILES['evidence']['error'][$i]
                        ];
                        
                        $upload_result = uploadFile($file);
                        if ($upload_result['success']) {
                            $evidence_files[] = $upload_result['filename'];
                        }
                    }
                }
            }
            
            // Get coordinates for location
            $coordinates = getLocationCoordinates($location);
            
            // Determine department based on category
            $department_mapping = [
                'water' => 'water',
                'roads' => 'roads',
                'electricity' => 'electricity',
                'streetlights' => 'electricity',
                'waste' => 'waste',
                'parks' => 'parks',
                'other' => 'general'
            ];
            
            $assigned_department = $department_mapping[$category];
            
            // Insert fault report
            $fault_id = $db->insert(
                "INSERT INTO fault_reports (reference_number, user_id, category, title, description, location, latitude, longitude, priority, assigned_department, evidence_files, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $reference_number,
                    $user['id'],
                    $category,
                    $title,
                    $description,
                    $location,
                    $coordinates['latitude'],
                    $coordinates['longitude'],
                    $priority,
                    $assigned_department,
                    json_encode($evidence_files)
                ]
            );
            
            if ($fault_id) {
                // Log activity
                logActivity($user['id'], 'fault_submitted', "Fault report #$reference_number submitted");
                
                // Send notification to user
                sendNotification($user['id'], "Your fault report #$reference_number has been submitted successfully and is being processed.", 'success');
                
                // Auto-assign to department if enabled
                $auto_assign = $db->selectOne("SELECT setting_value FROM system_settings WHERE setting_key = 'fault_auto_assign'");
                if ($auto_assign && $auto_assign['setting_value'] === 'true') {
                    $db->update(
                        "UPDATE fault_reports SET status = 'assigned' WHERE id = ?",
                        [$fault_id]
                    );
                }
                
                $success = "Fault report submitted successfully! Reference number: <strong>$reference_number</strong>";
                
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Failed to submit fault report. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Fault submission error: " . $e->getMessage());
            $error = 'An error occurred while submitting your report. Please try again.';
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
                    <h4 class="mb-0">Submit Fault Report</h4>
                    <p class="text-muted mb-0">Report infrastructure issues in your area</p>
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
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="faultForm">
                        <div class="mb-3">
                            <label for="title" class="form-label">Fault Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                   placeholder="Brief description of the fault" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <?php foreach (FAULT_CATEGORIES as $key => $name): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo (isset($_POST['category']) && $_POST['category'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Provide detailed information about the fault, including when you noticed it and any other relevant details" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location *</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                   placeholder="Exact location or address of the fault" required>
                            <small class="form-text text-muted">
                                Be as specific as possible (e.g., "Corner of Main Street and Oak Avenue, near the bus stop")
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="evidence" class="form-label">Evidence Files (Optional)</label>
                            <input type="file" class="form-control" id="evidence" name="evidence[]" 
                                   multiple accept="image/*,.pdf">
                            <small class="form-text text-muted">
                                Upload photos or documents related to the fault (Max 5MB per file)
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Your fault report will be assigned to the appropriate department automatically. 
                                You will receive notifications about the progress of your report.
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Reporting Guidelines</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-check-circle text-success me-2"></i>Do</h6>
                        <ul class="list-unstyled">
                            <li>• Be specific about the location</li>
                            <li>• Include photos if possible</li>
                            <li>• Provide clear descriptions</li>
                            <li>• Report safety hazards immediately</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-times-circle text-danger me-2"></i>Don't</h6>
                        <ul class="list-unstyled">
                            <li>• Submit duplicate reports</li>
                            <li>• Use inappropriate language</li>
                            <li>• Report private property issues</li>
                            <li>• Submit false information</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Category Guide -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Category Guide</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <i class="fas fa-tint text-primary me-2"></i>
                        <strong>Water & Sewer:</strong> Burst pipes, leaks, blockages
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-road text-secondary me-2"></i>
                        <strong>Roads:</strong> Potholes, damaged pavements, road signs
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        <strong>Electricity:</strong> Power outages, damaged cables
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-lightbulb text-info me-2"></i>
                        <strong>Street Lighting:</strong> Broken lights, dark areas
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-trash text-success me-2"></i>
                        <strong>Waste:</strong> Missed collections, overflowing bins
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-tree text-success me-2"></i>
                        <strong>Parks:</strong> Damaged equipment, maintenance issues
                    </div>
                </div>
            </div>
            
            <!-- Response Times -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Expected Response Times</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge bg-danger me-2">High Priority</span>
                        Emergency issues: 24 hours
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-warning me-2">Medium Priority</span>
                        Standard issues: 3-5 business days
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-info me-2">Low Priority</span>
                        Non-urgent issues: 1-2 weeks
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('faultForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const category = document.getElementById('category').value;
    const description = document.getElementById('description').value.trim();
    const location = document.getElementById('location').value.trim();
    
    if (!title || !category || !description || !location) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return;
    }
    
    // Check file sizes
    const fileInput = document.getElementById('evidence');
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    for (let i = 0; i < fileInput.files.length; i++) {
        if (fileInput.files[i].size > maxSize) {
            e.preventDefault();
            alert('File size must be less than 5MB');
            return;
        }
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
});

// Auto-fill location with user's address
document.addEventListener('DOMContentLoaded', function() {
    const locationInput = document.getElementById('location');
    const userAddress = '<?php echo htmlspecialchars($user['address']); ?>';
    
    if (userAddress && !locationInput.value) {
        locationInput.placeholder = 'e.g., Near ' + userAddress;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
