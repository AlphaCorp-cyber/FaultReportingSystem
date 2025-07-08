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

            // Verify payment record (simplified for demo)
            if (empty($data['payment_record_number'])) {
                return ['success' => false, 'message' => 'Payment record number is required'];
            }

            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $user_id = $this->db->insert(
                "INSERT INTO users (first_name, last_name, email, password_hash, phone, address, payment_record_number, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 'resident', true)",
                [
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $hashed_password,
                    $data['phone'],
                    $data['address'],
                    $data['payment_record_number']
                ]
            );

            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
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
