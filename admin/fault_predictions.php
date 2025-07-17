<?php
$page_title = 'Fault Predictions';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();
$prediction_data = [];
$error_message = '';

// Run Python prediction script
function runPredictionScript() {
    $python_path = '/usr/bin/python3';
    $script_path = dirname(__DIR__) . '/prediction/fault_predictor.py';
    $output_file = '/tmp/fault_predictions.json';
    
    // Run the Python script
    $command = "$python_path $script_path 2>&1";
    $output = shell_exec($command);
    
    // Read the results
    if (file_exists($output_file)) {
        $json_data = file_get_contents($output_file);
        $data = json_decode($json_data, true);
        return $data;
    }
    
    return null;
}

// Handle prediction generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_predictions'])) {
    $prediction_data = runPredictionScript();
    
    if ($prediction_data === null) {
        $error_message = 'Failed to generate predictions. Please ensure there is sufficient historical data.';
    } else {
        logActivity($user['id'], 'predictions_generated', 'Generated fault predictions report');
    }
}

// Get existing predictions if available
$existing_predictions_file = '/tmp/fault_predictions.json';
if (file_exists($existing_predictions_file)) {
    $json_data = file_get_contents($existing_predictions_file);
    $existing_predictions = json_decode($json_data, true);
} else {
    $existing_predictions = null;
}

// Get basic statistics for manual analysis
$stats = [];
try {
    $stats['total_faults'] = $db->selectOne("SELECT COUNT(*) as count FROM fault_reports")['count'];
    $stats['monthly_breakdown'] = $db->select("
        SELECT 
            EXTRACT(MONTH FROM created_at) as month,
            COUNT(*) as count
        FROM fault_reports 
        WHERE created_at >= NOW() - INTERVAL '12 months'
        GROUP BY EXTRACT(MONTH FROM created_at)
        ORDER BY month
    ");
    $stats['location_breakdown'] = $db->select("
        SELECT 
            location,
            COUNT(*) as count
        FROM fault_reports 
        GROUP BY location
        ORDER BY count DESC
        LIMIT 10
    ");
    $stats['category_breakdown'] = $db->select("
        SELECT 
            category,
            COUNT(*) as count
        FROM fault_reports 
        GROUP BY category
        ORDER BY count DESC
    ");
} catch (Exception $e) {
    $stats = ['error' => 'Could not load statistics'];
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-crystal-ball me-2"></i>
                        Fault Predictions & Analytics
                    </h4>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="generate_predictions" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i>
                            Generate New Predictions
                        </button>
                    </form>
                </div>
                
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($prediction_data): ?>
                        <!-- Prediction Results -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Predictions generated successfully! 
                                    Total faults analyzed: <?php echo $prediction_data['total_faults_analyzed']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- High Risk Locations -->
                        <?php if (!empty($prediction_data['high_risk_locations'])): ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5>High Risk Locations</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Location</th>
                                                    <th>Risk Score</th>
                                                    <th>Risk Level</th>
                                                    <th>Historical Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($prediction_data['high_risk_locations'] as $location): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($location['location']); ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar 
                                                                    <?php 
                                                                    if ($location['risk_score'] > 70) echo 'bg-danger';
                                                                    elseif ($location['risk_score'] > 40) echo 'bg-warning';
                                                                    else echo 'bg-success';
                                                                    ?>" 
                                                                    role="progressbar" 
                                                                    style="width: <?php echo $location['risk_score']; ?>%">
                                                                    <?php echo round($location['risk_score'], 1); ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge 
                                                                <?php 
                                                                if ($location['risk_level'] == 'High') echo 'bg-danger';
                                                                elseif ($location['risk_level'] == 'Medium') echo 'bg-warning';
                                                                else echo 'bg-success';
                                                                ?>">
                                                                <?php echo $location['risk_level']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $location['historical_count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Recommendations -->
                        <?php if (!empty($prediction_data['recommendations'])): ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5>Recommendations</h5>
                                    <div class="alert alert-info">
                                        <ul class="mb-0">
                                            <?php foreach ($prediction_data['recommendations'] as $recommendation): ?>
                                                <li><?php echo htmlspecialchars($recommendation); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif ($existing_predictions): ?>
                        <!-- Show existing predictions -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Showing previous predictions generated on <?php echo date('Y-m-d H:i:s', strtotime($existing_predictions['last_updated'])); ?>
                        </div>
                        
                        <?php if (!empty($existing_predictions['high_risk_locations'])): ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5>High Risk Locations</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Location</th>
                                                    <th>Risk Score</th>
                                                    <th>Risk Level</th>
                                                    <th>Historical Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($existing_predictions['high_risk_locations'] as $location): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($location['location']); ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar 
                                                                    <?php 
                                                                    if ($location['risk_score'] > 70) echo 'bg-danger';
                                                                    elseif ($location['risk_score'] > 40) echo 'bg-warning';
                                                                    else echo 'bg-success';
                                                                    ?>" 
                                                                    role="progressbar" 
                                                                    style="width: <?php echo $location['risk_score']; ?>%">
                                                                    <?php echo round($location['risk_score'], 1); ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge 
                                                                <?php 
                                                                if ($location['risk_level'] == 'High') echo 'bg-danger';
                                                                elseif ($location['risk_level'] == 'Medium') echo 'bg-warning';
                                                                else echo 'bg-success';
                                                                ?>">
                                                                <?php echo $location['risk_level']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $location['historical_count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                    <!-- Basic Statistics -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Location Analysis</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($stats['location_breakdown'])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Location</th>
                                                        <th>Total Faults</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['location_breakdown'] as $location): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($location['location']); ?></td>
                                                            <td><?php echo $location['count']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Category Analysis</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($stats['category_breakdown'])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Total Faults</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['category_breakdown'] as $category): ?>
                                                        <tr>
                                                            <td><?php echo ucfirst($category['category']); ?></td>
                                                            <td><?php echo $category['count']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>