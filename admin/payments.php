<?php
// admin/payments.php - Payment Management (Placeholder)

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
    
    if ($action === 'edit') {
        $payment_id = $_POST['payment_id'];
        $amount = $_POST['amount'];
        $status = $_POST['status'];
        $transaction_id = $functions->sanitize($_POST['transaction_id']);
        $payment_method = $functions->sanitize($_POST['payment_method']);
        
        try {
            $query = "UPDATE payments SET amount = :amount, status = :status, 
                     transaction_id = :transaction_id, payment_method = :payment_method
                     WHERE payment_id = :payment_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':transaction_id', $transaction_id);
            $stmt->bindParam(':payment_method', $payment_method);
            
            if ($stmt->execute()) {
                $message = 'Payment updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating payment.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get single payment for editing
$edit_payment = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $payment_id = $_GET['id'];
    $query = "SELECT p.*, s.first_name, s.last_name, s.student_id_number, 
                     pr.name as program_name, f.fee_type
              FROM payments p
              JOIN students s ON p.student_id = s.student_id
              JOIN programs pr ON s.program_id = pr.program_id
              JOIN fees f ON p.fee_id = f.fee_id
              WHERE p.payment_id = :payment_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':payment_id', $payment_id);
    $stmt->execute();
    $edit_payment = $stmt->fetch();
}

// Get payment records with student info
$query = "SELECT p.*, s.first_name, s.last_name, s.student_id_number, s.roll_number,
                 pr.name as program_name, f.fee_type, f.amount as fee_amount
          FROM payments p
          JOIN students s ON p.student_id = s.student_id
          JOIN programs pr ON s.program_id = pr.program_id
          JOIN fees f ON p.fee_id = f.fee_id
          ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Panel</title>
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
                        <a class="nav-link active" href="payments.php">
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
                        <span class="navbar-brand mb-0 h1">Payment Management</span>
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
                    
                    <!-- Edit Payment Form -->
                    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && $edit_payment): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-credit-card me-2"></i>
                                Edit Payment Record
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="payment_id" value="<?php echo $edit_payment['payment_id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Student</label>
                                        <input type="text" class="form-control" value="<?php echo $edit_payment['first_name'] . ' ' . $edit_payment['last_name']; ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Student ID</label>
                                        <input type="text" class="form-control" value="<?php echo $edit_payment['student_id_number']; ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Program</label>
                                        <input type="text" class="form-control" value="<?php echo $edit_payment['program_name']; ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fee Type</label>
                                        <input type="text" class="form-control" value="<?php echo $edit_payment['fee_type']; ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Amount (৳)</label>
                                        <input type="number" class="form-control" name="amount" step="0.01" value="<?php echo $edit_payment['amount']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="pending" <?php echo ($edit_payment['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="completed" <?php echo ($edit_payment['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="failed" <?php echo ($edit_payment['status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Transaction ID</label>
                                        <input type="text" class="form-control" name="transaction_id" value="<?php echo $edit_payment['transaction_id'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <input type="text" class="form-control" name="payment_method" value="<?php echo $edit_payment['payment_method'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Update Payment
                                    </button>
                                    <a href="payments.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Payments List -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            Payment Records
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Program</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Transaction ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['payment_id']; ?></td>
                                            <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                            <td><?php echo $payment['student_id_number']; ?></td>
                                            <td><?php echo $payment['program_name']; ?></td>
                                            <td><?php echo $payment['fee_type']; ?></td>
                                            <td>৳<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo $functions->formatDateTime($payment['payment_date'], 'M d, Y'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $payment['transaction_id'] ?: '-'; ?></td>
                                            <td>
                                                <a href="payments.php?action=edit&id=<?php echo $payment['payment_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
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
            $('#paymentsTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
