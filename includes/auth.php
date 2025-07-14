<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function login($email, $password) {
        try {
            $user = $this->db->selectOne(
                "SELECT * FROM users WHERE email = ? AND is_active = true", 
                [$email]
            );

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if user is approved (only for residents)
                if ($user['role'] === 'resident' && $user['verification_status'] !== 'approved') {
                    return false; // User not yet approved
                }

                // Update last login (skip if column doesn't exist)
                try {
                    $this->db->update(
                        "UPDATE users SET updated_at = NOW() WHERE id = ?",
                        [$user['id']]
                    );
                } catch (Exception $e) {
                    // Column may not exist, continue
                }

                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['login_time'] = time();

                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function register($data) {
        try {
            // Check if email already exists
            $existing = $this->db->selectOne(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']]
            );

            if ($existing) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user with pending verification status
            $user_id = $this->db->insert(
                "INSERT INTO users (first_name, last_name, email, password_hash, phone, address, role, is_active, verification_status) VALUES (?, ?, ?, ?, ?, ?, 'resident', false, 'pending')",
                [
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $hashed_password,
                    $data['phone'],
                    $data['address']
                ]
            );

            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    public function submitVerificationRequest($user_id, $national_id_path, $photo_path) {
        try {
            // Insert verification request
            $request_id = $this->db->insert(
                "INSERT INTO user_verification_requests (user_id, national_id_path, photo_path, status) VALUES (?, ?, ?, 'pending')",
                [$user_id, $national_id_path, $photo_path]
            );

            return ['success' => true, 'message' => 'Verification documents submitted successfully', 'request_id' => $request_id];
        } catch (Exception $e) {
            error_log("Verification request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit verification documents. Please try again.'];
        }
    }

    public function approveVerificationRequest($request_id, $admin_id, $notes = '') {
        try {
            // Get request details
            $request = $this->db->selectOne(
                "SELECT * FROM user_verification_requests WHERE id = ?",
                [$request_id]
            );

            if (!$request) {
                return ['success' => false, 'message' => 'Verification request not found'];
            }

            // Update request status
            $this->db->update(
                "UPDATE user_verification_requests SET status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, admin_notes = ? WHERE id = ?",
                [$admin_id, $notes, $request_id]
            );

            // Update user status
            $this->db->update(
                "UPDATE users SET is_active = true, verification_status = 'approved' WHERE id = ?",
                [$request['user_id']]
            );

            return ['success' => true, 'message' => 'User verification approved successfully'];
        } catch (Exception $e) {
            error_log("Verification approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve verification. Please try again.'];
        }
    }

    public function rejectVerificationRequest($request_id, $admin_id, $notes) {
        try {
            // Update request status
            $this->db->update(
                "UPDATE user_verification_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, admin_notes = ? WHERE id = ?",
                [$admin_id, $notes, $request_id]
            );

            return ['success' => true, 'message' => 'Verification request rejected successfully'];
        } catch (Exception $e) {
            error_log("Verification rejection error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject verification. Please try again.'];
        }
    }

    private function verifyPaymentRecord($payment_record_number) {
        // For demo purposes, any valid payment record number is accepted
        // In production, this would check against municipal payment system
        return ['valid' => true, 'message' => 'Payment verification successful'];
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['login_time']) && 
               (time() - $_SESSION['login_time']) < SESSION_TIMEOUT;
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }

    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ../auth/login.php');
            exit();
        }
    }

    public function requireRole($role) {
        $this->requireAuth();
        if (!$this->hasRole($role)) {
            header('Location: ../index.php');
            exit();
        }
    }
}

// Global auth instance
$auth = new Auth();
?>
