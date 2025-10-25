<?php
// student/attendance.php - Student Attendance View

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/debug.php';
require_once '../includes/debug_toggle.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new DebugDatabase();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get student's attendance records
$query = "SELECT a.*, c.course_code, c.course_name,
                 co.semester, co.academic_year,
                 t.first_name as teacher_first_name, t.last_name as teacher_last_name
          FROM attendance a
          JOIN enrollments e ON a.enrollment_id = e.enrollment_id
          JOIN course_offerings co ON e.offering_id = co.offering_id
          JOIN courses c ON co.course_id = c.course_id
          JOIN teachers t ON co.teacher_id = t.teacher_id
          WHERE e.student_id = :student_id
          ORDER BY a.date DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$attendance = $stmt->fetchAll();

// Calculate attendance statistics
$total_classes = count($attendance);
$present_classes = count(array_filter($attendance, function($record) {
    return $record['status'] === 'present';
}));
$attendance_percentage = $total_classes > 0 ? round(($present_classes / $total_classes) * 100, 2) : 0;

// Group attendance by course
$attendance_by_course = [];
foreach ($attendance as $record) {
    $key = $record['course_code'] . ' - ' . $record['course_name'];
    if (!isset($attendance_by_course[$key])) {
        $attendance_by_course[$key] = [
            'course_code' => $record['course_code'],
            'course_name' => $record['course_name'],
            'semester' => $record['semester'],
            'academic_year' => $record['academic_year'],
            'teacher_name' => $record['teacher_first_name'] . ' ' . $record['teacher_last_name'],
            'records' => []
        ];
    }
    $attendance_by_course[$key]['records'][] = $record;
}

// Calculate attendance percentage for each course
foreach ($attendance_by_course as $key => $course_data) {
    $course_total = count($course_data['records']);
    $course_present = count(array_filter($course_data['records'], function($record) {
        return $record['status'] === 'present';
    }));
    $attendance_by_course[$key]['percentage'] = $course_total > 0 ? round(($course_present / $course_total) * 100, 2) : 0;
    $attendance_by_course[$key]['total_classes'] = $course_total;
    $attendance_by_course[$key]['present_classes'] = $course_present;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Panel</title>
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
        .attendance-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .attendance-percentage {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .attendance-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
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
                        <a class="nav-link active" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i>
                            Attendance
                        </a>
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-credit-card me-2"></i>
                            Payments
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
                        <span class="navbar-brand mb-0 h1">My Attendance</span>
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
                    <!-- Overall Attendance Summary -->
                    <div class="content-card">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-3">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Overall Attendance Summary
                                </h5>
                                <div class="attendance-percentage text-<?php echo $attendance_percentage >= 75 ? 'success' : ($attendance_percentage >= 60 ? 'warning' : 'danger'); ?>">
                                    <?php echo $attendance_percentage; ?>%
                                </div>
                                <p class="text-muted mb-0">
                                    <?php echo $present_classes; ?> out of <?php echo $total_classes; ?> classes attended
                                </p>
                            </div>
                            <div class="col-md-6">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-<?php echo $attendance_percentage >= 75 ? 'success' : ($attendance_percentage >= 60 ? 'warning' : 'danger'); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $attendance_percentage; ?>%"
                                         aria-valuenow="<?php echo $attendance_percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo $attendance_percentage; ?>%
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?php if ($attendance_percentage >= 75): ?>
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            Good attendance! Keep it up.
                                        <?php elseif ($attendance_percentage >= 60): ?>
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                            Attendance is below recommended level.
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-1"></i>
                                            Poor attendance. Please improve.
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance by Course -->
                    <?php if (empty($attendance_by_course)): ?>
                        <div class="content-card">
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No attendance records</h5>
                                <p class="text-muted">Your attendance records will appear here once teachers start marking attendance.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($attendance_by_course as $course_key => $course_data): ?>
                            <div class="attendance-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="fas fa-book me-2"></i>
                                        <?php echo $course_data['course_code'] . ' - ' . $course_data['course_name']; ?>
                                    </h5>
                                    <div class="text-end">
                                        <div class="attendance-percentage text-<?php echo $course_data['percentage'] >= 75 ? 'success' : ($course_data['percentage'] >= 60 ? 'warning' : 'danger'); ?>">
                                            <?php echo $course_data['percentage']; ?>%
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $course_data['present_classes']; ?>/<?php echo $course_data['total_classes']; ?> classes
                                        </small>
                                    </div>
                                </div>
                                
                                <p class="text-muted mb-3">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>
                                    <?php echo $course_data['teacher_name']; ?> | 
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo $course_data['academic_year']; ?> - Semester <?php echo $course_data['semester']; ?>
                                </p>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($course_data['records'] as $record): ?>
                                                <tr>
                                                    <td><?php echo $functions->formatDateTime($record['date'], 'M d, Y'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $record['status'] === 'present' ? 'success' : 'danger'; ?> attendance-badge">
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
                        <?php endforeach; ?>
                    <?php endif; ?>
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
