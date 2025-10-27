<?php
// admin/enrollments.php - Enrollment Management (Placeholder)

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action === 'edit') {
        $enrollment_id = $_POST['enrollment_id'];
        $offering_id = $_POST['offering_id'];
        $status = $_POST['status'];
        
        try {
            $query = "UPDATE enrollments SET offering_id = :offering_id, status = :status 
                     WHERE enrollment_id = :enrollment_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':enrollment_id', $enrollment_id);
            $stmt->bindParam(':offering_id', $offering_id);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                $message = 'Enrollment updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating enrollment.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get single enrollment for editing
$edit_enrollment = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $enrollment_id = $_GET['id'];
    $query = "SELECT e.*, s.first_name, s.last_name, s.student_id, s.student_id_number,
                     c.course_id, c.course_code, c.course_name, co.offering_id, co.teacher_id
              FROM enrollments e
              JOIN students s ON e.student_id = s.student_id
              JOIN course_offerings co ON e.offering_id = co.offering_id
              JOIN courses c ON co.course_id = c.course_id
              WHERE e.enrollment_id = :enrollment_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':enrollment_id', $enrollment_id);
    $stmt->execute();
    $edit_enrollment = $stmt->fetch();
    
    // Get all available course offerings
    $query = "SELECT co.*, c.course_code, c.course_name, 
                     t.first_name as teacher_first_name, t.last_name as teacher_last_name
              FROM course_offerings co
              JOIN courses c ON co.course_id = c.course_id
              JOIN teachers t ON co.teacher_id = t.teacher_id
              ORDER BY co.academic_year DESC, co.semester DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $available_offerings = $stmt->fetchAll();
}

// Get enrollments with student and course info
$query = "SELECT e.*, s.first_name, s.last_name, s.student_id_number, s.roll_number,
                 c.course_code, c.course_name, co.semester, co.academic_year,
                 t.first_name as teacher_first_name, t.last_name as teacher_last_name
          FROM enrollments e
          JOIN students s ON e.student_id = s.student_id
          JOIN course_offerings co ON e.offering_id = co.offering_id
          JOIN courses c ON co.course_id = c.course_id
          JOIN teachers t ON co.teacher_id = t.teacher_id
          ORDER BY e.enrollment_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$enrollments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-graduation-cap me-2"></i>
                        UMS Admin
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                        <a class="nav-link" href="students.php">
                            <i class="fas fa-user-graduate me-2"></i>
                            Students
                        </a>
                        <a class="nav-link" href="teachers.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            Teachers
                        </a>
                        <a class="nav-link" href="courses.php">
                            <i class="fas fa-book me-2"></i>
                            Courses
                        </a>
                        <a class="nav-link" href="departments.php">
                            <i class="fas fa-building me-2"></i>
                            Departments
                        </a>
                        <a class="nav-link" href="programs.php">
                            <i class="fas fa-certificate me-2"></i>
                            Programs
                        </a>
                        <a class="nav-link active" href="enrollments.php">
                            <i class="fas fa-user-plus me-2"></i>
                            Enrollments
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
                        <a class="nav-link" href="notices.php">
                            <i class="fas fa-bullhorn me-2"></i>
                            Notices
                        </a>
                        <a class="nav-link" href="library.php">
                            <i class="fas fa-book-open me-2"></i>
                            Library
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reports
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
                        <span class="navbar-brand mb-0 h1">Enrollment Management</span>
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
                    
                    <!-- Edit Enrollment Form -->
                    <?php if ($edit_enrollment): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-user-plus me-2"></i>
                                Edit Enrollment
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="enrollment_id" value="<?php echo $edit_enrollment['enrollment_id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Student</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo $edit_enrollment['first_name'] . ' ' . $edit_enrollment['last_name']; ?> (<?php echo $edit_enrollment['student_id_number']; ?>)" 
                                               disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Current Course</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo $edit_enrollment['course_code'] . ' - ' . $edit_enrollment['course_name']; ?>" 
                                               disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Change to Course Offering</label>
                                        <select class="form-select" name="offering_id" required>
                                            <?php foreach ($available_offerings as $offering): ?>
                                                <option value="<?php echo $offering['offering_id']; ?>" 
                                                        <?php echo ($edit_enrollment['offering_id'] == $offering['offering_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $offering['course_code'] . ' - ' . $offering['course_name']; ?>
                                                    (Semester <?php echo $offering['semester']; ?>, <?php echo $offering['academic_year']; ?>)
                                                    - <?php echo $offering['teacher_first_name'] . ' ' . $offering['teacher_last_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="enrolled" <?php echo ($edit_enrollment['status'] === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                                            <option value="dropped" <?php echo ($edit_enrollment['status'] === 'dropped') ? 'selected' : ''; ?>>Dropped</option>
                                            <option value="completed" <?php echo ($edit_enrollment['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Update Enrollment
                                    </button>
                                    <a href="enrollments.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Enrollments List -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-user-plus me-2"></i>
                            Student Enrollments
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="enrollmentsTable">
                                <thead>
                                    <tr>
                                        <th>Enrollment ID</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Course</th>
                                        <th>Teacher</th>
                                        <th>Semester</th>
                                        <th>Academic Year</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo $enrollment['enrollment_id']; ?></td>
                                            <td><?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?></td>
                                            <td><?php echo $enrollment['student_id_number']; ?></td>
                                            <td><?php echo $enrollment['course_code'] . ' - ' . $enrollment['course_name']; ?></td>
                                            <td><?php echo $enrollment['teacher_first_name'] . ' ' . $enrollment['teacher_last_name']; ?></td>
                                            <td><?php echo $enrollment['semester']; ?></td>
                                            <td><?php echo $enrollment['academic_year']; ?></td>
                                            <td><?php echo $functions->formatDateTime($enrollment['enrollment_date'], 'M d, Y'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $enrollment['status'] === 'enrolled' ? 'success' : ($enrollment['status'] === 'completed' ? 'info' : 'warning'); ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="enrollments.php?action=edit&id=<?php echo $enrollment['enrollment_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#enrollmentsTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
