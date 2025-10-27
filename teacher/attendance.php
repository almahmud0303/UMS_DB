<?php
// teacher/attendance.php - Teacher Attendance Management

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('teacher');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$teacher_id = $_SESSION['profile_id'];

// Get teacher's courses
$query = "SELECT co.*, c.course_code, c.course_name, c.credits
          FROM course_offerings co
          JOIN courses c ON co.course_id = c.course_id
          WHERE co.teacher_id = :teacher_id
          ORDER BY co.academic_year DESC, co.semester DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$courses = $stmt->fetchAll();

// Get students for selected course
$students = [];
$selected_course = null;
$attendance_records = [];
if (isset($_GET['course'])) {
    $offering_id = $_GET['course'];
    
    // Verify teacher has access to this course
    $query = "SELECT co.*, c.course_code, c.course_name FROM course_offerings co
              JOIN courses c ON co.course_id = c.course_id
              WHERE co.offering_id = :offering_id AND co.teacher_id = :teacher_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':offering_id', $offering_id);
    $stmt->bindParam(':teacher_id', $teacher_id);
    $stmt->execute();
    $selected_course = $stmt->fetch();
    
    if ($selected_course) {
        // Get students enrolled in this course
        $query = "SELECT s.*, e.enrollment_id, e.enrollment_date
                  FROM enrollments e
                  JOIN students s ON e.student_id = s.student_id
                  WHERE e.offering_id = :offering_id AND e.status = 'enrolled'
                  ORDER BY s.first_name, s.last_name";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':offering_id', $offering_id);
        $stmt->execute();
        $students = $stmt->fetchAll();
        
        // Get attendance records for this course
        $query = "SELECT a.*, s.first_name, s.last_name, s.student_id_number, s.roll_number
                  FROM attendance a
                  JOIN enrollments e ON a.enrollment_id = e.enrollment_id
                  JOIN students s ON e.student_id = s.student_id
                  WHERE e.offering_id = :offering_id AND a.marked_by = :teacher_id
                  ORDER BY a.date DESC, s.first_name, s.last_name";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':offering_id', $offering_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        $attendance_records = $stmt->fetchAll();
    }
}

// Handle attendance submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_attendance') {
    $offering_id = $_POST['offering_id'];
    $attendance_date = $_POST['attendance_date'];
    
    try {
        $conn->beginTransaction();
        
        foreach ($_POST['attendance'] as $enrollment_id => $status) {
            if (!empty($status)) {
                $remarks = $functions->sanitize($_POST['remarks'][$enrollment_id] ?? '');
                
                // Check if attendance already exists for this date
                $query = "SELECT attendance_id FROM attendance 
                         WHERE enrollment_id = :enrollment_id AND date = :date";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':enrollment_id', $enrollment_id);
                $stmt->bindParam(':date', $attendance_date);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing attendance
                    $query = "UPDATE attendance SET status = :status, remarks = :remarks 
                             WHERE enrollment_id = :enrollment_id AND date = :date";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':enrollment_id', $enrollment_id);
                    $stmt->bindParam(':date', $attendance_date);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':remarks', $remarks);
                } else {
                    // Insert new attendance
                    $query = "INSERT INTO attendance (enrollment_id, date, status, remarks, marked_by) 
                             VALUES (:enrollment_id, :date, :status, :remarks, :marked_by)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':enrollment_id', $enrollment_id);
                    $stmt->bindParam(':date', $attendance_date);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':remarks', $remarks);
                    $stmt->bindParam(':marked_by', $teacher_id);
                }
                
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $message = 'Attendance submitted successfully!';
        $message_type = 'success';
        
        // Refresh the page to show updated attendance
        redirect("attendance.php?course=$offering_id");
        
    } catch (Exception $e) {
        $conn->rollback();
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
    <title>Attendance Management - Teacher Panel</title>
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
        .course-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-3px);
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
                        <a class="nav-link active" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i>
                            Attendance
                        </a>
                        <a class="nav-link" href="profile.php">
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
                        <span class="navbar-brand mb-0 h1">Attendance Management</span>
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
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Course Selection -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-book me-2"></i>
                            Select Course to Mark Attendance
                        </h5>
                        
                        <?php if (empty($courses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No courses assigned</h5>
                                <p class="text-muted">Contact the administration to get courses assigned to you.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($courses as $course): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="course-card">
                                            <h6 class="mb-2">
                                                <i class="fas fa-book me-2"></i>
                                                <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                            </h6>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                Semester <?php echo $course['semester']; ?> - <?php echo $course['academic_year']; ?>
                                            </p>
                                            <p class="text-muted mb-3">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                <?php echo $course['credits']; ?> credits
                                            </p>
                                            
                                            <a href="attendance.php?course=<?php echo $course['offering_id']; ?>" 
                                               class="btn btn-primary w-100">
                                                <i class="fas fa-calendar-check me-2"></i>
                                                Mark Attendance
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Attendance Form for Selected Course -->
                    <?php if ($selected_course && !empty($students)): ?>
                        <div class="content-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Mark Attendance for <?php echo $selected_course['course_code'] . ' - ' . $selected_course['course_name']; ?>
                                </h5>
                                <a href="attendance.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Courses
                                </a>
                            </div>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="submit_attendance">
                                <input type="hidden" name="offering_id" value="<?php echo $selected_course['offering_id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Attendance Date</label>
                                        <input type="date" class="form-control" name="attendance_date" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Name</th>
                                                <th>Roll No</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo $student['student_id_number']; ?></td>
                                                    <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                                    <td><?php echo $student['roll_number']; ?></td>
                                                    <td>
                                                        <select class="form-select form-select-sm" name="attendance[<?php echo $student['enrollment_id']; ?>]">
                                                            <option value="">Select Status</option>
                                                            <option value="present">Present</option>
                                                            <option value="absent">Absent</option>
                                                            <option value="late">Late</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="remarks[<?php echo $student['enrollment_id']; ?>]"
                                                               placeholder="Optional remarks">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Submit Attendance
                                    </button>
                                    <a href="attendance.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($selected_course && empty($students)): ?>
                        <div class="content-card">
                            <div class="text-center py-5">
                                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No students enrolled</h5>
                                <p class="text-muted">Students will appear here once they enroll in this course.</p>
                                <a href="attendance.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Courses
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Attendance History -->
                    <?php if ($selected_course && !empty($attendance_records)): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-history me-2"></i>
                                Attendance History
                            </h5>
                            <p class="text-muted mb-3">Past attendance records for <?php echo $selected_course['course_code']; ?></p>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Student ID</th>
                                            <th>Roll Number</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo $functions->formatDateTime($record['date'], 'M d, Y'); ?></td>
                                                <td><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></td>
                                                <td><?php echo $record['student_id_number']; ?></td>
                                                <td><?php echo $record['roll_number']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($record['status'] === 'present') ? 'success' : (($record['status'] === 'absent') ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $record['remarks'] ?? '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

</body>
</html>
