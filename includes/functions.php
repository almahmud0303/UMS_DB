<?php
// includes/functions.php - Common Functions

require_once __DIR__ . '/../config/database.php';

class CommonFunctions {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Sanitize input data
    public function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    // Format date for display
    public function formatDate($date, $format = 'Y-m-d') {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }
    
    // Format datetime for display
    public function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
        if (empty($datetime)) return '';
        return date($format, strtotime($datetime));
    }
    
    // Generate unique ID
    public function generateUniqueId($prefix = '') {
        return $prefix . uniqid() . rand(1000, 9999);
    }
    
    // Upload file
    public function uploadFile($file, $target_dir = UPLOAD_PATH) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        $target_file = $target_dir . basename($file['name']);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File too large'];
        }
        
        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        // Generate unique filename
        $new_filename = uniqid() . '.' . $file_type;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'filename' => $new_filename, 'path' => $target_file];
        } else {
            return ['success' => false, 'message' => 'Upload failed'];
        }
    }
    
    // Send email notification (placeholder - implement with PHPMailer)
    public function sendEmail($to, $subject, $message) {
        // This is a placeholder. In production, use PHPMailer or similar
        $headers = "From: noreply@university.edu\r\n";
        $headers .= "Reply-To: noreply@university.edu\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    // Get pagination data
    public function getPaginationData($total_records, $current_page = 1, $records_per_page = 10) {
        $total_pages = ceil($total_records / $records_per_page);
        $offset = ($current_page - 1) * $records_per_page;
        
        return [
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'records_per_page' => $records_per_page,
            'offset' => $offset,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_pages,
            'prev_page' => max(1, $current_page - 1),
            'next_page' => min($total_pages, $current_page + 1)
        ];
    }
    
    // Generate breadcrumb
    public function generateBreadcrumb($items) {
        $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
        
        foreach ($items as $index => $item) {
            if ($index === count($items) - 1) {
                $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . $item['title'] . '</li>';
            } else {
                $breadcrumb .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['title'] . '</a></li>';
            }
        }
        
        $breadcrumb .= '</ol></nav>';
        return $breadcrumb;
    }
    
    // Log activity
    public function logActivity($user_id, $action, $details = '') {
        try {
            $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                     VALUES (:user_id, :action, :details, :ip_address, :user_agent, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':details', $details);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Get dashboard statistics
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Total students
            $query = "SELECT COUNT(*) as total FROM students";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_students'] = $stmt->fetch()['total'];
            
            // Total teachers
            $query = "SELECT COUNT(*) as total FROM teachers";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_teachers'] = $stmt->fetch()['total'];
            
            // Total courses
            $query = "SELECT COUNT(*) as total FROM courses";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_courses'] = $stmt->fetch()['total'];
            
            // Total departments
            $query = "SELECT COUNT(*) as total FROM departments";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_departments'] = $stmt->fetch()['total'];
            
            return $stats;
        } catch(PDOException $e) {
            return [];
        }
    }
}

// Global functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function showAlert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

function showSuccess($message) {
    return showAlert($message, 'success');
}

function showError($message) {
    return showAlert($message, 'danger');
}

function showWarning($message) {
    return showAlert($message, 'warning');
}

function showInfo($message) {
    return showAlert($message, 'info');
}
?>
