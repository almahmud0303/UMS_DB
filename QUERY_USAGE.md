# Query Usage and System Architecture Documentation

## Overview

This document explains the database query usage across the University Management System (UMS) and the work done to remove debug functionality.

## Changes Made

### 1. Debug Code Removal

**Date**: Current Session  
**Purpose**: Remove all debug/query display functionality to make the system production-ready

#### Files Deleted:
- `includes/debug.php` - Debug database wrapper with query tracking
- `includes/debug_toggle.php` - Debug toggle functionality

#### Files Modified:
All 26 PHP files across student, teacher, and admin modules

#### Changes:
1. **Removed debug includes**: Changed from `debug.php` and `debug_toggle.php` to standard `database.php`
2. **Replaced database class**: Changed from `DebugDatabase()` to `Database()`
3. **Removed debug UI**: Removed all debug panels, toggle buttons, and query display components

---

## Database Connection Architecture

### Core Database Class
**Location**: `config/database.php`

```php
class Database {
    private $host = 'localhost';
    private $db_name = 'umsdb';
    private $username = 'root';
    private $password = '';
    
    public function getConnection() {
        // Returns PDO connection
    }
}
```

**Usage**: Standard database connection using PDO (PHP Data Objects) for secure, prepared statement support.

---

## Query Usage by Module

### Student Module

#### 1. **Dashboard** (`student/dashboard.php`)

**Queries Used**:
1. Student Information Query
   - **Purpose**: Get complete student profile
   - **Tables**: `students`, `departments`, `programs`, `users`
   - **Join**: 4-table join to get student details with department and program names

2. Enrolled Courses Query
   - **Purpose**: Get all enrolled courses with grades and attendance
   - **Tables**: `enrollments`, `course_offerings`, `courses`, `teachers`, `grades`, `attendance`
   - **Aggregations**: COUNT for attendance, SUM for present count
   - **Groups**: By enrollment ID

3. Recent Notices Query
   - **Purpose**: Get latest notices for students
   - **Tables**: `notices`
   - **Filters**: Target audience (all/students), active notices
   - **Order**: By date posted

4. Payment Status Query
   - **Purpose**: Calculate fee payment status
   - **Tables**: `fees`, `payments`
   - **Aggregations**: SUM to calculate remaining amount
   - **Groups**: By fee type

---

#### 2. **Attendance** (`student/attendance.php`)

**Queries Used**:
1. Attendance Records Query
   - **Purpose**: Get all attendance records for the student
   - **Tables**: `attendance`, `enrollments`, `course_offerings`, `courses`, `teachers`
   - **Joins**: 4-table join to link attendance with course and teacher info
   - **Filters**: By student_id
   - **Orders**: By date DESC

**Post-Processing**:
- Calculate total vs present classes
- Calculate attendance percentage
- Group attendance by course
- Calculate per-course attendance statistics

---

#### 3. **Grades** (`student/grades.php`)

**Queries Used**:
1. Grades with Course Info Query
   - **Purpose**: Get all grades with course details
   - **Tables**: `enrollments`, `course_offerings`, `courses`, `teachers`, `grades`
   - **Left Join**: On grades (not all courses graded)
   - **Orders**: By academic year, semester, course code

**Post-Processing**:
- Calculate GPA (Grade Point Average)
- Group grades by semester
- Calculate semester-wise GPA
- Identify completed courses

---

#### 4. **Courses** (`student/courses.php`)

**Queries Used**:
1. Enrolled Courses Query
   - **Purpose**: Get enrolled courses with grades
   - **Tables**: `enrollments`, `course_offerings`, `courses`, `teachers`, `grades`
   - **Left Join**: On grades
   - **Filters**: By student_id
   - **Orders**: By academic year, semester, course code

**Post-Processing**:
- Group courses by semester
- Display in semester-wise cards
- Show grade status

---

#### 5. **Payments** (`student/payments.php`)

