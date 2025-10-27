<?php
// admin/programs.php - Program Management (CRUD)

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
        $name = $functions->sanitize($_POST['name']);
        $code = $functions->sanitize($_POST['code']);
        $duration_years = $_POST['duration_years'];
        $department_id = $_POST['department_id'];
        $total_credits = $_POST['total_credits'];
        
        try {
            $query = "INSERT INTO programs (name, code, duration_years, department_id, total_credits) 
                     VALUES (:name, :code, :duration_years, :department_id, :total_credits)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':duration_years', $duration_years);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':total_credits', $total_credits);
            
            if ($stmt->execute()) {
                $message = 'Program added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding program.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    if ($action === 'edit') {
        $program_id = $_POST['program_id'];
        $name = $functions->sanitize($_POST['name']);
        $code = $functions->sanitize($_POST['code']);
        $duration_years = $_POST['duration_years'];
        $department_id = $_POST['department_id'];
        $total_credits = $_POST['total_credits'];
        
        try {
            $query = "UPDATE programs SET name = :name, code = :code, 
                     duration_years = :duration_years, department_id = :department_id, 
                     total_credits = :total_credits 
                     WHERE program_id = :program_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':program_id', $program_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':duration_years', $duration_years);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':total_credits', $total_credits);
            
            if ($stmt->execute()) {
                $message = 'Program updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating program.';
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
    $program_id = $_GET['delete'];
    
    try {
        $query = "DELETE FROM programs WHERE program_id = :program_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':program_id', $program_id);
        
        if ($stmt->execute()) {
            $message = 'Program deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting program.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get programs with department info
$query = "SELECT p.*, d.name as department_name 
          FROM programs p 
          JOIN departments d ON p.department_id = d.department_id 
          ORDER BY p.program_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$programs = $stmt->fetchAll();

// Get departments for forms
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

// Get program for editing
$edit_program = null;
if (isset($_GET['edit'])) {
    $program_id = $_GET['edit'];
    $query = "SELECT * FROM programs WHERE program_id = :program_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':program_id', $program_id);
    $stmt->execute();
    $edit_program = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Management - Admin Panel</title>
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
                        <a class="nav-link" href="courses.php">
                            <i class="fas fa-book me-2"></i>
                            Courses
                        </a>
                        <a class="nav-link" href="departments.php">
                            <i class="fas fa-building me-2"></i>
                            Departments
                        </a>
                        <a class="nav-link active" href="programs.php">
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
                        <span class="navbar-brand mb-0 h1">Program Management</span>
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
                    
                    <!-- Add/Edit Program Form -->
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $edit_program): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-certificate me-2"></i>
                                <?php echo $edit_program ? 'Edit Program' : 'Add New Program'; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?php echo $edit_program ? 'edit' : 'add'; ?>">
                                <?php if ($edit_program): ?>
                                    <input type="hidden" name="program_id" value="<?php echo $edit_program['program_id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Program Name</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo $edit_program ? $edit_program['name'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Program Code</label>
                                        <input type="text" class="form-control" name="code" 
                                               value="<?php echo $edit_program ? $edit_program['code'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Duration (Years)</label>
                                        <input type="number" class="form-control" name="duration_years" min="1" max="6" 
                                               value="<?php echo $edit_program ? $edit_program['duration_years'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" name="department_id" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['department_id']; ?>" 
                                                        <?php echo ($edit_program && $edit_program['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $dept['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Total Credits</label>
                                        <input type="number" class="form-control" name="total_credits" min="60" max="200" 
                                               value="<?php echo $edit_program ? $edit_program['total_credits'] : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $edit_program ? 'Update Program' : 'Add Program'; ?>
                                    </button>
                                    <a href="programs.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Programs List -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-certificate me-2"></i>
                                Programs List
                            </h5>
                            <a href="programs.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Add New Program
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="programsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Program Name</th>
                                        <th>Code</th>
                                        <th>Duration</th>
                                        <th>Department</th>
                                        <th>Credits</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programs as $program): ?>
                                        <tr>
                                            <td><?php echo $program['program_id']; ?></td>
                                            <td><?php echo $program['name']; ?></td>
                                            <td><?php echo $program['code']; ?></td>
                                            <td><?php echo $program['duration_years']; ?> years</td>
                                            <td><?php echo $program['department_name']; ?></td>
                                            <td><?php echo $program['total_credits']; ?></td>
                                            <td>
                                                <a href="programs.php?edit=<?php echo $program['program_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="programs.php?delete=<?php echo $program['program_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this program?')">
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
            $('#programsTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
