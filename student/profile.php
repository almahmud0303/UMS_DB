<?php
// student/profile.php - Student Profile Management

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get student information
$query = "SELECT s.*, p.name as program_name, d.name as department_name
          FROM students s
          JOIN programs p ON s.program_id = p.program_id
          JOIN departments d ON s.department_id = d.department_id
          WHERE s.student_id = :student_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$student = $stmt->fetch();

$message = '';
$message_type = '';

// Handle profile update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $first_name = $functions->sanitize($_POST['first_name']);
    $last_name = $functions->sanitize($_POST['last_name']);
    $email = $functions->sanitize($_POST['email']);
    $phone = $functions->sanitize($_POST['phone']);
    $address = $functions->sanitize($_POST['address']);
    $emergency_contact = $functions->sanitize($_POST['emergency_contact']);
    $emergency_phone = $functions->sanitize($_POST['emergency_phone']);
    
    try {
        $query = "UPDATE students SET first_name = :first_name, last_name = :last_name, 
                 email = :email, phone = :phone, address = :address, 
                 emergency_contact = :emergency_contact, emergency_phone = :emergency_phone 
                 WHERE student_id = :student_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':emergency_contact', $emergency_contact);
        $stmt->bindParam(':emergency_phone', $emergency_phone);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            
            // Refresh student data
            $query = "SELECT s.*, p.name as program_name, d.name as department_name
                      FROM students s
                      JOIN programs p ON s.program_id = p.program_id
                      JOIN departments d ON s.department_id = d.department_id
                      WHERE s.student_id = :student_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $student = $stmt->fetch();
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
    <title>My Profile - Student Panel</title>
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
                        <i class="fas fa-user-graduate me-2"></i>
                        Student Panel
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
                        <a class="nav-link" href="grades.php">
                            <i class="fas fa-chart-line me-2"></i>
                            Grades
                        </a>
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i>
                            Attendance
                        </a>
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-credit-card me-2"></i>
                            Payments
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
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h3>
                        <p class="mb-1">Student ID: <?php echo $student['student_id_number']; ?></p>
                        <p class="mb-0">Roll Number: <?php echo $student['roll_number']; ?></p>
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
                                           value="<?php echo $student['first_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" 
                                           value="<?php echo $student['last_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo $student['email'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo $student['phone'] ?? ''; ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo $student['address'] ?? ''; ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Emergency Contact</label>
                                    <input type="text" class="form-control" name="emergency_contact" 
                                           value="<?php echo $student['emergency_contact'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Emergency Phone</label>
                                    <input type="tel" class="form-control" name="emergency_phone" 
                                           value="<?php echo $student['emergency_phone'] ?? ''; ?>">
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
                    
                    <!-- Academic Information -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Academic Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Student ID</label>
                                <p class="form-control-plaintext"><?php echo $student['student_id_number']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Roll Number</label>
                                <p class="form-control-plaintext"><?php echo $student['roll_number']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Program</label>
                                <p class="form-control-plaintext"><?php echo $student['program_name']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <p class="form-control-plaintext"><?php echo $student['department_name']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Admission Date</label>
                                <p class="form-control-plaintext"><?php echo $student['admission_date'] ? $functions->formatDateTime($student['admission_date'], 'M d, Y') : 'Not specified'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Current Semester</label>
                                <p class="form-control-plaintext"><?php echo $student['current_semester'] ?? 'Not specified'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Academic Year</label>
                                <p class="form-control-plaintext"><?php echo $student['academic_year'] ?? 'Not specified'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-<?php echo ($student['status'] ?? 'active') === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($student['status'] ?? 'active'); ?>
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
