<?php
// admin/courses.php - Course Management (CRUD)

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
    
    if ($action === 'add') {
        $course_code = $functions->sanitize($_POST['course_code']);
        $course_name = $functions->sanitize($_POST['course_name']);
        $credits = $_POST['credits'];
        $department_id = $_POST['department_id'];
        $description = $functions->sanitize($_POST['description']);
        $prerequisites = $functions->sanitize($_POST['prerequisites']);
        
        try {
            $query = "INSERT INTO courses (course_code, course_name, credits, department_id, description, prerequisites) 
                     VALUES (:course_code, :course_name, :credits, :department_id, :description, :prerequisites)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':course_code', $course_code);
            $stmt->bindParam(':course_name', $course_name);
            $stmt->bindParam(':credits', $credits);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':prerequisites', $prerequisites);
            
            if ($stmt->execute()) {
                $message = 'Course added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding course.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    if ($action === 'edit') {
        $course_id = $_POST['course_id'];
        $course_code = $functions->sanitize($_POST['course_code']);
        $course_name = $functions->sanitize($_POST['course_name']);
        $credits = $_POST['credits'];
        $department_id = $_POST['department_id'];
        $description = $functions->sanitize($_POST['description']);
        $prerequisites = $functions->sanitize($_POST['prerequisites']);
        
        try {
            $query = "UPDATE courses SET course_code = :course_code, course_name = :course_name, 
                     credits = :credits, department_id = :department_id, 
                     description = :description, prerequisites = :prerequisites 
                     WHERE course_id = :course_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':course_code', $course_code);
            $stmt->bindParam(':course_name', $course_name);
            $stmt->bindParam(':credits', $credits);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':prerequisites', $prerequisites);
            
            if ($stmt->execute()) {
                $message = 'Course updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating course.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $course_id = $_GET['delete'];
    
    try {
        $query = "DELETE FROM courses WHERE course_id = :course_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':course_id', $course_id);
        
        if ($stmt->execute()) {
            $message = 'Course deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting course.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get courses with department info and teacher assignments
$query = "SELECT c.*, d.name as department_name,
                 GROUP_CONCAT(
                     CONCAT(t.first_name, ' ', t.last_name, ' (Semester ', co.semester, ')') 
                     ORDER BY co.academic_year DESC, co.semester DESC
                     SEPARATOR ', '
                 ) as assigned_teachers,
                 COUNT(DISTINCT co.teacher_id) as teacher_count
          FROM courses c 
          JOIN departments d ON c.department_id = d.department_id 
          LEFT JOIN course_offerings co ON c.course_id = co.course_id
          LEFT JOIN teachers t ON co.teacher_id = t.teacher_id
          GROUP BY c.course_id
          ORDER BY c.course_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$courses = $stmt->fetchAll();

// Get departments for forms
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

// Handle teacher assignment
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'assign_teacher') {
    $course_id = $_POST['course_id'];
    $teacher_id = $_POST['teacher_id'];
    $semester = $_POST['semester'];
    $academic_year = $functions->sanitize($_POST['academic_year']);
    $max_students = $_POST['max_students'];
    $schedule = $functions->sanitize($_POST['schedule']);
    $classroom = $functions->sanitize($_POST['classroom']);
    
    try {
        $query = "INSERT INTO course_offerings (course_id, teacher_id, semester, academic_year, max_students, schedule, classroom) 
                 VALUES (:course_id, :teacher_id, :semester, :academic_year, :max_students, :schedule, :classroom)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':academic_year', $academic_year);
        $stmt->bindParam(':max_students', $max_students);
        $stmt->bindParam(':schedule', $schedule);
        $stmt->bindParam(':classroom', $classroom);
        
        if ($stmt->execute()) {
            $message = 'Teacher assigned to course successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error assigning teacher.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get teachers for assignment dropdown
$query = "SELECT t.*, d.name as department_name FROM teachers t 
          JOIN departments d ON t.department_id = d.department_id 
          ORDER BY t.first_name, t.last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll();

// Get course for teacher assignment
$assign_course = null;
if (isset($_GET['assign_teacher'])) {
    $course_id = $_GET['assign_teacher'];
    $query = "SELECT * FROM courses WHERE course_id = :course_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();
    $assign_course = $stmt->fetch();
    
    // Get current assignments for this course
    $query = "SELECT co.*, t.first_name, t.last_name, t.employee_id 
              FROM course_offerings co
              JOIN teachers t ON co.teacher_id = t.teacher_id
              WHERE co.course_id = :course_id
              ORDER BY co.academic_year DESC, co.semester DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();
    $current_assignments = $stmt->fetchAll();
}

// Get course for editing
$edit_course = null;
if (isset($_GET['edit'])) {
    $course_id = $_GET['edit'];
    $query = "SELECT * FROM courses WHERE course_id = :course_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();
    $edit_course = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Admin Panel</title>
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
                        <a class="nav-link active" href="courses.php">
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
                        <span class="navbar-brand mb-0 h1">Course Management</span>
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
                    
                    <!-- Assign Teacher to Course Form -->
                    <?php if ($assign_course): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-user-tie me-2"></i>
                                Assign Teacher to <?php echo $assign_course['course_code']; ?> - <?php echo $assign_course['course_name']; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="assign_teacher">
                                <input type="hidden" name="course_id" value="<?php echo $assign_course['course_id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Select Teacher</label>
                                        <select class="form-select" name="teacher_id" required>
                                            <option value="">Select Teacher</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                                <option value="<?php echo $teacher['teacher_id']; ?>">
                                                    <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?> 
                                                    (<?php echo $teacher['employee_id']; ?>) - <?php echo $teacher['department_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Semester</label>
                                        <select class="form-select" name="semester" required>
                                            <option value="1">Semester 1</option>
                                            <option value="2">Semester 2</option>
                                            <option value="3">Semester 3</option>
                                            <option value="4">Semester 4</option>
                                            <option value="5">Semester 5</option>
                                            <option value="6">Semester 6</option>
                                            <option value="7">Semester 7</option>
                                            <option value="8">Semester 8</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Academic Year</label>
                                        <input type="text" class="form-control" name="academic_year" 
                                               placeholder="e.g., 2024-25" value="2024-25" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Max Students</label>
                                        <input type="number" class="form-control" name="max_students" 
                                               value="30" min="1" max="100" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Schedule</label>
                                        <input type="text" class="form-control" name="schedule" 
                                               placeholder="e.g., MWF 9:00-10:00">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Classroom</label>
                                        <input type="text" class="form-control" name="classroom" 
                                               placeholder="e.g., CSE-101">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Assign Teacher
                                    </button>
                                    <a href="courses.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                            
                            <?php if (isset($current_assignments) && count($current_assignments) > 0): ?>
                                <hr class="my-4">
                                <h6 class="mb-3">Current Teacher Assignments</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Teacher</th>
                                                <th>Semester</th>
                                                <th>Academic Year</th>
                                                <th>Schedule</th>
                                                <th>Classroom</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($current_assignments as $assignment): ?>
                                                <tr>
                                                    <td><?php echo $assignment['first_name'] . ' ' . $assignment['last_name']; ?></td>
                                                    <td>Semester <?php echo $assignment['semester']; ?></td>
                                                    <td><?php echo $assignment['academic_year']; ?></td>
                                                    <td><?php echo $assignment['schedule'] ?: '-'; ?></td>
                                                    <td><?php echo $assignment['classroom'] ?: '-'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add/Edit Course Form -->
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $edit_course): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-book me-2"></i>
                                <?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?php echo $edit_course ? 'edit' : 'add'; ?>">
                                <?php if ($edit_course): ?>
                                    <input type="hidden" name="course_id" value="<?php echo $edit_course['course_id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Course Code</label>
                                        <input type="text" class="form-control" name="course_code" 
                                               value="<?php echo $edit_course ? $edit_course['course_code'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Course Name</label>
                                        <input type="text" class="form-control" name="course_name" 
                                               value="<?php echo $edit_course ? $edit_course['course_name'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Credits</label>
                                        <input type="number" class="form-control" name="credits" min="1" max="6" 
                                               value="<?php echo $edit_course ? $edit_course['credits'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" name="department_id" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['department_id']; ?>" 
                                                        <?php echo ($edit_course && $edit_course['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $dept['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo $edit_course ? $edit_course['description'] : ''; ?></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Prerequisites</label>
                                        <input type="text" class="form-control" name="prerequisites" 
                                               value="<?php echo $edit_course ? $edit_course['prerequisites'] : ''; ?>"
                                               placeholder="e.g., CSE101,CSE102">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $edit_course ? 'Update Course' : 'Add Course'; ?>
                                    </button>
                                    <a href="courses.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Courses List -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2"></i>
                                Courses List
                            </h5>
                            <a href="courses.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Add New Course
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="coursesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Credits</th>
                                        <th>Department</th>
                                        <th>Assigned Teachers</th>
                                        <th>Prerequisites</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?php echo $course['course_id']; ?></td>
                                            <td><?php echo $course['course_code']; ?></td>
                                            <td><?php echo $course['course_name']; ?></td>
                                            <td><?php echo $course['credits']; ?></td>
                                            <td><?php echo $course['department_name']; ?></td>
                                            <td>
                                                <?php if ($course['assigned_teachers']): ?>
                                                    <span class="text-success"><?php echo $course['assigned_teachers']; ?></span>
                                                    <br><small class="text-muted"><?php echo $course['teacher_count']; ?> teacher(s)</small>
                                                <?php else: ?>
                                                    <span class="text-danger">No teacher assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $course['prerequisites'] ?: 'None'; ?></td>
                                            <td>
                                                <a href="courses.php?assign_teacher=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-sm btn-outline-success me-1" title="Assign Teacher">
                                                    <i class="fas fa-user-tie"></i>
                                                </a>
                                                <a href="courses.php?edit=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="courses.php?delete=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this course?')" title="Delete">
                                                    <i class="fas fa-trash"></i>
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
            $('#coursesTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
