<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Log the logout activity
if (isLoggedIn()) {
    $user = getCurrentUser();
    logActivity($user['id'], 'logout', 'User logged out successfully');
}

// Destroy session and redirect
$auth->logout();
header('Location: ../index.php');
exit();
?>
