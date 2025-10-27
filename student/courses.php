<?php
// student/courses.php - Student Courses View

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get student's enrolled courses
$query = "SELECT e.*, co.semester, co.academic_year,
                 c.course_code, c.course_name, c.credits, c.description,
                 t.first_name as teacher_first_name, t.last_name as teacher_last_name,
                 g.grade_letter, g.grade_point
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
$courses = $stmt->fetchAll();

// Group courses by semester
$courses_by_semester = [];
foreach ($courses as $course) {
    $key = $course['academic_year'] . ' - Semester ' . $course['semester'];
    if (!isset($courses_by_semester[$key])) {
        $courses_by_semester[$key] = [];
    }
    $courses_by_semester[$key][] = $course;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Panel</title>
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
        .semester-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .grade-badge {
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
                        <a class="nav-link active" href="courses.php">
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
                        <span class="navbar-brand mb-0 h1">My Courses</span>
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
                    <?php if (empty($courses_by_semester)): ?>
                        <div class="content-card">
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No courses enrolled</h5>
                                <p class="text-muted">You haven't enrolled in any courses yet. Contact the administration for course enrollment.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses_by_semester as $semester => $semester_courses): ?>
                            <div class="semester-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo $semester; ?>
                                </h5>
                                
                                <div class="row">
                                    <?php foreach ($semester_courses as $course): ?>
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
                                                    <i class="fas fa-graduation-cap me-2"></i>
                                                    <?php echo $course['credits']; ?> credits
                                                </p>
                                                <?php if ($course['description']): ?>
                                                    <p class="text-muted mb-3">
                                                        <?php echo substr($course['description'], 0, 100) . '...'; ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <?php if ($course['grade_letter']): ?>
                                                            <span class="badge bg-<?php echo $course['grade_point'] >= 3.0 ? 'success' : ($course['grade_point'] >= 2.0 ? 'warning' : 'danger'); ?> grade-badge">
                                                                <?php echo $course['grade_letter']; ?> (<?php echo $course['grade_point']; ?>)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary grade-badge">Not Graded</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-<?php echo $course['status'] === 'enrolled' ? 'success' : ($course['status'] === 'completed' ? 'info' : 'warning'); ?>">
                                                            <?php echo ucfirst($course['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
