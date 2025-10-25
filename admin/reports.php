<?php
// admin/reports.php - Reports and Analytics

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/debug.php';
require_once '../includes/debug_toggle.php';

$auth = new Auth();
$auth->requireRole('admin');

$database = new DebugDatabase();
$conn = $database->getConnection();
$functions = new CommonFunctions();

// Get statistics
$stats = [];

// Total counts
$query = "SELECT COUNT(*) as count FROM students";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_students'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM teachers";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_teachers'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM courses";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_courses'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM departments";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_departments'] = $stmt->fetch()['count'];

// Students by department
$query = "SELECT d.name as department_name, COUNT(s.student_id) as student_count
          FROM departments d
          LEFT JOIN students s ON d.department_id = s.department_id
          GROUP BY d.department_id, d.name
          ORDER BY student_count DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$students_by_dept = $stmt->fetchAll();

// Courses by department
$query = "SELECT d.name as department_name, COUNT(c.course_id) as course_count
          FROM departments d
          LEFT JOIN courses c ON d.department_id = c.department_id
          GROUP BY d.department_id, d.name
          ORDER BY course_count DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$courses_by_dept = $stmt->fetchAll();

// Recent enrollments
$query = "SELECT e.*, s.first_name, s.last_name, s.student_id_number,
                 c.course_code, c.course_name, co.semester, co.academic_year
          FROM enrollments e
          JOIN students s ON e.student_id = s.student_id
          JOIN course_offerings co ON e.offering_id = co.offering_id
          JOIN courses c ON co.course_id = c.course_id
          ORDER BY e.enrollment_date DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_enrollments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                        <a class="nav-link active" href="reports.php">
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
                        <span class="navbar-brand mb-0 h1">Reports & Analytics</span>
                        <div class="navbar-nav ms-auto">
                            <span class="navbar-text me-3">
                                Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                            </span>
                            <?php echo renderDebugToggle(); ?>
                            <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="container-fluid">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-user-graduate me-2"></i>
                                    Total Students
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_teachers']; ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>
                                    Total Teachers
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-book me-2"></i>
                                    Total Courses
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_departments']; ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-building me-2"></i>
                                    Total Departments
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Students by Department
                                </h5>
                                <div class="chart-container">
                                    <canvas id="studentsChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Courses by Department
                                </h5>
                                <div class="chart-container">
                                    <canvas id="coursesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Enrollments -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-user-plus me-2"></i>
                            Recent Enrollments
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Academic Year</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?></td>
                                            <td><?php echo $enrollment['student_id_number']; ?></td>
                                            <td><?php echo $enrollment['course_code'] . ' - ' . $enrollment['course_name']; ?></td>
                                            <td><?php echo $enrollment['semester']; ?></td>
                                            <td><?php echo $enrollment['academic_year']; ?></td>
                                            <td><?php echo $functions->formatDateTime($enrollment['enrollment_date'], 'M d, Y'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $enrollment['status'] === 'enrolled' ? 'success' : ($enrollment['status'] === 'completed' ? 'info' : 'warning'); ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
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
    <script>
        // Students by Department Chart
        const studentsCtx = document.getElementById('studentsChart').getContext('2d');
        new Chart(studentsCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($students_by_dept, 'department_name')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($students_by_dept, 'student_count')); ?>],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Courses by Department Chart
        const coursesCtx = document.getElementById('coursesChart').getContext('2d');
        new Chart(coursesCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($courses_by_dept, 'department_name')) . "'"; ?>],
                datasets: [{
                    label: 'Courses',
                    data: [<?php echo implode(',', array_column($courses_by_dept, 'course_count')); ?>],
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <!-- Debug Panel -->
    <?php echo renderDebugPanel(); ?>
    
    <style>
        .query-debugger {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 500px;
            max-height: 400px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            display: none;
        }
        
        .query-debugger.show {
            display: block;
        }
        
        .debug-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .debug-header h6 {
            margin: 0;
            color: #495057;
        }
        
        .debug-content {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
        }
        
        .query-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .query-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .query-number {
            font-weight: bold;
            color: #007bff;
        }
        
        .execution-time {
            font-size: 0.8em;
            color: #6c757d;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .query-sql pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.85em;
            margin: 8px 0;
            overflow-x: auto;
        }
        
        .query-params {
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .query-params code {
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .debug-toggle {
            margin-right: 10px;
        }
    </style>
    
    <script>
        function toggleDebugger() {
            const debugger = document.getElementById('queryDebugger');
            if (debugger) {
                debugger.classList.toggle('show');
            }
        }
        
        // Auto-show debugger if queries are present
        <?php if (QueryDebugger::isEnabled() && !empty(QueryDebugger::getQueries())): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const debugger = document.getElementById('queryDebugger');
            if (debugger) {
                debugger.classList.add('show');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
