<?php
// teacher/dashboard.php - Teacher Dashboard

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/debug.php';
require_once '../includes/debug_toggle.php';

$auth = new Auth();
$auth->requireRole('teacher');

$database = new DebugDatabase();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$teacher_id = $_SESSION['profile_id'];

// Get teacher's assigned courses
$query = "SELECT co.*, c.course_code, c.course_name, c.credits, d.name as department_name,
                 COUNT(e.enrollment_id) as enrolled_students
          FROM course_offerings co
          JOIN courses c ON co.course_id = c.course_id
          JOIN departments d ON c.department_id = d.department_id
          LEFT JOIN enrollments e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
          WHERE co.teacher_id = :teacher_id
          GROUP BY co.offering_id
          ORDER BY co.academic_year DESC, co.semester DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$assigned_courses = $stmt->fetchAll();

// Get teacher info
$query = "SELECT t.*, d.name as department_name FROM teachers t
          JOIN departments d ON t.department_id = d.department_id
          WHERE t.teacher_id = :teacher_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$teacher_info = $stmt->fetch();

// Get recent notices
$query = "SELECT * FROM notices 
          WHERE target_audience IN ('all', 'teachers') AND is_active = 1
          ORDER BY date_posted DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$notices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - University Management System</title>
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .stat-icon.courses { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.students { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.grades { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.attendance { background: linear-gradient(135deg, #43e97b, #38f9d7); }
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <span class="navbar-brand mb-0 h1">Teacher Dashboard</span>
                        <div class="navbar-nav ms-auto">
                            <span class="navbar-text me-3">
                                Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                            </span>
                            <?php echo renderDebugToggle(); ?>
                            <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-2">
                                <i class="fas fa-sign-out-alt me-1"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="container-fluid">
                    <!-- Teacher Info -->
                    <div class="content-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo $teacher_info['first_name'] . ' ' . $teacher_info['last_name']; ?>
                                </h4>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-id-badge me-2"></i>
                                    Employee ID: <?php echo $teacher_info['employee_id']; ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-building me-2"></i>
                                    Department: <?php echo $teacher_info['department_name']; ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-briefcase me-2"></i>
                                    Designation: <?php echo $teacher_info['designation']; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="stat-icon courses">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon courses me-3">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo count($assigned_courses); ?></h3>
                                        <p class="text-muted mb-0">Assigned Courses</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon students me-3">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">
                                            <?php 
                                            $total_students = array_sum(array_column($assigned_courses, 'enrolled_students'));
                                            echo $total_students;
                                            ?>
                                        </h3>
                                        <p class="text-muted mb-0">Total Students</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon grades me-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">0</h3>
                                        <p class="text-muted mb-0">Grades Pending</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon attendance me-3">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">0</h3>
                                        <p class="text-muted mb-0">Attendance Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assigned Courses -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-book me-2"></i>
                            My Assigned Courses
                        </h5>
                        
                        <?php if (empty($assigned_courses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No courses assigned yet</h5>
                                <p class="text-muted">Contact the administration to get courses assigned.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($assigned_courses as $course): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="course-card">
                                            <h6 class="mb-2">
                                                <i class="fas fa-book me-2"></i>
                                                <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                            </h6>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-university me-2"></i>
                                                <?php echo $course['department_name']; ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                Semester <?php echo $course['semester']; ?> - <?php echo $course['academic_year']; ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-users me-2"></i>
                                                <?php echo $course['enrolled_students']; ?> students enrolled
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php echo $course['schedule']; ?>
                                            </p>
                                            <p class="text-muted mb-3">
                                                <i class="fas fa-door-open me-2"></i>
                                                <?php echo $course['classroom']; ?>
                                            </p>
                                            
                                            <div class="d-flex gap-2">
                                                <a href="course_students.php?offering_id=<?php echo $course['offering_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-users me-1"></i>
                                                    View Students
                                                </a>
                                                <a href="attendance.php?offering_id=<?php echo $course['offering_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-calendar-check me-1"></i>
                                                    Attendance
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Notices -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-bullhorn me-2"></i>
                            Recent Notices
                        </h5>
                        
                        <?php if (empty($notices)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-bullhorn fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent notices</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($notices as $notice): ?>
                                    <div class="list-group-item border-0 bg-light mb-2 rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo $notice['title']; ?></h6>
                                                <p class="mb-1 text-muted"><?php echo substr($notice['description'], 0, 100) . '...'; ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo $functions->formatDateTime($notice['date_posted'], 'M d, Y H:i'); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-<?php echo $notice['priority'] === 'urgent' ? 'danger' : ($notice['priority'] === 'high' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($notice['priority']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
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