**Queries Used**:
1. Payment Records Query
   - **Purpose**: Get payment history
   - **Tables**: `payments`, `fees`, `students`, `programs`
   - **Joins**: To get fee details and student info
   - **Orders**: By payment date DESC

2. Student Program Info Query
   - **Purpose**: Get student's program information
   - **Tables**: `students`, `programs`
   - **Filters**: By student_id

3. Fee Structure Query
   - **Purpose**: Get all fees for student's program
   - **Tables**: `fees`
   - **Filters**: By program_id
   - **Orders**: By semester, fee type

**Post-Processing**:
- Calculate total paid amount
- Count completed vs pending payments
- Calculate remaining amounts

---

#### 6. **Profile** (`student/profile.php`)

**Queries Used**:
1. Student Profile Query
   - **Purpose**: Get complete profile information
   - **Tables**: `students`, `programs`, `departments`
   - **Joins**: To get program and department names
   - **Filters**: By student_id

2. Profile Update Query (on form submit)
   - **Purpose**: Update student profile
   - **Tables**: `students`
   - **Updates**: First name, last name, email, phone, address, emergency contacts
   - **Filters**: By student_id

---

### Teacher Module

#### 1. **Dashboard** (`teacher/dashboard.php`)

**Queries Used**:
1. Teacher Information Query
   - **Purpose**: Get teacher profile
   - **Tables**: `teachers`, `departments`
   - **Joins**: To get department name
   - **Filters**: By teacher_id

2. Assigned Courses Query
   - **Purpose**: Get courses taught by teacher
   - **Tables**: `course_offerings`, `courses`, `enrollments`
   - **Aggregations**: COUNT for student enrollments
   - **Filters**: By teacher_id
   - **Groups**: By offering ID

3. Recent Notices Query
   - **Purpose**: Get notices for teachers
   - **Tables**: `notices`
   - **Filters**: Target audience (all/teachers), active
   - **Orders**: By date DESC
   - **Limit**: 5

---

#### 2. **Students** (`teacher/students.php`)

**Queries Used**:
1. Students in Courses Query
   - **Purpose**: Get all students in teacher's courses
   - **Tables**: `enrollments`, `course_offerings`, `students`, `courses`
   - **Joins**: 3-table join
   - **Filters**: By teacher_id
   - **Distinct**: To avoid duplicates

2. Student Details Query
   - **Purpose**: Get detailed info for each student
   - **Tables**: `students`, `programs`, `departments`

---

#### 3. **Grades** (`teacher/grades.php`)

**Queries Used**:
1. Students to Grade Query
   - **Purpose**: Get students enrolled in teacher's courses
   - **Tables**: `enrollments`, `course_offerings`, `students`, `courses`, `grades`
   - **Filters**: By teacher_id
   - **Left Join**: On grades (to see ungraded students)

2. Insert/Update Grade Query (on submit)
   - **Purpose**: Save grade submission
   - **Tables**: `grades`
   - **Operations**: INSERT or UPDATE
   - **Fields**: grade_letter, grade_point, remarks

---

#### 4. **Attendance** (`teacher/attendance.php`)

**Queries Used**:
1. Students for Attendance Query
   - **Purpose**: Get students in teacher's courses
   - **Tables**: `enrollments`, `course_offerings`, `students`, `courses`
   - **Filters**: By teacher_id

2. Insert Attendance Query (on submit)
   - **Purpose**: Record attendance
   - **Tables**: `attendance`
   - **Fields**: enrollment_id, date, status, remarks

3. Attendance History Query
   - **Purpose**: Get past attendance records
   - **Tables**: `attendance`, `enrollments`, `students`, `courses`
   - **Filters**: By teacher's courses
   - **Orders**: By date DESC

---

#### 5. **Courses** (`teacher/courses.php`)

**Queries Used**:
1. Assigned Courses Query
   - **Purpose**: Get all courses assigned to teacher
   - **Tables**: `course_offerings`, `courses`, `departments`
   - **Filters**: By teacher_id
   - **Orders**: By academic year, semester

