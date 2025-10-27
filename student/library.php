<?php
// student/library.php - Student Library View

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('student');

$database = new Database();
$conn = $database->getConnection();
$functions = new CommonFunctions();

$student_id = $_SESSION['profile_id'];

// Get all available books
$query = "SELECT lb.*, d.name as department_name
          FROM library_books lb
          LEFT JOIN departments d ON lb.department_id = d.department_id
          WHERE lb.available_copies > 0
          ORDER BY lb.title";
$stmt = $conn->prepare($query);
$stmt->execute();
$books = $stmt->fetchAll();

// Get my book issues
$query = "SELECT li.*, lb.title, lb.author, lb.isbn
          FROM library_issues li
          JOIN library_books lb ON li.book_id = lb.book_id
          WHERE li.student_id = :student_id
          ORDER BY li.issue_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$my_issues = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library - Student Panel</title>
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
        .book-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .book-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-credit-card me-2"></i>
                            Payments
                        </a>
                        <a class="nav-link active" href="library.php">
                            <i class="fas fa-book-open me-2"></i>
                            Library
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
                        <span class="navbar-brand mb-0 h1">Library</span>
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
                    <!-- My Book Issues -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-book-open me-2"></i>
                            My Book Issues
                        </h5>
                        
                        <?php if (count($my_issues) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="myIssuesTable">
                                    <thead>
                                        <tr>
                                            <th>Book</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Fine</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_issues as $issue): ?>
                                            <tr>
                                                <td><?php echo $issue['title']; ?></td>
                                                <td><?php echo $issue['author']; ?></td>
                                                <td><?php echo $issue['isbn'] ?: '-'; ?></td>
                                                <td><?php echo $functions->formatDateTime($issue['issue_date'], 'M d, Y'); ?></td>
                                                <td><?php echo $functions->formatDateTime($issue['due_date'], 'M d, Y'); ?></td>
                                                <td>
                                                    <?php if ($issue['return_date']): ?>
                                                        <?php echo $functions->formatDateTime($issue['return_date'], 'M d, Y'); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not returned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $issue['status'] === 'returned' ? 'success' : ($issue['status'] === 'overdue' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($issue['status']); ?>
                                                    </span>
                                                </td>
                                                <td>৳<?php echo number_format($issue['fine_amount'] ?? 0, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <p class="text-muted">You haven't issued any books yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Available Books -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-book me-2"></i>
                            Available Books
                        </h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="booksTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>ISBN</th>
                                        <th>Publisher</th>
                                        <th>Year</th>
                                        <th>Department</th>
                                        <th>Category</th>
                                        <th>Available Copies</th>
                                        <th>Shelf Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td><?php echo $book['title']; ?></td>
                                            <td><?php echo $book['author']; ?></td>
                                            <td><?php echo $book['isbn'] ?: '-'; ?></td>
                                            <td><?php echo $book['publisher'] ?: '-'; ?></td>
                                            <td><?php echo $book['publication_year'] ?: '-'; ?></td>
                                            <td><?php echo $book['department_name'] ?: '-'; ?></td>
                                            <td><?php echo $book['category'] ?: '-'; ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo $book['available_copies']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $book['shelf_location'] ?: '-'; ?></td>
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
            $('#booksTable').DataTable({
                "pageLength": 25,
                "order": [[0, "asc"]]
            });
            $('#myIssuesTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>
</body>
</html>

