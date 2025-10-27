<?php
// student/dashboard.php - Student Dashboard

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get student info
$query = "SELECT s.*, d.name as department_name, p.name as program_name, u.username, u.email
          FROM students s
          JOIN departments d ON s.department_id = d.department_id
          JOIN programs p ON s.program_id = p.program_id
          JOIN users u ON s.user_id = u.user_id
          WHERE s.student_id = :student_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$student_info = $stmt->fetch();

// Get enrolled courses with grades
$query = "SELECT e.*, co.semester, co.academic_year, co.schedule, co.classroom,
                 c.course_code, c.course_name, c.credits,
                 t.first_name as teacher_first_name, t.last_name as teacher_last_name,
                 g.grade_letter, g.grade_point, g.graded_at,
                 COUNT(a.attendance_id) as total_attendance,
                 SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
          FROM enrollments e
          JOIN course_offerings co ON e.offering_id = co.offering_id
          JOIN courses c ON co.course_id = c.course_id
          JOIN teachers t ON co.teacher_id = t.teacher_id
          LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
          LEFT JOIN attendance a ON e.enrollment_id = a.enrollment_id
          WHERE e.student_id = :student_id
          GROUP BY e.enrollment_id
          ORDER BY co.academic_year DESC, co.semester DESC, c.course_code";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$enrolled_courses = $stmt->fetchAll();

// Calculate GPA
$total_points = 0;
$total_credits = 0;
$completed_courses = 0;

foreach ($enrolled_courses as $course) {
    if ($course['grade_point'] !== null) {
        $total_points += $course['grade_point'] * $course['credits'];
        $total_credits += $course['credits'];
        $completed_courses++;
    }
}

$gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.00;

// Get recent notices
$query = "SELECT * FROM notices 
          WHERE target_audience IN ('all', 'students') AND is_active = 1
          ORDER BY date_posted DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$notices = $stmt->fetchAll();

// Get payment status
$query = "SELECT f.fee_type, f.amount, f.due_date,
                 SUM(p.amount) as paid_amount,
                 (f.amount - COALESCE(SUM(p.amount), 0)) as remaining_amount
          FROM fees f
          LEFT JOIN payments p ON f.fee_id = p.fee_id AND p.student_id = :student_id AND p.status = 'completed'
          WHERE f.program_id = :program_id
          GROUP BY f.fee_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->bindParam(':program_id', $student_info['program_id']);
$stmt->execute();
$fee_status = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Management System</title>
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
        .stat-icon.gpa { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.attendance { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.payments { background: linear-gradient(135deg, #43e97b, #38f9d7); }
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
        .grade-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .attendance-percentage {
            font-size: 1rem;
            font-weight: 600;
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <span class="navbar-brand mb-0 h1">Student Dashboard</span>
                        <div class="navbar-nav ms-auto">
                            <span class="navbar-text me-3">
                                Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                            </span>
                            <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-2">
                                <i class="fas fa-sign-out-alt me-1"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="container-fluid">
                    <!-- Student Info -->
                    <div class="content-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas fa-user-graduate me-2"></i>
                                    <?php echo $student_info['first_name'] . ' ' . $student_info['last_name']; ?>
                                </h4>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-id-badge me-2"></i>
                                    Student ID: <?php echo $student_info['student_id_number']; ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-hashtag me-2"></i>
                                    Roll No: <?php echo $student_info['roll_number']; ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-building me-2"></i>
                                    Department: <?php echo $student_info['department_name']; ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-certificate me-2"></i>
                                    Program: <?php echo $student_info['program_name']; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="stat-icon gpa">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h3 class="mt-2 mb-0"><?php echo $gpa; ?></h3>
                                <p class="text-muted mb-0">Current GPA</p>
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
                                        <h3 class="mb-0"><?php echo count($enrolled_courses); ?></h3>
                                        <p class="text-muted mb-0">Enrolled Courses</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon gpa me-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $completed_courses; ?></h3>
                                        <p class="text-muted mb-0">Completed Courses</p>
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
                                        <h3 class="mb-0"><?php echo $student_info['semester']; ?></h3>
                                        <p class="text-muted mb-0">Current Semester</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon payments me-3">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $student_info['session']; ?></h3>
                                        <p class="text-muted mb-0">Session</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enrolled Courses -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-book me-2"></i>
                            My Enrolled Courses
                        </h5>
                        
                        <?php if (empty($enrolled_courses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No courses enrolled</h5>
                                <p class="text-muted">Contact your advisor to enroll in courses.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($enrolled_courses as $course): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="course-card">
                                            <h6 class="mb-2">
                                                <i class="fas fa-book me-2"></i>
                                                <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                            </h6>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                                <?php echo $course['teacher_first_name'] . ' ' . $course['teacher_last_name']; ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                Semester <?php echo $course['semester']; ?> - <?php echo $course['academic_year']; ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php echo $course['schedule']; ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-door-open me-2"></i>
                                                <?php echo $course['classroom']; ?>
                                            </p>
                                            
                                            <!-- Attendance -->
                                            <div class="mb-2">
                                                <?php 
                                                $attendance_percentage = 0;
                                                if ($course['total_attendance'] > 0) {
                                                    $attendance_percentage = round(($course['present_count'] / $course['total_attendance']) * 100, 1);
                                                }
                                                ?>
                                                <span class="attendance-percentage text-<?php echo $attendance_percentage >= 75 ? 'success' : ($attendance_percentage >= 50 ? 'warning' : 'danger'); ?>">
                                                    <i class="fas fa-calendar-check me-1"></i>
                                                    <?php echo $attendance_percentage; ?>%
                                                </span>
                                                <small class="text-muted d-block">
                                                    <?php echo $course['present_count']; ?>/<?php echo $course['total_attendance']; ?> classes
                                                </small>
                                            </div>
                                            
                                            <!-- Grade -->
                                            <div class="mb-3">
                                                <?php if ($course['grade_letter']): ?>
                                                    <span class="badge bg-<?php echo $course['grade_point'] >= 3.0 ? 'success' : ($course['grade_point'] >= 2.0 ? 'warning' : 'danger'); ?> grade-badge">
                                                        <?php echo $course['grade_letter']; ?> (<?php echo $course['grade_point']; ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary grade-badge">Not Graded</span>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Fee Status -->
                    <?php if (!empty($fee_status)): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-credit-card me-2"></i>
                                Fee Status
                            </h5>
                            
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fee Type</th>
                                            <th>Amount</th>
                                            <th>Paid</th>
                                            <th>Remaining</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fee_status as $fee): ?>
                                            <tr>
                                                <td><?php echo $fee['fee_type']; ?></td>
                                                <td>৳<?php echo number_format($fee['amount'], 2); ?></td>
                                                <td>৳<?php echo number_format($fee['paid_amount'] ?? 0, 2); ?></td>
                                                <td>৳<?php echo number_format($fee['remaining_amount'], 2); ?></td>
                                                <td><?php echo $functions->formatDate($fee['due_date']); ?></td>
                                                <td>
                                                    <?php if ($fee['remaining_amount'] <= 0): ?>
                                                        <span class="badge bg-success">Paid</span>
                                                    <?php elseif ($fee['paid_amount'] > 0): ?>
                                                        <span class="badge bg-warning">Partial</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Unpaid</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
</body>
</html>
