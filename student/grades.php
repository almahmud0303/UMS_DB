<?php
// student/grades.php - Student Grades View

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get student's grades with course info
$query = "SELECT e.*, co.semester, co.academic_year,
                 c.course_code, c.course_name, c.credits,
                 t.first_name as teacher_first_name, t.last_name as teacher_last_name,
                 g.grade_letter, g.grade_point, g.graded_at, g.remarks
          FROM enrollments e
          JOIN course_offerings co ON e.offering_id = co.offering_id
          JOIN courses c ON co.course_id = c.course_id
          JOIN teachers t ON co.teacher_id = t.teacher_id
          LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
          WHERE e.student_id = :student_id
          ORDER BY co.academic_year DESC, co.semester DESC, c.course_code";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$grades = $stmt->fetchAll();

// Calculate GPA
$total_points = 0;
$total_credits = 0;
$completed_courses = 0;

foreach ($grades as $grade) {
    if ($grade['grade_point'] !== null) {
        $total_points += $grade['grade_point'] * $grade['credits'];
        $total_credits += $grade['credits'];
        $completed_courses++;
    }
}

$gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.00;

// Group grades by semester
$grades_by_semester = [];
foreach ($grades as $grade) {
    $key = $grade['academic_year'] . ' - Semester ' . $grade['semester'];
    if (!isset($grades_by_semester[$key])) {
        $grades_by_semester[$key] = [];
    }
    $grades_by_semester[$key][] = $grade;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Student Panel</title>
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
        .gpa-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .gpa-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .grade-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .semester-card {
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
                        <a class="nav-link active" href="grades.php">
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
                        <span class="navbar-brand mb-0 h1">My Grades</span>
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
                    <!-- GPA Card -->
                    <div class="gpa-card">
                        <div class="gpa-number"><?php echo $gpa; ?></div>
                        <h4 class="mb-2">Current GPA</h4>
                        <p class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            <?php echo $completed_courses; ?> courses completed
                        </p>
                    </div>
                    
                    <!-- Grades by Semester -->
                    <?php if (empty($grades_by_semester)): ?>
                        <div class="content-card">
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No grades available</h5>
                                <p class="text-muted">Your grades will appear here once they are submitted by your teachers.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($grades_by_semester as $semester => $semester_grades): ?>
                            <div class="semester-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo $semester; ?>
                                </h5>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Name</th>
                                                <th>Credits</th>
                                                <th>Teacher</th>
                                                <th>Grade</th>
                                                <th>Graded Date</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($semester_grades as $grade): ?>
                                                <tr>
                                                    <td><?php echo $grade['course_code']; ?></td>
                                                    <td><?php echo $grade['course_name']; ?></td>
                                                    <td><?php echo $grade['credits']; ?></td>
                                                    <td><?php echo $grade['teacher_first_name'] . ' ' . $grade['teacher_last_name']; ?></td>
                                                    <td>
                                                        <?php if ($grade['grade_letter']): ?>
                                                            <span class="badge bg-<?php echo $grade['grade_point'] >= 3.0 ? 'success' : ($grade['grade_point'] >= 2.0 ? 'warning' : 'danger'); ?> grade-badge">
                                                                <?php echo $grade['grade_letter']; ?> (<?php echo $grade['grade_point']; ?>)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary grade-badge">Not Graded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($grade['graded_at']): ?>
                                                            <?php echo $functions->formatDateTime($grade['graded_at'], 'M d, Y'); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $grade['remarks'] ?: '<span class="text-muted">-</span>'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Semester GPA -->
                                <?php
                                $semester_points = 0;
                                $semester_credits = 0;
                                foreach ($semester_grades as $grade) {
                                    if ($grade['grade_point'] !== null) {
                                        $semester_points += $grade['grade_point'] * $grade['credits'];
                                        $semester_credits += $grade['credits'];
                                    }
                                }
                                $semester_gpa = $semester_credits > 0 ? round($semester_points / $semester_credits, 2) : 0.00;
                                ?>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <strong>Semester GPA: <?php echo $semester_gpa; ?></strong>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <small class="text-muted">
                                                <?php echo $semester_credits; ?> credits completed
                                            </small>
                                        </div>
                                    </div>
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
</body>
</html>
