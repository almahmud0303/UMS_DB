<?php
// admin/students.php - Student Management (CRUD)

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
        $first_name = $functions->sanitize($_POST['first_name']);
        $last_name = $functions->sanitize($_POST['last_name']);
        $student_id_number = $functions->sanitize($_POST['student_id_number']);
        $roll_number = $functions->sanitize($_POST['roll_number']);
        $department_id = $_POST['department_id'];
        $program_id = $_POST['program_id'];
        $admission_date = $_POST['admission_date'];
        $session = $functions->sanitize($_POST['session']);
        $phone = $functions->sanitize($_POST['phone']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $emergency_contact = $functions->sanitize($_POST['emergency_contact']);
        
        // Create user account first
        $username = strtolower($first_name . '.' . $last_name);
        $password = password_hash('password', PASSWORD_DEFAULT);
        $email = $username . '@student.university.edu';
        
        try {
            $conn->beginTransaction();
            
            // Insert user
            $query = "INSERT INTO users (username, password, role, email) VALUES (:username, :password, 'student', :email)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user_id = $conn->lastInsertId();
            
            // Insert student
            $query = "INSERT INTO students (user_id, first_name, last_name, student_id_number, roll_number, 
                     department_id, program_id, admission_date, session, phone, date_of_birth, gender, emergency_contact) 
                     VALUES (:user_id, :first_name, :last_name, :student_id_number, :roll_number, 
                     :department_id, :program_id, :admission_date, :session, :phone, :date_of_birth, :gender, :emergency_contact)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':student_id_number', $student_id_number);
            $stmt->bindParam(':roll_number', $roll_number);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':program_id', $program_id);
            $stmt->bindParam(':admission_date', $admission_date);
            $stmt->bindParam(':session', $session);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':emergency_contact', $emergency_contact);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = 'Student added successfully!';
                $message_type = 'success';
            } else {
                $conn->rollback();
                $message = 'Error adding student.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    if ($action === 'edit') {
        $student_id = $_POST['student_id'];
        $first_name = $functions->sanitize($_POST['first_name']);
        $last_name = $functions->sanitize($_POST['last_name']);
        $student_id_number = $functions->sanitize($_POST['student_id_number']);
        $roll_number = $functions->sanitize($_POST['roll_number']);
        $department_id = $_POST['department_id'];
        $program_id = $_POST['program_id'];
        $admission_date = $_POST['admission_date'];
        $session = $functions->sanitize($_POST['session']);
        $phone = $functions->sanitize($_POST['phone']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $emergency_contact = $functions->sanitize($_POST['emergency_contact']);
        
        try {
            $query = "UPDATE students SET first_name = :first_name, last_name = :last_name, 
                     student_id_number = :student_id_number, roll_number = :roll_number, 
                     department_id = :department_id, program_id = :program_id, 
                     admission_date = :admission_date, session = :session, phone = :phone, 
                     date_of_birth = :date_of_birth, gender = :gender, emergency_contact = :emergency_contact 
                     WHERE student_id = :student_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':student_id_number', $student_id_number);
            $stmt->bindParam(':roll_number', $roll_number);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':program_id', $program_id);
            $stmt->bindParam(':admission_date', $admission_date);
            $stmt->bindParam(':session', $session);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':emergency_contact', $emergency_contact);
            
            if ($stmt->execute()) {
                $message = 'Student updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating student.';
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
    $student_id = $_GET['delete'];
    
    try {
        $conn->beginTransaction();
        
        // Get user_id first
        $query = "SELECT user_id FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $student = $stmt->fetch();
        
        if ($student) {
            // Delete student
            $query = "DELETE FROM students WHERE student_id = :student_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            // Delete user
            $query = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $student['user_id']);
            $stmt->execute();
            
            $conn->commit();
            $message = 'Student deleted successfully!';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get students with department and program info
$query = "SELECT s.*, d.name as department_name, p.name as program_name, u.username, u.email 
          FROM students s 
          JOIN departments d ON s.department_id = d.department_id 
          JOIN programs p ON s.program_id = p.program_id 
          JOIN users u ON s.user_id = u.user_id 
          ORDER BY s.student_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll();

// Get departments and programs for forms
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

$query = "SELECT * FROM programs ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$programs = $stmt->fetchAll();

// Get student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $student_id = $_GET['edit'];
    $query = "SELECT * FROM students WHERE student_id = :student_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $edit_student = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Admin Panel</title>
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
                        <a class="nav-link active" href="students.php">
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
                        <span class="navbar-brand mb-0 h1">Student Management</span>
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
                    
                    <!-- Add/Edit Student Form -->
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $edit_student): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-user-plus me-2"></i>
                                <?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?php echo $edit_student ? 'edit' : 'add'; ?>">
                                <?php if ($edit_student): ?>
                                    <input type="hidden" name="student_id" value="<?php echo $edit_student['student_id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo $edit_student ? $edit_student['first_name'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo $edit_student ? $edit_student['last_name'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Student ID Number</label>
                                        <input type="text" class="form-control" name="student_id_number" 
                                               value="<?php echo $edit_student ? $edit_student['student_id_number'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Roll Number</label>
                                        <input type="text" class="form-control" name="roll_number" 
                                               value="<?php echo $edit_student ? $edit_student['roll_number'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" name="department_id" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['department_id']; ?>" 
                                                        <?php echo ($edit_student && $edit_student['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $dept['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Program</label>
                                        <select class="form-select" name="program_id" required>
                                            <option value="">Select Program</option>
                                            <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo $program['program_id']; ?>" 
                                                        <?php echo ($edit_student && $edit_student['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $program['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Admission Date</label>
                                        <input type="date" class="form-control" name="admission_date" 
                                               value="<?php echo $edit_student ? $edit_student['admission_date'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Session</label>
                                        <input type="text" class="form-control" name="session" 
                                               value="<?php echo $edit_student ? $edit_student['session'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo $edit_student ? $edit_student['phone'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" 
                                               value="<?php echo $edit_student ? $edit_student['date_of_birth'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($edit_student && $edit_student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($edit_student && $edit_student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($edit_student && $edit_student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Emergency Contact</label>
                                        <input type="tel" class="form-control" name="emergency_contact" 
                                               value="<?php echo $edit_student ? $edit_student['emergency_contact'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $edit_student ? 'Update Student' : 'Add Student'; ?>
                                    </button>
                                    <a href="students.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Students List -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Students List
                            </h5>
                            <a href="students.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Add New Student
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Student ID</th>
                                        <th>Roll No</th>
                                        <th>Department</th>
                                        <th>Program</th>
                                        <th>Session</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['student_id']; ?></td>
                                            <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                            <td><?php echo $student['student_id_number']; ?></td>
                                            <td><?php echo $student['roll_number']; ?></td>
                                            <td><?php echo $student['department_name']; ?></td>
                                            <td><?php echo $student['program_name']; ?></td>
                                            <td><?php echo $student['session']; ?></td>
                                            <td><?php echo $student['phone']; ?></td>
                                            <td>
                                                <a href="students.php?edit=<?php echo $student['student_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="students.php?delete=<?php echo $student['student_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this student?')">
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
            $('#studentsTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
