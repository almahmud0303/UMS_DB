<?php
// includes/auth.php - Authentication System

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT u.*, 
                            COALESCE(
                                CASE 
                                    WHEN u.role = 'student' THEN s.first_name
                                    WHEN u.role = 'teacher' THEN t.first_name
                                    ELSE 'Admin'
                                END, 'User'
                            ) as first_name,
                            COALESCE(
                                CASE 
                                    WHEN u.role = 'student' THEN s.last_name
                                    WHEN u.role = 'teacher' THEN t.last_name
                                    ELSE 'User'
                                END, 'User'
                            ) as last_name,
                            CASE 
                                WHEN u.role = 'student' THEN s.student_id
                                WHEN u.role = 'teacher' THEN t.teacher_id
                                ELSE NULL
                            END as profile_id
                     FROM users u
                     LEFT JOIN students s ON u.user_id = s.user_id AND u.role = 'student'
                     LEFT JOIN teachers t ON u.user_id = t.user_id AND u.role = 'teacher'
                     WHERE u.username = :username";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if(password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'] ?? 'User';
                    $_SESSION['last_name'] = $user['last_name'] ?? 'User';
                    $_SESSION['profile_id'] = $user['profile_id'] ?? null;
                    $_SESSION['email'] = $user['email'];
                    
                    return true;
                }
            }
            return false;
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireRole($required_role) {
        $this->requireLogin();
        if ($_SESSION['role'] !== $required_role) {
            header('Location: unauthorized.php');
            exit();
        }
    }
    
    public function requireAnyRole($roles) {
        $this->requireLogin();
        if (!in_array($_SESSION['role'], $roles)) {
            header('Location: unauthorized.php');
            exit();
        }
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            // Verify old password
            $query = "SELECT password FROM users WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if(password_verify($old_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = :password WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    return $stmt->execute();
                }
            }
            return false;
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
}

// Helper function to check if user has permission
function hasPermission($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    if (is_array($required_role)) {
        return in_array($_SESSION['role'], $required_role);
    }
    
    return $_SESSION['role'] === $required_role;
}

// Helper function to get current user info
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? null,
            'last_name' => $_SESSION['last_name'] ?? null,
            'profile_id' => $_SESSION['profile_id'] ?? null,
            'email' => $_SESSION['email'] ?? null
        ];
    }
    return null;
}
?>
