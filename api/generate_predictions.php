<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/functions.php';

// Require admin authentication
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $python_path = '/usr/bin/python3';
    $script_path = dirname(__DIR__) . '/prediction/fault_predictor.py';
    
    // Run the Python script
    $command = "$python_path $script_path 2>&1";
    $output = shell_exec($command);
    
    // Check if predictions file was created
    $predictions_file = '/tmp/fault_predictions.json';
    if (file_exists($predictions_file)) {
        $predictions = json_decode(file_get_contents($predictions_file), true);
        
        if ($predictions) {
            // Log the activity
            $user = getCurrentUser();
            logActivity($user['id'], 'predictions_generated', 'Generated fault predictions via API');
            
            echo json_encode([
                'success' => true,
                'predictions' => $predictions,
                'message' => 'Predictions generated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to parse predictions data'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate predictions. Output: ' . $output
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating predictions: ' . $e->getMessage()
    ]);
}
?>