---

#### 6. **Profile** (`teacher/profile.php`)

**Queries Used**:
1. Teacher Profile Query
   - **Purpose**: Get teacher information
   - **Tables**: `teachers`, `departments`
   - **Joins**: To get department name

2. Update Profile Query
   - **Purpose**: Update teacher profile
   - **Tables**: `teachers`
   - **Filters**: By teacher_id

---

### Admin Module

#### 1. **Dashboard** (`admin/dashboard.php`)

**Queries Used** (via functions):
1. Total Students Query
2. Total Teachers Query
3. Total Courses Query
4. Active Programs Query
5. Recent Enrollments Query
6. Attendance Statistics Query

**Purpose**: Comprehensive overview of system statistics

---

#### 2. **Students Management** (`admin/students.php`)

**Queries Used**:
1. List All Students Query
   - **Purpose**: Display all students with filters
   - **Tables**: `students`, `programs`, `departments`
   - **Joins**: 2-table join
   - **Filters**: By program, department, status (if provided)
   - **Orders**: By student_id

2. Insert Student Query
   - **Purpose**: Add new student
   - **Tables**: `students`, `users`
   - **Operations**: INSERT into both tables

3. Update Student Query
   - **Purpose**: Update student information
   - **Tables**: `students`
   - **Filters**: By student_id

4. Delete Student Query
   - **Purpose**: Remove student
   - **Tables**: `students`, `users`
   - **Cascade**: Delete related records

---

#### 3. **Teachers Management** (`admin/teachers.php`)

**Queries Used**:
1. List All Teachers Query
   - **Purpose**: Display all teachers
   - **Tables**: `teachers`, `departments`
   - **Joins**: To get department name
   - **Orders**: By teacher_id

2. Insert Teacher Query
3. Update Teacher Query
4. Delete Teacher Query

**Similar pattern to student management**

---

#### 4. **Courses Management** (`admin/courses.php`)

**Queries Used**:
1. List All Courses Query
   - **Purpose**: Display all courses
   - **Tables**: `courses`, `departments`
   - **Joins**: To get department name
   - **Orders**: By course_code

2. Insert/Update/Delete Course Queries

---

#### 5. **Enrollments** (`admin/enrollments.php`)

**Queries Used**:
1. List All Enrollments Query
   - **Purpose**: Display all enrollments
   - **Tables**: `enrollments`, `students`, `course_offerings`, `courses`
   - **Joins**: 3-table join to get student and course info
   - **Orders**: By enrollment date DESC

2. Enrollment Management Queries

---

#### 6. **Grades** (`admin/grades.php`)

**Queries Used**:
1. List All Grades Query
   - **Purpose**: Display all grade records
   - **Tables**: `grades`, `enrollments`, `students`, `courses`
   - **Joins**: Multiple tables to show complete grade info
   - **Orders**: By graded date DESC

---

#### 7. **Attendance** (`admin/attendance.php`)

**Queries Used**:
1. List All Attendance Query
   - **Purpose**: Display all attendance records
   - **Tables**: `attendance`, `enrollments`, `students`, `courses`
   - **Joins**: To show student and course info
   - **Orders**: By date DESC

---

#### 8. **Departments** (`admin/departments.php`)

**Queries Used**:
1. List All Departments Query
   - **Purpose**: Display departments
   - **Tables**: `departments`
   - **Orders**: By department name

2. CRUD operations for departments

---

#### 9. **Programs** (`admin/programs.php`)

**Queries Used**:
1. List All Programs Query
   - **Purpose**: Display programs
   - **Tables**: `programs`, `departments`
   - **Joins**: To get department name
   - **Orders**: By program name

2. CRUD operations for programs

---

#### 10. **Reports** (`admin/reports.php`)

**Queries Used**:
Multiple analytical queries:
1. Student enrollment trends
2. Grade distribution
3. Attendance statistics
4. Payment summaries
5. Course popularity analysis

