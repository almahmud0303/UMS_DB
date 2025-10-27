<?php
// teacher/profile.php - Teacher Profile Management

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('teacher');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$teacher_id = $_SESSION['profile_id'];

// Get teacher information with email
$query = "SELECT t.*, d.name as department_name, u.email
          FROM teachers t
          JOIN departments d ON t.department_id = d.department_id
          JOIN users u ON t.user_id = u.user_id
          WHERE t.teacher_id = :teacher_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$teacher = $stmt->fetch();

$message = '';
$message_type = '';

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = 'New passwords do not match.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'danger';
    } else {
        try {
            // Get user_id from teachers table
            $query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            $teacher_data = $stmt->fetch();
            $user_id = $teacher_data['user_id'];
            
            // Verify current password
            $query = "SELECT * FROM users WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error changing password.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Current password is incorrect.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle profile update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $first_name = $functions->sanitize($_POST['first_name']);
    $last_name = $functions->sanitize($_POST['last_name']);
    $designation = $functions->sanitize($_POST['designation']);
    $phone = $functions->sanitize($_POST['phone']);
    $address = $functions->sanitize($_POST['address']);

    
    try {
        $query = "UPDATE teachers SET first_name = :first_name, last_name = :last_name, 
                 designation = :designation, phone = :phone, address = :address 
                 WHERE teacher_id = :teacher_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':designation', $designation);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            
            // Refresh teacher data
            $query = "SELECT t.*, d.name as department_name, u.email
                      FROM teachers t
                      JOIN departments d ON t.department_id = d.department_id
                      JOIN users u ON t.user_id = u.user_id
                      WHERE t.teacher_id = :teacher_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            $teacher = $stmt->fetch();
        } else {
            $message = 'Error updating profile.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Teacher Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .profile-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        Teacher Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                        <a class="nav-link" href="courses.php">
                            <i class="fas fa-book me-2"></i>
                            My Courses
                        </a>
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-user-graduate me-2"></i>
                            My Students
                        </a>
                        <a class="nav-link" href="grades.php">
                            <i class="fas fa-chart-line me-2"></i>
                            Manage Grades
                        </a>
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i>
                            Attendance
                        </a>
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user me-2"></i>
                            Profile
                        </a>
                        <hr class="my-3">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">My Profile</span>
                        <div class="navbar-nav ms-auto">
                            <span class="navbar-text me-3">
                                Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                            </span>
                            <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="container-fluid">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Profile Header -->
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h3>
                        <p class="mb-1">Employee ID: <?php echo $teacher['employee_id']; ?></p>
                        <p class="mb-0"><?php echo $teacher['designation']; ?></p>
                    </div>
                    
                    <!-- Change Password Card -->
                    <div class="content-card mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-lock me-2"></i>
                            Change Password
                        </h5>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" 
                                           placeholder="Enter current password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" 
                                           placeholder="Enter new password (min 6 characters)" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           placeholder="Confirm new password" required minlength="6">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Change Password
                            </button>
                        </form>
                    </div>
                    
                    <!-- Profile Information -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-user me-2"></i>
                            Personal Information
                        </h5>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" 
                                           value="<?php echo $teacher['first_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" 
                                           value="<?php echo $teacher['last_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Designation</label>
                                    <input type="text" class="form-control" name="designation" 
                                           value="<?php echo $teacher['designation'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo $teacher['email'] ?? ''; ?>" disabled readonly>
                                    <small class="form-text text-muted">Email cannot be changed</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo $teacher['phone'] ?? ''; ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo $teacher['address'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Professional Information -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-briefcase me-2"></i>
                            Professional Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Employee ID</label>
                                <p class="form-control-plaintext"><?php echo $teacher['employee_id']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <p class="form-control-plaintext"><?php echo $teacher['department_name']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Hire Date</label>
                                <p class="form-control-plaintext"><?php echo $teacher['hire_date'] ? $functions->formatDateTime($teacher['hire_date'], 'M d, Y') : 'Not specified'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Salary</label>
                                <p class="form-control-plaintext">৳<?php echo number_format($teacher['salary'] ?? 0, 2); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-<?php echo ($teacher['status'] ?? 'active') === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($teacher['status'] ?? 'active'); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

</body>
</html>
