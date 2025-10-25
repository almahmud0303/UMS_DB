<?php
// admin/courses.php - Course Management (CRUD)

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/debug.php';
require_once '../includes/debug_toggle.php';

$auth = new Auth();
$auth->requireRole('admin');

$database = new DebugDatabase();
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

// Get courses with department info
$query = "SELECT c.*, d.name as department_name 
          FROM courses c 
          JOIN departments d ON c.department_id = d.department_id 
          ORDER BY c.course_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$courses = $stmt->fetchAll();

// Get departments for forms
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

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
                            <?php echo renderDebugToggle(); ?>
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
                                            <td><?php echo $course['prerequisites'] ?: 'None'; ?></td>
                                            <td>
                                                <a href="courses.php?edit=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="courses.php?delete=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this course?')">
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
