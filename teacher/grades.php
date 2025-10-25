<?php
// teacher/grades.php - Grade Management for Teachers

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

// Get teacher's courses with enrolled students
$query = "SELECT co.*, c.course_code, c.course_name, c.credits,
                 COUNT(e.enrollment_id) as enrolled_students
          FROM course_offerings co
          JOIN courses c ON co.course_id = c.course_id
          LEFT JOIN enrollments e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
          WHERE co.teacher_id = :teacher_id
          GROUP BY co.offering_id
          ORDER BY co.academic_year DESC, co.semester DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$courses = $stmt->fetchAll();

// Get students for a specific course
$students = [];
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
        $query = "SELECT s.*, e.enrollment_id, e.enrollment_date,
                         g.grade_letter, g.grade_point, g.graded_at, g.remarks
                  FROM enrollments e
                  JOIN students s ON e.student_id = s.student_id
                  LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
                  WHERE e.offering_id = :offering_id
                  ORDER BY s.first_name, s.last_name";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':offering_id', $offering_id);
        $stmt->execute();
        $students = $stmt->fetchAll();
    }
}

// Handle grade submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_grades') {
    $offering_id = $_POST['offering_id'];
    
    try {
        $conn->beginTransaction();
        
        foreach ($_POST['grades'] as $enrollment_id => $grade_data) {
            if (!empty($grade_data['grade_letter'])) {
                $grade_letter = $grade_data['grade_letter'];
                $remarks = $functions->sanitize($grade_data['remarks']);
                
                // Calculate grade point
                $grade_points = [
                    'A+' => 4.00, 'A' => 4.00, 'A-' => 3.70,
                    'B+' => 3.30, 'B' => 3.00, 'B-' => 2.70,
                    'C+' => 2.30, 'C' => 2.00, 'C-' => 1.70,
                    'D' => 1.00, 'F' => 0.00
                ];
                
                $grade_point = $grade_points[$grade_letter] ?? 0.00;
                
                // Check if grade already exists
                $query = "SELECT grade_id FROM grades WHERE enrollment_id = :enrollment_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':enrollment_id', $enrollment_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing grade
                    $query = "UPDATE grades SET grade_letter = :grade_letter, grade_point = :grade_point, 
                             remarks = :remarks, graded_by = :graded_by, graded_at = NOW()
                             WHERE enrollment_id = :enrollment_id";
                } else {
                    // Insert new grade
                    $query = "INSERT INTO grades (enrollment_id, grade_letter, grade_point, remarks, graded_by, graded_at) 
                             VALUES (:enrollment_id, :grade_letter, :grade_point, :remarks, :graded_by, NOW())";
                }
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':enrollment_id', $enrollment_id);
                $stmt->bindParam(':grade_letter', $grade_letter);
                $stmt->bindParam(':grade_point', $grade_point);
                $stmt->bindParam(':remarks', $remarks);
                $stmt->bindParam(':graded_by', $teacher_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $message = 'Grades submitted successfully!';
        $message_type = 'success';
        
        // Refresh the page to show updated grades
        redirect("grades.php?course=$offering_id");
        
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
    <title>Grade Management - Teacher Panel</title>
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
                        <a class="nav-link active" href="grades.php">
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
                        <span class="navbar-brand mb-0 h1">Grade Management</span>
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
                            Select Course to Manage Grades
                        </h5>
                        
                        <?php if (empty($courses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No courses assigned</h5>
                                <p class="text-muted">Contact the administration to get courses assigned.</p>
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
                                                <i class="fas fa-users me-2"></i>
                                                <?php echo $course['enrolled_students']; ?> students enrolled
                                            </p>
                                            
                                            <a href="grades.php?course=<?php echo $course['offering_id']; ?>" 
                                               class="btn btn-primary w-100">
                                                <i class="fas fa-chart-line me-2"></i>
                                                Manage Grades
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Grade Management for Selected Course -->
                    <?php if (isset($selected_course) && !empty($students)): ?>
                        <div class="content-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Grades for <?php echo $selected_course['course_code'] . ' - ' . $selected_course['course_name']; ?>
                                </h5>
                                <a href="grades.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Courses
                                </a>
                            </div>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="submit_grades">
                                <input type="hidden" name="offering_id" value="<?php echo $selected_course['offering_id']; ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Name</th>
                                                <th>Roll No</th>
                                                <th>Current Grade</th>
                                                <th>New Grade</th>
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
                                                        <?php if ($student['grade_letter']): ?>
                                                            <span class="badge bg-<?php echo $student['grade_point'] >= 3.0 ? 'success' : ($student['grade_point'] >= 2.0 ? 'warning' : 'danger'); ?> grade-badge">
                                                                <?php echo $student['grade_letter']; ?> (<?php echo $student['grade_point']; ?>)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary grade-badge">Not Graded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" name="grades[<?php echo $student['enrollment_id']; ?>][grade_letter]">
                                                            <option value="">Select Grade</option>
                                                            <option value="A+" <?php echo ($student['grade_letter'] == 'A+') ? 'selected' : ''; ?>>A+ (4.00)</option>
                                                            <option value="A" <?php echo ($student['grade_letter'] == 'A') ? 'selected' : ''; ?>>A (4.00)</option>
                                                            <option value="A-" <?php echo ($student['grade_letter'] == 'A-') ? 'selected' : ''; ?>>A- (3.70)</option>
                                                            <option value="B+" <?php echo ($student['grade_letter'] == 'B+') ? 'selected' : ''; ?>>B+ (3.30)</option>
                                                            <option value="B" <?php echo ($student['grade_letter'] == 'B') ? 'selected' : ''; ?>>B (3.00)</option>
                                                            <option value="B-" <?php echo ($student['grade_letter'] == 'B-') ? 'selected' : ''; ?>>B- (2.70)</option>
                                                            <option value="C+" <?php echo ($student['grade_letter'] == 'C+') ? 'selected' : ''; ?>>C+ (2.30)</option>
                                                            <option value="C" <?php echo ($student['grade_letter'] == 'C') ? 'selected' : ''; ?>>C (2.00)</option>
                                                            <option value="C-" <?php echo ($student['grade_letter'] == 'C-') ? 'selected' : ''; ?>>C- (1.70)</option>
                                                            <option value="D" <?php echo ($student['grade_letter'] == 'D') ? 'selected' : ''; ?>>D (1.00)</option>
                                                            <option value="F" <?php echo ($student['grade_letter'] == 'F') ? 'selected' : ''; ?>>F (0.00)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="grades[<?php echo $student['enrollment_id']; ?>][remarks]"
                                                               value="<?php echo $student['remarks'] ?? ''; ?>"
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
                                        Submit Grades
                                    </button>
                                    <a href="grades.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php elseif (isset($selected_course) && empty($students)): ?>
                        <div class="content-card">
                            <div class="text-center py-5">
                                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No students enrolled</h5>
                                <p class="text-muted">Students will appear here once they enroll in this course.</p>
                                <a href="grades.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Courses
                                </a>
                            </div>
                        </div>
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
