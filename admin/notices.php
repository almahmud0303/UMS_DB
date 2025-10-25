<?php
// admin/notices.php - Notice Management

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
        $title = $functions->sanitize($_POST['title']);
        $description = $functions->sanitize($_POST['description']);
        $target_audience = $_POST['target_audience'];
        $priority = $_POST['priority'];
        $expiry_date = $_POST['expiry_date'] ?: null;
        
        try {
            $query = "INSERT INTO notices (title, description, posted_by, target_audience, priority, expiry_date) 
                     VALUES (:title, :description, :posted_by, :target_audience, :priority, :expiry_date)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':posted_by', $_SESSION['user_id']);
            $stmt->bindParam(':target_audience', $target_audience);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':expiry_date', $expiry_date);
            
            if ($stmt->execute()) {
                $message = 'Notice posted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error posting notice.';
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
    $notice_id = $_GET['delete'];
    
    try {
        $query = "DELETE FROM notices WHERE notice_id = :notice_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':notice_id', $notice_id);
        
        if ($stmt->execute()) {
            $message = 'Notice deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting notice.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get notices
$query = "SELECT n.*, u.username 
          FROM notices n 
          JOIN users u ON n.posted_by = u.user_id 
          ORDER BY n.date_posted DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$notices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Management - Admin Panel</title>
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
                        <a class="nav-link active" href="notices.php">
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
                        <span class="navbar-brand mb-0 h1">Notice Management</span>
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
                    
                    <!-- Add Notice Form -->
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-bullhorn me-2"></i>
                                Post New Notice
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add">
                                
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="title" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="5" required></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Target Audience</label>
                                        <select class="form-select" name="target_audience" required>
                                            <option value="all">All Users</option>
                                            <option value="students">Students Only</option>
                                            <option value="teachers">Teachers Only</option>
                                            <option value="admin">Admin Only</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Priority</label>
                                        <select class="form-select" name="priority" required>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Expiry Date (Optional)</label>
                                        <input type="date" class="form-control" name="expiry_date">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Post Notice
                                    </button>
                                    <a href="notices.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Notices List -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-bullhorn me-2"></i>
                                All Notices
                            </h5>
                            <a href="notices.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Post New Notice
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="noticesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Target</th>
                                        <th>Priority</th>
                                        <th>Posted By</th>
                                        <th>Date Posted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notices as $notice): ?>
                                        <tr>
                                            <td><?php echo $notice['notice_id']; ?></td>
                                            <td><?php echo $notice['title']; ?></td>
                                            <td><?php echo substr($notice['description'], 0, 50) . '...'; ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($notice['target_audience']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $notice['priority'] === 'urgent' ? 'danger' : ($notice['priority'] === 'high' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($notice['priority']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $notice['username']; ?></td>
                                            <td><?php echo $functions->formatDateTime($notice['date_posted'], 'M d, Y'); ?></td>
                                            <td>
                                                <a href="notices.php?delete=<?php echo $notice['notice_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this notice?')">
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
            $('#noticesTable').DataTable({
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
