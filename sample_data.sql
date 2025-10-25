-- Sample Data Insertion for University Management System
-- Database: umsdb

USE umsdb;

-- Insert sample users
INSERT INTO users (username, password, role, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@university.edu'),
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'john.doe@university.edu'),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'jane.smith@university.edu'),
('teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'mike.johnson@university.edu'),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'alice.brown@student.university.edu'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'bob.wilson@student.university.edu'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'carol.davis@student.university.edu'),
('student4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'david.miller@student.university.edu'),
('student5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'emma.garcia@student.university.edu'),
('student6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'frank.martinez@student.university.edu');

-- Insert sample departments
INSERT INTO departments (name, code, description) VALUES
('Computer Science and Engineering', 'CSE', 'Department of Computer Science and Engineering'),
('Electrical and Electronics Engineering', 'EEE', 'Department of Electrical and Electronics Engineering'),
('Business Administration', 'BBA', 'Department of Business Administration'),
('Mathematics', 'MATH', 'Department of Mathematics'),
('Physics', 'PHY', 'Department of Physics');

-- Insert sample programs
INSERT INTO programs (name, code, duration_years, department_id, total_credits) VALUES
('Bachelor of Science in Computer Science', 'BSCS', 4, 1, 130),
('Master of Science in Computer Science', 'MSCS', 2, 1, 60),
('Bachelor of Science in Electrical Engineering', 'BSEE', 4, 2, 140),
('Master of Science in Electrical Engineering', 'MSEE', 2, 2, 60),
('Bachelor of Business Administration', 'BBA', 4, 3, 120),
('Master of Business Administration', 'MBA', 2, 3, 60),
('Bachelor of Science in Mathematics', 'BSMATH', 4, 4, 120),
('Bachelor of Science in Physics', 'BSPHY', 4, 5, 120);

-- Insert sample teachers
INSERT INTO teachers (user_id, first_name, last_name, employee_id, department_id, designation, phone, hire_date, salary) VALUES
(2, 'John', 'Doe', 'T001', 1, 'Professor', '555-0101', '2015-01-15', 80000.00),
(3, 'Jane', 'Smith', 'T002', 1, 'Associate Professor', '555-0102', '2018-03-20', 70000.00),
(4, 'Mike', 'Johnson', 'T003', 2, 'Assistant Professor', '555-0103', '2020-08-10', 60000.00);

-- Update departments with head teachers
UPDATE departments SET head_id = 1 WHERE department_id = 1;
UPDATE departments SET head_id = 3 WHERE department_id = 2;

-- Insert sample students
INSERT INTO students (user_id, first_name, last_name, student_id_number, roll_number, department_id, program_id, admission_date, session, semester, phone, date_of_birth, gender, emergency_contact) VALUES
(5, 'Alice', 'Brown', 'S001', 'CSE2024001', 1, 1, '2024-01-15', '2024-25', 1, '555-0201', '2003-05-15', 'Female', '555-0202'),
(6, 'Bob', 'Wilson', 'S002', 'CSE2024002', 1, 1, '2024-01-15', '2024-25', 1, '555-0203', '2003-08-22', 'Male', '555-0204'),
(7, 'Carol', 'Davis', 'S003', 'EEE2024001', 2, 3, '2024-01-15', '2024-25', 1, '555-0205', '2003-03-10', 'Female', '555-0206'),
(8, 'David', 'Miller', 'S004', 'BBA2024001', 3, 5, '2024-01-15', '2024-25', 1, '555-0207', '2003-11-05', 'Male', '555-0208'),
(9, 'Emma', 'Garcia', 'S005', 'CSE2024003', 1, 1, '2024-01-15', '2024-25', 1, '555-0209', '2003-07-18', 'Female', '555-0210'),
(10, 'Frank', 'Martinez', 'S006', 'EEE2024002', 2, 3, '2024-01-15', '2024-25', 1, '555-0211', '2003-09-12', 'Male', '555-0212');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, credits, department_id, description) VALUES
('CSE101', 'Introduction to Programming', 3, 1, 'Basic programming concepts using Python'),
('CSE102', 'Data Structures and Algorithms', 3, 1, 'Fundamental data structures and algorithm design'),
('CSE201', 'Database Systems', 3, 1, 'Database design and SQL programming'),
('CSE202', 'Web Development', 3, 1, 'HTML, CSS, JavaScript, and PHP'),
('CSE301', 'Software Engineering', 3, 1, 'Software development methodologies'),
('EEE101', 'Circuit Analysis', 3, 2, 'Basic electrical circuit analysis'),
('EEE102', 'Digital Electronics', 3, 2, 'Digital logic and circuit design'),
('BBA101', 'Principles of Management', 3, 3, 'Fundamental management concepts'),
('BBA102', 'Business Communication', 3, 3, 'Effective business communication skills'),
('MATH101', 'Calculus I', 3, 4, 'Differential and integral calculus'),
('PHY101', 'General Physics', 3, 5, 'Mechanics and thermodynamics');

-- Insert sample course offerings
INSERT INTO course_offerings (course_id, teacher_id, semester, academic_year, max_students, schedule, classroom) VALUES
(1, 1, 1, '2024-25', 30, 'MWF 9:00-10:00', 'CSE-101'),
(2, 1, 1, '2024-25', 30, 'MWF 10:00-11:00', 'CSE-102'),
(3, 2, 1, '2024-25', 25, 'TTH 9:00-10:30', 'CSE-201'),
(4, 2, 1, '2024-25', 25, 'TTH 10:30-12:00', 'CSE-202'),
(6, 3, 1, '2024-25', 30, 'MWF 11:00-12:00', 'EEE-101'),
(7, 3, 1, '2024-25', 30, 'MWF 2:00-3:00', 'EEE-102'),
(8, 1, 1, '2024-25', 40, 'MWF 1:00-2:00', 'BBA-101'),
(9, 2, 1, '2024-25', 40, 'TTH 1:00-2:30', 'BBA-102');

-- Insert sample enrollments
INSERT INTO enrollments (student_id, offering_id, enrollment_date) VALUES
-- Alice Brown enrollments
(1, 1, '2024-01-20 10:00:00'),
(1, 2, '2024-01-20 10:00:00'),
(1, 3, '2024-01-20 10:00:00'),
(1, 4, '2024-01-20 10:00:00'),
-- Bob Wilson enrollments
(2, 1, '2024-01-20 10:30:00'),
(2, 2, '2024-01-20 10:30:00'),
(2, 3, '2024-01-20 10:30:00'),
(2, 4, '2024-01-20 10:30:00'),
-- Carol Davis enrollments
(3, 5, '2024-01-20 11:00:00'),
(3, 6, '2024-01-20 11:00:00'),
-- David Miller enrollments
(4, 7, '2024-01-20 11:30:00'),
(4, 8, '2024-01-20 11:30:00'),
-- Emma Garcia enrollments
(5, 1, '2024-01-20 12:00:00'),
(5, 2, '2024-01-20 12:00:00'),
(5, 3, '2024-01-20 12:00:00'),
-- Frank Martinez enrollments
(6, 5, '2024-01-20 12:30:00'),
(6, 6, '2024-01-20 12:30:00');

-- Insert sample grades
INSERT INTO grades (enrollment_id, grade_letter, grade_point, remarks, graded_by) VALUES
(1, 'A', 4.00, 'Excellent work', 1),
(2, 'A-', 3.70, 'Very good performance', 1),
(3, 'B+', 3.30, 'Good understanding', 2),
(4, 'A', 4.00, 'Outstanding project work', 2),
(5, 'B', 3.00, 'Satisfactory work', 1),
(6, 'B-', 2.70, 'Needs improvement', 1),
(7, 'C+', 2.30, 'Below average', 2),
(8, 'B+', 3.30, 'Good effort', 2),
(9, 'A-', 3.70, 'Strong performance', 3),
(10, 'B', 3.00, 'Adequate work', 3),
(11, 'A', 4.00, 'Excellent presentation', 1),
(12, 'B+', 3.30, 'Good communication', 2),
(13, 'A', 4.00, 'Perfect attendance and work', 1),
(14, 'A-', 3.70, 'Very good algorithms', 1),
(15, 'B+', 3.30, 'Good database design', 2),
(16, 'A-', 3.70, 'Strong circuit analysis', 3),
(17, 'B', 3.00, 'Satisfactory work', 3);

-- Insert sample attendance records
INSERT INTO attendance (enrollment_id, date, status, remarks, marked_by) VALUES
-- Alice Brown attendance
(1, '2024-01-22', 'present', '', 1),
(1, '2024-01-24', 'present', '', 1),
(1, '2024-01-26', 'present', '', 1),
(2, '2024-01-22', 'present', '', 1),
(2, '2024-01-24', 'present', '', 1),
(2, '2024-01-26', 'present', '', 1),
(3, '2024-01-23', 'present', '', 2),
(3, '2024-01-25', 'present', '', 2),
(4, '2024-01-23', 'present', '', 2),
(4, '2024-01-25', 'present', '', 2),
-- Bob Wilson attendance
(5, '2024-01-22', 'present', '', 1),
(5, '2024-01-24', 'late', 'Traffic delay', 1),
(5, '2024-01-26', 'present', '', 1),
(6, '2024-01-22', 'present', '', 1),
(6, '2024-01-24', 'present', '', 1),
(6, '2024-01-26', 'absent', 'Sick', 1),
(7, '2024-01-23', 'present', '', 2),
(7, '2024-01-25', 'present', '', 2),
(8, '2024-01-23', 'present', '', 2),
(8, '2024-01-25', 'present', '', 2),
-- Carol Davis attendance
(9, '2024-01-22', 'present', '', 3),
(9, '2024-01-24', 'present', '', 3),
(9, '2024-01-26', 'present', '', 3),
(10, '2024-01-22', 'present', '', 3),
(10, '2024-01-24', 'present', '', 3),
(10, '2024-01-26', 'present', '', 3),
-- David Miller attendance
(11, '2024-01-22', 'present', '', 1),
(11, '2024-01-24', 'present', '', 1),
(11, '2024-01-26', 'present', '', 1),
(12, '2024-01-23', 'present', '', 2),
(12, '2024-01-25', 'present', '', 2),
-- Emma Garcia attendance
(13, '2024-01-22', 'present', '', 1),
(13, '2024-01-24', 'present', '', 1),
(13, '2024-01-26', 'present', '', 1),
(14, '2024-01-22', 'present', '', 1),
(14, '2024-01-24', 'present', '', 1),
(14, '2024-01-26', 'present', '', 1),
(15, '2024-01-23', 'present', '', 2),
(15, '2024-01-25', 'present', '', 2),
-- Frank Martinez attendance
(16, '2024-01-22', 'present', '', 3),
(16, '2024-01-24', 'present', '', 3),
(16, '2024-01-26', 'present', '', 3),
(17, '2024-01-22', 'present', '', 3),
(17, '2024-01-24', 'present', '', 3),
(17, '2024-01-26', 'present', '', 3);

-- Insert sample fees
INSERT INTO fees (program_id, fee_type, amount, semester, academic_year, due_date, is_mandatory) VALUES
(1, 'Tuition Fee', 50000.00, 1, '2024-25', '2024-02-15', TRUE),
(1, 'Lab Fee', 5000.00, 1, '2024-25', '2024-02-15', TRUE),
(1, 'Library Fee', 2000.00, 1, '2024-25', '2024-02-15', TRUE),
(3, 'Tuition Fee', 45000.00, 1, '2024-25', '2024-02-15', TRUE),
(3, 'Lab Fee', 8000.00, 1, '2024-25', '2024-02-15', TRUE),
(3, 'Library Fee', 2000.00, 1, '2024-25', '2024-02-15', TRUE),
(5, 'Tuition Fee', 40000.00, 1, '2024-25', '2024-02-15', TRUE),
(5, 'Library Fee', 2000.00, 1, '2024-25', '2024-02-15', TRUE);

-- Insert sample payments
INSERT INTO payments (student_id, fee_id, amount, payment_date, payment_method, transaction_id, status, remarks) VALUES
(1, 1, 50000.00, '2024-02-10 14:30:00', 'bank_transfer', 'TXN001', 'completed', 'Tuition fee payment'),
(1, 2, 5000.00, '2024-02-10 14:30:00', 'bank_transfer', 'TXN002', 'completed', 'Lab fee payment'),
(1, 3, 2000.00, '2024-02-10 14:30:00', 'bank_transfer', 'TXN003', 'completed', 'Library fee payment'),
(2, 1, 50000.00, '2024-02-12 10:15:00', 'card', 'TXN004', 'completed', 'Tuition fee payment'),
(2, 2, 5000.00, '2024-02-12 10:15:00', 'card', 'TXN005', 'completed', 'Lab fee payment'),
(2, 3, 2000.00, '2024-02-12 10:15:00', 'card', 'TXN006', 'completed', 'Library fee payment'),
(3, 4, 45000.00, '2024-02-08 16:45:00', 'bank_transfer', 'TXN007', 'completed', 'Tuition fee payment'),
(3, 5, 8000.00, '2024-02-08 16:45:00', 'bank_transfer', 'TXN008', 'completed', 'Lab fee payment'),
(3, 6, 2000.00, '2024-02-08 16:45:00', 'bank_transfer', 'TXN009', 'completed', 'Library fee payment'),
(4, 7, 40000.00, '2024-02-15 11:20:00', 'cash', 'TXN010', 'completed', 'Tuition fee payment'),
(4, 8, 2000.00, '2024-02-15 11:20:00', 'cash', 'TXN011', 'completed', 'Library fee payment'),
(5, 1, 50000.00, '2024-02-14 09:30:00', 'bank_transfer', 'TXN012', 'completed', 'Tuition fee payment'),
(5, 2, 5000.00, '2024-02-14 09:30:00', 'bank_transfer', 'TXN013', 'completed', 'Lab fee payment'),
(5, 3, 2000.00, '2024-02-14 09:30:00', 'bank_transfer', 'TXN014', 'completed', 'Library fee payment'),
(6, 4, 45000.00, '2024-02-13 13:15:00', 'card', 'TXN015', 'completed', 'Tuition fee payment'),
(6, 5, 8000.00, '2024-02-13 13:15:00', 'card', 'TXN016', 'completed', 'Lab fee payment'),
(6, 6, 2000.00, '2024-02-13 13:15:00', 'card', 'TXN017', 'completed', 'Library fee payment');

-- Insert sample notices
INSERT INTO notices (title, description, posted_by, target_audience, priority, is_active, date_posted, expiry_date) VALUES
('Welcome to New Academic Year 2024-25', 'Welcome all students, teachers, and staff to the new academic year. Classes will begin from January 22, 2024.', 1, 'all', 'high', TRUE, '2024-01-15 10:00:00', '2024-02-28'),
('Mid-term Examination Schedule', 'Mid-term examinations will be conducted from March 15-25, 2024. Please check the detailed schedule on the notice board.', 1, 'students', 'high', TRUE, '2024-01-20 14:30:00', '2024-03-30'),
('Library Hours Extended', 'Library will remain open until 10 PM on weekdays and 6 PM on weekends starting from February 1, 2024.', 1, 'all', 'medium', TRUE, '2024-01-25 09:00:00', '2024-06-30'),
('Fee Payment Deadline', 'Last date for fee payment is February 15, 2024. Late fees will be applicable after the deadline.', 1, 'students', 'urgent', TRUE, '2024-01-30 11:00:00', '2024-02-20'),
('Faculty Meeting', 'All faculty members are requested to attend the monthly meeting on February 5, 2024, at 3 PM in the conference room.', 1, 'teachers', 'medium', TRUE, '2024-02-01 08:00:00', '2024-02-10');

-- Insert sample library books
INSERT INTO library_books (title, author, isbn, publisher, publication_year, department_id, category, total_copies, available_copies, shelf_location) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '978-0262033848', 'MIT Press', 2009, 1, 'Computer Science', 5, 4, 'CS-A1'),
('Database System Concepts', 'Abraham Silberschatz', '978-0073523323', 'McGraw-Hill', 2019, 1, 'Computer Science', 3, 2, 'CS-A2'),
('Clean Code', 'Robert C. Martin', '978-0132350884', 'Prentice Hall', 2008, 1, 'Software Engineering', 4, 3, 'CS-B1'),
('Fundamentals of Electric Circuits', 'Charles K. Alexander', '978-0073529554', 'McGraw-Hill', 2016, 2, 'Electrical Engineering', 3, 2, 'EE-A1'),
('Digital Design', 'M. Morris Mano', '978-0132774208', 'Prentice Hall', 2012, 2, 'Electrical Engineering', 4, 3, 'EE-A2'),
('Principles of Management', 'Peter Drucker', '978-0061252662', 'Harper Business', 2008, 3, 'Management', 6, 5, 'BBA-A1'),
('Calculus: Early Transcendentals', 'James Stewart', '978-1285741550', 'Cengage Learning', 2015, 4, 'Mathematics', 4, 3, 'MATH-A1'),
('University Physics', 'Hugh D. Young', '978-0133969290', 'Pearson', 2015, 5, 'Physics', 3, 2, 'PHY-A1');

-- Insert sample library issues
INSERT INTO library_issues (student_id, book_id, issue_date, due_date, return_date, fine_amount, status, remarks) VALUES
(1, 1, '2024-01-25', '2024-02-24', NULL, 0.00, 'issued', ''),
(1, 2, '2024-01-25', '2024-02-24', NULL, 0.00, 'issued', ''),
(2, 3, '2024-01-26', '2024-02-25', NULL, 0.00, 'issued', ''),
(3, 4, '2024-01-27', '2024-02-26', NULL, 0.00, 'issued', ''),
(4, 6, '2024-01-28', '2024-02-27', NULL, 0.00, 'issued', ''),
(5, 1, '2024-01-29', '2024-02-28', NULL, 0.00, 'issued', ''),
(6, 5, '2024-01-30', '2024-03-01', NULL, 0.00, 'issued', '');

-- Note: Password for all users is 'password' (hashed using PHP password_hash())
-- You can change passwords after logging in through the admin panel
