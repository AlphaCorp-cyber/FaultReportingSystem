<?php
require_once 'config/config.php';

class Auth {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function login($email, $password) {
        try {
            $user = $this->db->selectOne(
                "SELECT * FROM users WHERE email = ? AND status = 'active'", 
                [$email]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $this->db->update(
                    "UPDATE users SET last_login = NOW() WHERE id = ?",
                    [$user['id']]
                );

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

            // Verify payment record
            $payment_verification = $this->verifyPaymentRecord($data['account_number'], $data['id_number']);
            if (!$payment_verification['valid']) {
                return ['success' => false, 'message' => $payment_verification['message']];
            }

            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $user_id = $this->db->insert(
                "INSERT INTO users (first_name, last_name, email, password, phone, address, account_number, id_number, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'resident', 'active', NOW())",
                [
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $hashed_password,
                    $data['phone'],
                    $data['address'],
                    $data['account_number'],
                    $data['id_number']
                ]
            );

            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    private function verifyPaymentRecord($account_number, $id_number) {
        try {
            $payment_record = $this->db->selectOne(
                "SELECT * FROM payment_records WHERE account_number = ? AND id_number = ? AND status = 'active'",
                [$account_number, $id_number]
            );

            if (!$payment_record) {
                return ['valid' => false, 'message' => 'Invalid account number or ID number. Please contact the municipality.'];
            }

            // Check if payments are up to date (within last 3 months)
            $last_payment = $this->db->selectOne(
                "SELECT * FROM payment_records WHERE account_number = ? ORDER BY last_payment_date DESC LIMIT 1",
                [$account_number]
            );

            if (!$last_payment || strtotime($last_payment['last_payment_date']) < strtotime('-3 months')) {
                return ['valid' => false, 'message' => 'Your municipal payments are not up to date. Please settle outstanding payments.'];
            }

            return ['valid' => true, 'message' => 'Payment verification successful'];
        } catch (Exception $e) {
            error_log("Payment verification error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Payment verification failed. Please try again.'];
        }
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
