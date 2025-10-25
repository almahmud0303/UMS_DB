<?php
// student/payments.php - Student Payments View

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/debug.php';
require_once '../includes/debug_toggle.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new DebugDatabase();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get student's payment records
$query = "SELECT p.*, f.fee_type, f.amount as fee_amount, f.semester as fee_semester,
                 pr.name as program_name
          FROM payments p
          JOIN fees f ON p.fee_id = f.fee_id
          JOIN students s ON p.student_id = s.student_id
          JOIN programs pr ON s.program_id = pr.program_id
          WHERE p.student_id = :student_id
          ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$payments = $stmt->fetchAll();

// Calculate payment statistics
$total_paid = array_sum(array_column($payments, 'amount'));
$completed_payments = count(array_filter($payments, function($payment) {
    return $payment['status'] === 'completed';
}));
$pending_payments = count(array_filter($payments, function($payment) {
    return $payment['status'] === 'pending';
}));

// Get student's program info for fee structure
$query = "SELECT s.*, pr.name as program_name, pr.duration_years
          FROM students s
          JOIN programs pr ON s.program_id = pr.program_id
          WHERE s.student_id = :student_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$student_info = $stmt->fetch();

// Get fee structure for student's program
$query = "SELECT * FROM fees WHERE program_id = :program_id ORDER BY semester, fee_type";
$stmt = $conn->prepare($query);
$stmt->bindParam(':program_id', $student_info['program_id']);
$stmt->execute();
$fee_structure = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments - Student Panel</title>
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
        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        .amount-display {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
        .status-badge {
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
                        <a class="nav-link" href="courses.php">
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
                        <a class="nav-link active" href="payments.php">
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
                        <span class="navbar-brand mb-0 h1">My Payments</span>
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
                    <!-- Payment Summary -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            Payment Summary
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="amount-display">৳<?php echo number_format($total_paid, 2); ?></div>
                                    <p class="text-muted mb-0">Total Paid</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="amount-display text-success"><?php echo $completed_payments; ?></div>
                                    <p class="text-muted mb-0">Completed Payments</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="amount-display text-warning"><?php echo $pending_payments; ?></div>
                                    <p class="text-muted mb-0">Pending Payments</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="amount-display text-info"><?php echo count($payments); ?></div>
                                    <p class="text-muted mb-0">Total Transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment History -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-history me-2"></i>
                            Payment History
                        </h5>
                        
                        <?php if (empty($payments)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No payment records</h5>
                                <p class="text-muted">Your payment history will appear here once you make payments.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Fee Type</th>
                                            <th>Semester</th>
                                            <th>Amount</th>
                                            <th>Payment Date</th>
                                            <th>Status</th>
                                            <th>Transaction ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo $payment['payment_id']; ?></td>
                                                <td><?php echo $payment['fee_type']; ?></td>
                                                <td><?php echo $payment['fee_semester']; ?></td>
                                                <td>৳<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo $functions->formatDateTime($payment['payment_date'], 'M d, Y'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger'); ?> status-badge">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $payment['transaction_id'] ?: '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Fee Structure -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-list me-2"></i>
                            Fee Structure - <?php echo $student_info['program_name']; ?>
                        </h5>
                        
                        <?php if (empty($fee_structure)): ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No fee structure available for your program.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fee Type</th>
                                            <th>Semester</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fee_structure as $fee): ?>
                                            <tr>
                                                <td><?php echo $fee['fee_type']; ?></td>
                                                <td><?php echo $fee['semester']; ?></td>
                                                <td>৳<?php echo number_format($fee['amount'], 2); ?></td>
                                                <td><?php echo $fee['due_date'] ? $functions->formatDateTime($fee['due_date'], 'M d, Y') : 'Not specified'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
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
