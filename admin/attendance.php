<?php
// admin/attendance.php - Attendance Management (Placeholder)

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

// Get attendance records with student and course info
$query = "SELECT a.*, s.first_name, s.last_name, s.student_id_number, s.roll_number,
                 c.course_code, c.course_name, co.semester, co.academic_year,
                 t.first_name as teacher_first_name, t.last_name as teacher_last_name
          FROM attendance a
          JOIN enrollments e ON a.enrollment_id = e.enrollment_id
          JOIN students s ON e.student_id = s.student_id
          JOIN course_offerings co ON e.offering_id = co.offering_id
          JOIN courses c ON co.course_id = c.course_id
          JOIN teachers t ON co.teacher_id = t.teacher_id
          ORDER BY a.date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$attendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Admin Panel</title>
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
                        <a class="nav-link" href="enrollments.php">
                            <i class="fas fa-user-plus me-2"></i>
                            Enrollments
                        </a>
                        <a class="nav-link" href="grades.php">
                            <i class="fas fa-chart-line me-2"></i>
                            Grades
                        </a>
                        <a class="nav-link active" href="attendance.php">
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
                    <!-- Attendance List -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-calendar-check me-2"></i>
                            Attendance Records
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Attendance ID</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Course</th>
                                        <th>Teacher</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td><?php echo $record['attendance_id']; ?></td>
                                            <td><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></td>
                                            <td><?php echo $record['student_id_number']; ?></td>
                                            <td><?php echo $record['course_code'] . ' - ' . $record['course_name']; ?></td>
                                            <td><?php echo $record['teacher_first_name'] . ' ' . $record['teacher_last_name']; ?></td>
                                            <td><?php echo $functions->formatDateTime($record['date'], 'M d, Y'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $record['status'] === 'present' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $record['remarks'] ?: '-'; ?></td>
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
            $('#attendanceTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
