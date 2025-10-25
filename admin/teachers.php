<?php
// admin/teachers.php - Teacher Management (CRUD)

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
        $first_name = $functions->sanitize($_POST['first_name']);
        $last_name = $functions->sanitize($_POST['last_name']);
        $employee_id = $functions->sanitize($_POST['employee_id']);
        $department_id = $_POST['department_id'];
        $designation = $functions->sanitize($_POST['designation']);
        $phone = $functions->sanitize($_POST['phone']);
        $hire_date = $_POST['hire_date'];
        $salary = $_POST['salary'];
        
        // Create user account first
        $username = strtolower($first_name . '.' . $last_name);
        $password = password_hash('password', PASSWORD_DEFAULT);
        $email = $username . '@university.edu';
        
        try {
            $conn->beginTransaction();
            
            // Insert user
            $query = "INSERT INTO users (username, password, role, email) VALUES (:username, :password, 'teacher', :email)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user_id = $conn->lastInsertId();
            
            // Insert teacher
            $query = "INSERT INTO teachers (user_id, first_name, last_name, employee_id, department_id, designation, phone, hire_date, salary) 
                     VALUES (:user_id, :first_name, :last_name, :employee_id, :department_id, :designation, :phone, :hire_date, :salary)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':designation', $designation);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':hire_date', $hire_date);
            $stmt->bindParam(':salary', $salary);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = 'Teacher added successfully!';
                $message_type = 'success';
            } else {
                $conn->rollback();
                $message = 'Error adding teacher.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    if ($action === 'edit') {
        $teacher_id = $_POST['teacher_id'];
        $first_name = $functions->sanitize($_POST['first_name']);
        $last_name = $functions->sanitize($_POST['last_name']);
        $employee_id = $functions->sanitize($_POST['employee_id']);
        $department_id = $_POST['department_id'];
        $designation = $functions->sanitize($_POST['designation']);
        $phone = $functions->sanitize($_POST['phone']);
        $hire_date = $_POST['hire_date'];
        $salary = $_POST['salary'];
        
        try {
            $query = "UPDATE teachers SET first_name = :first_name, last_name = :last_name, 
                     employee_id = :employee_id, department_id = :department_id, 
                     designation = :designation, phone = :phone, hire_date = :hire_date, salary = :salary 
                     WHERE teacher_id = :teacher_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':designation', $designation);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':hire_date', $hire_date);
            $stmt->bindParam(':salary', $salary);
            
            if ($stmt->execute()) {
                $message = 'Teacher updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating teacher.';
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
    $teacher_id = $_GET['delete'];
    
    try {
        $conn->beginTransaction();
        
        // Get user_id first
        $query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        $teacher = $stmt->fetch();
        
        if ($teacher) {
            // Delete teacher
            $query = "DELETE FROM teachers WHERE teacher_id = :teacher_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            
            // Delete user
            $query = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $teacher['user_id']);
            $stmt->execute();
            
            $conn->commit();
            $message = 'Teacher deleted successfully!';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get teachers with department info
$query = "SELECT t.*, d.name as department_name, u.username, u.email 
          FROM teachers t 
          JOIN departments d ON t.department_id = d.department_id 
          JOIN users u ON t.user_id = u.user_id 
          ORDER BY t.teacher_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll();

// Get departments for forms
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

// Get teacher for editing
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $teacher_id = $_GET['edit'];
    $query = "SELECT * FROM teachers WHERE teacher_id = :teacher_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':teacher_id', $teacher_id);
    $stmt->execute();
    $edit_teacher = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management - Admin Panel</title>
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
                        <a class="nav-link active" href="teachers.php">
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
                        <span class="navbar-brand mb-0 h1">Teacher Management</span>
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
                    
                    <!-- Add/Edit Teacher Form -->
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $edit_teacher): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                <?php echo $edit_teacher ? 'Edit Teacher' : 'Add New Teacher'; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?php echo $edit_teacher ? 'edit' : 'add'; ?>">
                                <?php if ($edit_teacher): ?>
                                    <input type="hidden" name="teacher_id" value="<?php echo $edit_teacher['teacher_id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['first_name'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['last_name'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" name="employee_id" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['employee_id'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" name="department_id" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['department_id']; ?>" 
                                                        <?php echo ($edit_teacher && $edit_teacher['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $dept['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Designation</label>
                                        <input type="text" class="form-control" name="designation" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['designation'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['phone'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hire Date</label>
                                        <input type="date" class="form-control" name="hire_date" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['hire_date'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Salary</label>
                                        <input type="number" class="form-control" name="salary" step="0.01" 
                                               value="<?php echo $edit_teacher ? $edit_teacher['salary'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $edit_teacher ? 'Update Teacher' : 'Add Teacher'; ?>
                                    </button>
                                    <a href="teachers.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Teachers List -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                Teachers List
                            </h5>
                            <a href="teachers.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Add New Teacher
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="teachersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Employee ID</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Phone</th>
                                        <th>Salary</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo $teacher['teacher_id']; ?></td>
                                            <td><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></td>
                                            <td><?php echo $teacher['employee_id']; ?></td>
                                            <td><?php echo $teacher['department_name']; ?></td>
                                            <td><?php echo $teacher['designation']; ?></td>
                                            <td><?php echo $teacher['phone']; ?></td>
                                            <td>৳<?php echo number_format($teacher['salary'], 2); ?></td>
                                            <td>
                                                <a href="teachers.php?edit=<?php echo $teacher['teacher_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="teachers.php?delete=<?php echo $teacher['teacher_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this teacher?')">
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
            $('#teachersTable').DataTable({
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
