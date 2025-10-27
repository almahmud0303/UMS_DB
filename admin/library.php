<?php
// admin/library.php - Library Management (Placeholder)

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

// Handle form submissions for books
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action === 'add_book') {
        $title = $functions->sanitize($_POST['title']);
        $author = $functions->sanitize($_POST['author']);
        $isbn = $functions->sanitize($_POST['isbn']);
        $publisher = $functions->sanitize($_POST['publisher']);
        $publication_year = $_POST['publication_year'];
        $department_id = $_POST['department_id'];
        $category = $functions->sanitize($_POST['category']);
        $total_copies = $_POST['total_copies'];
        $available_copies = $_POST['available_copies'];
        $shelf_location = $functions->sanitize($_POST['shelf_location']);
        
        try {
            $query = "INSERT INTO library_books (title, author, isbn, publisher, publication_year, 
                     department_id, category, total_copies, available_copies, shelf_location) 
                     VALUES (:title, :author, :isbn, :publisher, :publication_year, 
                     :department_id, :category, :total_copies, :available_copies, :shelf_location)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':author', $author);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':publisher', $publisher);
            $stmt->bindParam(':publication_year', $publication_year);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':total_copies', $total_copies);
            $stmt->bindParam(':available_copies', $available_copies);
            $stmt->bindParam(':shelf_location', $shelf_location);
            
            if ($stmt->execute()) {
                $message = 'Book added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding book.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    if ($action === 'edit_book') {
        $book_id = $_POST['book_id'];
        $title = $functions->sanitize($_POST['title']);
        $author = $functions->sanitize($_POST['author']);
        $isbn = $functions->sanitize($_POST['isbn']);
        $publisher = $functions->sanitize($_POST['publisher']);
        $publication_year = $_POST['publication_year'];
        $department_id = $_POST['department_id'];
        $category = $functions->sanitize($_POST['category']);
        $total_copies = $_POST['total_copies'];
        $available_copies = $_POST['available_copies'];
        $shelf_location = $functions->sanitize($_POST['shelf_location']);
        
        try {
            $query = "UPDATE library_books SET title = :title, author = :author, isbn = :isbn, 
                     publisher = :publisher, publication_year = :publication_year, 
                     department_id = :department_id, category = :category, 
                     total_copies = :total_copies, available_copies = :available_copies, 
                     shelf_location = :shelf_location WHERE book_id = :book_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':book_id', $book_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':author', $author);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':publisher', $publisher);
            $stmt->bindParam(':publication_year', $publication_year);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':total_copies', $total_copies);
            $stmt->bindParam(':available_copies', $available_copies);
            $stmt->bindParam(':shelf_location', $shelf_location);
            
            if ($stmt->execute()) {
                $message = 'Book updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating book.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle delete
if (isset($_GET['delete_book'])) {
    $book_id = $_GET['delete_book'];
    
    try {
        $query = "DELETE FROM library_books WHERE book_id = :book_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':book_id', $book_id);
        
        if ($stmt->execute()) {
            $message = 'Book deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting book.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle book return status update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_issue_status') {
    $issue_id = $_POST['issue_id'];
    $return_date = $_POST['return_date'];
    $status = $_POST['status'];
    $fine_amount = $_POST['fine_amount'];
    $remarks = $functions->sanitize($_POST['remarks']);
    
    try {
        $query = "UPDATE library_issues SET return_date = :return_date, status = :status, 
                 fine_amount = :fine_amount, remarks = :remarks 
                 WHERE issue_id = :issue_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':issue_id', $issue_id);
        $stmt->bindParam(':return_date', $return_date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':fine_amount', $fine_amount);
        $stmt->bindParam(':remarks', $remarks);
        
        if ($stmt->execute()) {
            $message = 'Book status updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating book status.';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}


// Get books
$query = "SELECT lb.*, d.name as department_name 
          FROM library_books lb 
          LEFT JOIN departments d ON lb.department_id = d.department_id 
          ORDER BY lb.book_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$books = $stmt->fetchAll();

// Get departments for forms
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

// Get single book for editing
$edit_book = null;
if (isset($_GET['edit_book'])) {
    $book_id = $_GET['edit_book'];
    $query = "SELECT * FROM library_books WHERE book_id = :book_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':book_id', $book_id);
    $stmt->execute();
    $edit_book = $stmt->fetch();
}

// Get library issues with book and student info
$query = "SELECT li.*, lb.title, lb.author, lb.isbn,
                 s.first_name, s.last_name, s.student_id_number, s.roll_number,
                 DATEDIFF(CURRENT_DATE, li.due_date) as days_overdue
          FROM library_issues li
          JOIN library_books lb ON li.book_id = lb.book_id
          JOIN students s ON li.student_id = s.student_id
          ORDER BY li.issue_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$issues = $stmt->fetchAll();

// Get currently issued books (not returned)
$query = "SELECT li.*, lb.title, lb.author, lb.isbn,
                 s.first_name, s.last_name, s.student_id_number, s.roll_number, s.phone,
                 DATEDIFF(CURRENT_DATE, li.due_date) as days_overdue
          FROM library_issues li
          JOIN library_books lb ON li.book_id = lb.book_id
          JOIN students s ON li.student_id = s.student_id
          WHERE li.return_date IS NULL
          ORDER BY li.due_date ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$current_issues = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Admin Panel</title>
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
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-credit-card me-2"></i>
                            Payments
                        </a>
                        <a class="nav-link" href="notices.php">
                            <i class="fas fa-bullhorn me-2"></i>
                            Notices
                        </a>
                        <a class="nav-link active" href="library.php">
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
                        <span class="navbar-brand mb-0 h1">Library Management</span>
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
                    
                    <!-- Add/Edit Book Form -->
                    <?php if ((isset($_GET['action']) && $_GET['action'] === 'add_book') || isset($_GET['edit_book'])): ?>
                        <div class="content-card">
                            <h5 class="mb-3">
                                <i class="fas fa-book me-2"></i>
                                <?php echo $edit_book ? 'Edit Book' : 'Add New Book'; ?>
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?php echo $edit_book ? 'edit_book' : 'add_book'; ?>">
                                <?php if ($edit_book): ?>
                                    <input type="hidden" name="book_id" value="<?php echo $edit_book['book_id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="title" 
                                               value="<?php echo $edit_book['title'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Author</label>
                                        <input type="text" class="form-control" name="author" 
                                               value="<?php echo $edit_book['author'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" class="form-control" name="isbn" 
                                               value="<?php echo $edit_book['isbn'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Publisher</label>
                                        <input type="text" class="form-control" name="publisher" 
                                               value="<?php echo $edit_book['publisher'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Publication Year</label>
                                        <input type="number" class="form-control" name="publication_year" 
                                               value="<?php echo $edit_book['publication_year'] ?? ''; ?>" min="1900" max="2030">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-select" name="department_id">
                                            <option value="">Select Department (Optional)</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['department_id']; ?>" 
                                                        <?php echo ($edit_book && $edit_book['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $dept['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category</label>
                                        <input type="text" class="form-control" name="category" 
                                               value="<?php echo $edit_book['category'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Total Copies</label>
                                        <input type="number" class="form-control" name="total_copies" 
                                               value="<?php echo $edit_book['total_copies'] ?? '1'; ?>" min="1" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Available Copies</label>
                                        <input type="number" class="form-control" name="available_copies" 
                                               value="<?php echo $edit_book['available_copies'] ?? '1'; ?>" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Shelf Location</label>
                                        <input type="text" class="form-control" name="shelf_location" 
                                               value="<?php echo $edit_book['shelf_location'] ?? ''; ?>"
                                               placeholder="e.g., CS-A1">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $edit_book ? 'Update Book' : 'Add Book'; ?>
                                    </button>
                                    <a href="library.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Books List -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2"></i>
                                Library Books
                            </h5>
                            <a href="library.php?action=add_book" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Add New Book
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="booksTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>ISBN</th>
                                        <th>Publisher</th>
                                        <th>Year</th>
                                        <th>Department</th>
                                        <th>Category</th>
                                        <th>Copies</th>
                                        <th>Available</th>
                                        <th>Shelf</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td><?php echo $book['book_id']; ?></td>
                                            <td><?php echo $book['title']; ?></td>
                                            <td><?php echo $book['author']; ?></td>
                                            <td><?php echo $book['isbn'] ?: '-'; ?></td>
                                            <td><?php echo $book['publisher'] ?: '-'; ?></td>
                                            <td><?php echo $book['publication_year'] ?: '-'; ?></td>
                                            <td><?php echo $book['department_name'] ?: '-'; ?></td>
                                            <td><?php echo $book['category'] ?: '-'; ?></td>
                                            <td><?php echo $book['total_copies']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $book['available_copies'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $book['available_copies']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $book['shelf_location'] ?: '-'; ?></td>
                                            <td>
                                                <a href="library.php?edit_book=<?php echo $book['book_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="library.php?delete_book=<?php echo $book['book_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this book?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Currently Issued Books -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-book-open me-2"></i>
                            Currently Issued Books
                        </h5>
                        <p class="text-muted mb-3">Books that are currently with students</p>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="currentIssuesTable">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Fine Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($current_issues) > 0): ?>
                                        <?php foreach ($current_issues as $issue): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $issue['first_name'] . ' ' . $issue['last_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $issue['phone'] ?? ''; ?></small>
                                                </td>
                                                <td><?php echo $issue['student_id_number']; ?></td>
                                                <td><?php echo $issue['title']; ?></td>
                                                <td><?php echo $issue['author']; ?></td>
                                                <td><?php echo $functions->formatDateTime($issue['issue_date'], 'M d, Y'); ?></td>
                                                <td><?php echo $functions->formatDateTime($issue['due_date'], 'M d, Y'); ?></td>
                                                <td>
                                                    <?php if ($issue['days_overdue'] > 0): ?>
                                                        <span class="badge bg-danger">
                                                            <?php echo $issue['days_overdue']; ?> days overdue
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">On time</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>৳<?php echo number_format($issue['fine_amount'] ?? 0, 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($issue['days_overdue'] > 0) ? 'danger' : 'warning'; ?>">
                                                        Issued
                                                    </span>
                                                    <br>
                                                    <button type="button" class="btn btn-sm btn-outline-success mt-2" 
                                                            data-bs-toggle="modal" data-bs-target="#returnModal<?php echo $issue['issue_id']; ?>">
                                                        <i class="fas fa-undo me-1"></i>
                                                        Mark Returned
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                                <p class="text-muted mb-0">All books are in the library</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- All Book Issues History -->
                    <div class="content-card">
                        <h5 class="mb-3">
                            <i class="fas fa-history me-2"></i>
                            All Book Issues History
                        </h5>
                        <p class="text-muted mb-3">Complete history of all book issues and returns</p>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="allIssuesTable">
                                <thead>
                                    <tr>
                                        <th>Issue ID</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Fine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issues as $issue): ?>
                                        <tr>
                                            <td><?php echo $issue['issue_id']; ?></td>
                                            <td><?php echo $issue['first_name'] . ' ' . $issue['last_name']; ?></td>
                                            <td><?php echo $issue['student_id_number']; ?></td>
                                            <td><?php echo $issue['title']; ?></td>
                                            <td><?php echo $issue['author']; ?></td>
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
                                                <span class="badge bg-<?php echo $issue['return_date'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $issue['return_date'] ? 'Returned' : 'Issued'; ?>
                                                </span>
                                            </td>
                                            <td>৳<?php echo number_format($issue['fine_amount'] ?? 0, 2); ?></td>
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
    
    <!-- Return Book Modals -->
    <?php foreach ($current_issues as $issue): ?>
    <div class="modal fade" id="returnModal<?php echo $issue['issue_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-undo me-2"></i>
                        Mark Book as Returned
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_issue_status">
                    <input type="hidden" name="issue_id" value="<?php echo $issue['issue_id']; ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Student</label>
                            <p class="form-control-plaintext">
                                <?php echo $issue['first_name'] . ' ' . $issue['last_name']; ?> 
                                (<?php echo $issue['student_id_number']; ?>)
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Book</label>
                            <p class="form-control-plaintext">
                                <?php echo $issue['title']; ?> by <?php echo $issue['author']; ?>
                            </p>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Issue Date</label>
                                <p class="form-control-plaintext"><?php echo $functions->formatDateTime($issue['issue_date'], 'M d, Y'); ?></p>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Due Date</label>
                                <p class="form-control-plaintext"><?php echo $functions->formatDateTime($issue['due_date'], 'M d, Y'); ?></p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Return Date</label>
                                <input type="date" class="form-control" name="return_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="returned" selected>Returned</option>
                                    <option value="lost">Lost</option>
                                    <option value="overdue">Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fine Amount (৳)</label>
                                <input type="number" class="form-control" name="fine_amount" 
                                       step="0.01" value="<?php echo $issue['fine_amount'] ?? '0.00'; ?>" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2" 
                                          placeholder="Any additional notes..."><?php echo $issue['remarks'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>
                            Mark as Returned
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#booksTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
            $('#currentIssuesTable').DataTable({
                "pageLength": 25,
                "order": [[5, "asc"]]
            });
            $('#allIssuesTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>

</body>
</html>