---

#### 11. **Notices** (`admin/notices.php`)

**Queries Used**:
1. List All Notices Query
   - **Purpose**: Display system notices
   - **Tables**: `notices`
   - **Orders**: By date_posted DESC

2. Insert Notice Query
3. Update Notice Query
4. Delete Notice Query

---

#### 12. **Library** (`admin/library.php`)

**Queries Used**:
1. Library Issues Query
   - **Purpose**: Track book issues
   - **Tables**: `library_issues`, `library_books`, `students`
   - **Joins**: To get book and student details

2. Book Management Queries

---

## Database Design

### Key Tables

1. **users** - Authentication
2. **students** - Student profiles
3. **teachers** - Teacher profiles
4. **courses** - Course catalog
5. **course_offerings** - Semester-wise course offerings
6. **enrollments** - Student course enrollments
7. **attendance** - Attendance records
8. **grades** - Grade records
9. **departments** - Department structure
10. **programs** - Academic programs
11. **fees** - Fee structure
12. **payments** - Payment records
13. **notices** - System announcements
14. **library_books** - Book catalog
15. **library_issues** - Book lending records

---

## Security Features

### 1. Prepared Statements
All queries use PDO prepared statements to prevent SQL injection:
```php
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :id");
$stmt->bindParam(':id', $student_id);
$stmt->execute();
```

### 2. Role-Based Access
Authentication via `auth.php` ensures users only access their module:
- Students → Student module only
- Teachers → Teacher module only
- Admins → Admin module + all modules

### 3. Session Management
- Session-based authentication
- Role verification on every page
- Auto-logout on session timeout

---

## Query Patterns

### Common Joins

**4-Table Join Pattern** (Student Dashboard):
```sql
students s
JOIN departments d ON s.department_id = d.department_id
JOIN programs p ON s.program_id = p.program_id
JOIN users u ON s.user_id = u.user_id
```

**Attendance with Course Info**:
```sql
attendance a
JOIN enrollments e ON a.enrollment_id = e.enrollment_id
JOIN course_offerings co ON e.offering_id = co.offering_id
JOIN courses c ON co.course_id = c.course_id
```

### Aggregation Patterns

**Counting and Summing**:
```sql
COUNT(a.attendance_id) as total_attendance
SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
```

**Calculating Remaining**:
```sql
COALESCE(SUM(p.amount), 0) as remaining_amount
(f.amount - COALESCE(SUM(p.amount), 0)) as balance
```

---

## Performance Considerations

1. **Indexing**: Foreign keys are indexed
2. **Pagination**: Large lists are paginated
3. **Selective Columns**: Only required columns are selected
4. **Eager Loading**: Related data loaded efficiently in one query where possible
5. **GROUP BY**: Used appropriately for aggregations

---

## Query Execution Flow

1. **Connection**: Get PDO connection via `Database` class
2. **Prepare**: Create prepared statement
3. **Bind**: Bind parameters (prevents SQL injection)
4. **Execute**: Run the query
5. **Fetch**: Retrieve results (fetchAll or fetch)
6. **Process**: Post-process data for display
7. **Display**: Render in view

---

## Error Handling

- Try-catch blocks around queries
- Error messages logged, not displayed to users
- Graceful degradation when queries fail
- User-friendly error messages

---

## Summary

This system uses approximately **50+ different SQL queries** across:
- **6 Student pages** (dashboard, attendance, grades, courses, payments, profile)
- **6 Teacher pages** (dashboard, students, grades, attendance, courses, profile)
- **13 Admin pages** (dashboard, students, teachers, courses, enrollments, grades, attendance, departments, programs, reports, notices, library, payments)

All queries follow secure coding practices with prepared statements and proper joins to maintain data integrity.

---

**Last Updated**: Current Session  
**Database**: MySQL (umsdb)  
**Framework**: Pure PHP with PDO  

