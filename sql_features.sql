-- Advanced SQL Features for University Management System
-- Database: umsdb

USE umsdb;

-- ==============================================
-- STORED PROCEDURES
-- ==============================================

-- Procedure to enroll a student in a course
DELIMITER //
CREATE PROCEDURE EnrollStudent(
    IN p_student_id INT,
    IN p_offering_id INT,
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_max_students INT DEFAULT 0;
    DECLARE v_current_enrolled INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 'Error: Failed to enroll student';
    END;
    
    START TRANSACTION;
    
    -- Check if student is already enrolled
    SELECT COUNT(*) INTO v_count 
    FROM enrollments 
    WHERE student_id = p_student_id AND offering_id = p_offering_id;
    
    IF v_count > 0 THEN
        SET p_result = 'Student is already enrolled in this course';
        ROLLBACK;
    ELSE
        -- Check course capacity
        SELECT max_students INTO v_max_students 
        FROM course_offerings 
        WHERE offering_id = p_offering_id;
        
        SELECT COUNT(*) INTO v_current_enrolled 
        FROM enrollments 
        WHERE offering_id = p_offering_id AND status = 'enrolled';
        
        IF v_current_enrolled >= v_max_students THEN
            SET p_result = 'Course is full. Cannot enroll more students';
            ROLLBACK;
        ELSE
            -- Enroll the student
            INSERT INTO enrollments (student_id, offering_id) 
            VALUES (p_student_id, p_offering_id);
            
            SET p_result = 'Student enrolled successfully';
            COMMIT;
        END IF;
    END IF;
END //
DELIMITER ;

-- Procedure to calculate student GPA
DELIMITER //
CREATE PROCEDURE CalculateStudentGPA(
    IN p_student_id INT,
    OUT p_gpa DECIMAL(3,2)
)
BEGIN
    DECLARE v_total_points DECIMAL(8,2) DEFAULT 0;
    DECLARE v_total_credits INT DEFAULT 0;
    DECLARE v_course_credits INT DEFAULT 0;
    DECLARE v_grade_point DECIMAL(3,2) DEFAULT 0;
    DECLARE done INT DEFAULT FALSE;
    
    DECLARE grade_cursor CURSOR FOR
        SELECT c.credits, g.grade_point
        FROM enrollments e
        JOIN course_offerings co ON e.offering_id = co.offering_id
        JOIN courses c ON co.course_id = c.course_id
        JOIN grades g ON e.enrollment_id = g.enrollment_id
        WHERE e.student_id = p_student_id AND e.status = 'completed';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN grade_cursor;
    
    read_loop: LOOP
        FETCH grade_cursor INTO v_course_credits, v_grade_point;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SET v_total_points = v_total_points + (v_course_credits * v_grade_point);
        SET v_total_credits = v_total_credits + v_course_credits;
    END LOOP;
    
    CLOSE grade_cursor;
    
    IF v_total_credits > 0 THEN
        SET p_gpa = v_total_points / v_total_credits;
    ELSE
        SET p_gpa = 0.00;
    END IF;
END //
DELIMITER ;

-- Procedure to generate student transcript
DELIMITER //
CREATE PROCEDURE GenerateTranscript(
    IN p_student_id INT,
    IN p_semester INT,
    IN p_academic_year VARCHAR(20)
)
BEGIN
    SELECT 
        s.first_name,
        s.last_name,
        s.student_id_number,
        s.roll_number,
        d.name as department_name,
        p.name as program_name,
        c.course_code,
        c.course_name,
        c.credits,
        g.grade_letter,
        g.grade_point,
        co.semester,
        co.academic_year,
        t.first_name as teacher_first_name,
        t.last_name as teacher_last_name
    FROM students s
    JOIN departments d ON s.department_id = d.department_id
    JOIN programs p ON s.program_id = p.program_id
    JOIN enrollments e ON s.student_id = e.student_id
    JOIN course_offerings co ON e.offering_id = co.offering_id
    JOIN courses c ON co.course_id = c.course_id
    JOIN teachers t ON co.teacher_id = t.teacher_id
    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
    WHERE s.student_id = p_student_id
    AND (p_semester IS NULL OR co.semester = p_semester)
    AND (p_academic_year IS NULL OR co.academic_year = p_academic_year)
    ORDER BY co.academic_year DESC, co.semester DESC, c.course_code;
END //
DELIMITER ;

-- ==============================================
-- TRIGGERS
-- ==============================================

-- Trigger to automatically update available copies when book is issued
DELIMITER //
CREATE TRIGGER update_book_copies_on_issue
AFTER INSERT ON library_issues
FOR EACH ROW
BEGIN
    UPDATE library_books 
    SET available_copies = available_copies - 1 
    WHERE book_id = NEW.book_id;
END //
DELIMITER ;

-- Trigger to automatically update available copies when book is returned
DELIMITER //
CREATE TRIGGER update_book_copies_on_return
AFTER UPDATE ON library_issues
FOR EACH ROW
BEGIN
    IF OLD.status = 'issued' AND NEW.status = 'returned' THEN
        UPDATE library_books 
        SET available_copies = available_copies + 1 
        WHERE book_id = NEW.book_id;
    END IF;
END //
DELIMITER ;

-- Trigger to calculate fine for overdue books
DELIMITER //
CREATE TRIGGER calculate_overdue_fine
BEFORE UPDATE ON library_issues
FOR EACH ROW
BEGIN
    DECLARE v_days_overdue INT DEFAULT 0;
    DECLARE v_fine_per_day DECIMAL(5,2) DEFAULT 5.00;
    
    IF NEW.status = 'returned' AND OLD.status = 'issued' THEN
        SET v_days_overdue = DATEDIFF(NEW.return_date, NEW.due_date);
        
        IF v_days_overdue > 0 THEN
            SET NEW.fine_amount = v_days_overdue * v_fine_per_day;
        ELSE
            SET NEW.fine_amount = 0.00;
        END IF;
    END IF;
END //
DELIMITER ;

-- Trigger to prevent duplicate enrollments
DELIMITER //
CREATE TRIGGER prevent_duplicate_enrollment
BEFORE INSERT ON enrollments
FOR EACH ROW
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_count
    FROM enrollments
    WHERE student_id = NEW.student_id 
    AND offering_id = NEW.offering_id;
    
    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Student is already enrolled in this course';
    END IF;
END //
DELIMITER ;

-- ==============================================
-- VIEWS
-- ==============================================

-- View for student academic summary
CREATE VIEW student_academic_summary AS
SELECT 
    s.student_id,
    s.first_name,
    s.last_name,
    s.student_id_number,
    s.roll_number,
    d.name as department_name,
    p.name as program_name,
    s.semester,
    s.session,
    COUNT(e.enrollment_id) as total_courses_enrolled,
    COUNT(g.grade_id) as courses_completed,
    AVG(g.grade_point) as current_gpa,
    SUM(c.credits) as total_credits_earned
FROM students s
JOIN departments d ON s.department_id = d.department_id
JOIN programs p ON s.program_id = p.program_id
LEFT JOIN enrollments e ON s.student_id = e.student_id
LEFT JOIN course_offerings co ON e.offering_id = co.offering_id
LEFT JOIN courses c ON co.course_id = c.course_id
LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
GROUP BY s.student_id;

-- View for teacher course assignments
CREATE VIEW teacher_course_assignments AS
SELECT 
    t.teacher_id,
    t.first_name,
    t.last_name,
    t.employee_id,
    d.name as department_name,
    co.offering_id,
    c.course_code,
    c.course_name,
    c.credits,
    co.semester,
    co.academic_year,
    co.schedule,
    co.classroom,
    COUNT(e.enrollment_id) as enrolled_students,
    co.max_students
FROM teachers t
JOIN departments d ON t.department_id = d.department_id
JOIN course_offerings co ON t.teacher_id = co.teacher_id
JOIN courses c ON co.course_id = c.course_id
LEFT JOIN enrollments e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
GROUP BY co.offering_id;

-- View for attendance summary
CREATE VIEW attendance_summary AS
SELECT 
    s.student_id,
    s.first_name,
    s.last_name,
    s.roll_number,
    c.course_code,
    c.course_name,
    COUNT(a.attendance_id) as total_classes,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.attendance_id)) * 100, 2) as attendance_percentage
FROM students s
JOIN enrollments e ON s.student_id = e.student_id
JOIN course_offerings co ON e.offering_id = co.offering_id
JOIN courses c ON co.course_id = c.course_id
LEFT JOIN attendance a ON e.enrollment_id = a.enrollment_id
GROUP BY s.student_id, c.course_id;

-- View for payment summary
CREATE VIEW payment_summary AS
SELECT 
    s.student_id,
    s.first_name,
    s.last_name,
    s.roll_number,
    p.name as program_name,
    f.fee_type,
    f.amount as fee_amount,
    f.due_date,
    SUM(pay.amount) as paid_amount,
    (f.amount - COALESCE(SUM(pay.amount), 0)) as remaining_amount,
    CASE 
        WHEN COALESCE(SUM(pay.amount), 0) >= f.amount THEN 'Paid'
        WHEN COALESCE(SUM(pay.amount), 0) > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END as payment_status
FROM students s
JOIN programs p ON s.program_id = p.program_id
JOIN fees f ON p.program_id = f.program_id
LEFT JOIN payments pay ON s.student_id = pay.student_id AND f.fee_id = pay.fee_id AND pay.status = 'completed'
GROUP BY s.student_id, f.fee_id;

-- View for department statistics
CREATE VIEW department_statistics AS
SELECT 
    d.department_id,
    d.name as department_name,
    d.code as department_code,
    COUNT(DISTINCT t.teacher_id) as total_teachers,
    COUNT(DISTINCT s.student_id) as total_students,
    COUNT(DISTINCT c.course_id) as total_courses,
    COUNT(DISTINCT p.program_id) as total_programs,
    AVG(g.grade_point) as average_gpa
FROM departments d
LEFT JOIN teachers t ON d.department_id = t.department_id
LEFT JOIN students s ON d.department_id = s.department_id
LEFT JOIN courses c ON d.department_id = c.department_id
LEFT JOIN programs p ON d.department_id = p.department_id
LEFT JOIN enrollments e ON s.student_id = e.student_id
LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
GROUP BY d.department_id;

-- ==============================================
-- FUNCTIONS
-- ==============================================

-- Function to calculate GPA for a student
DELIMITER //
CREATE FUNCTION CalculateGPA(p_student_id INT) 
RETURNS DECIMAL(3,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_gpa DECIMAL(3,2) DEFAULT 0.00;
    
    SELECT COALESCE(AVG(g.grade_point), 0.00) INTO v_gpa
    FROM enrollments e
    JOIN grades g ON e.enrollment_id = g.enrollment_id
    WHERE e.student_id = p_student_id AND e.status = 'completed';
    
    RETURN v_gpa;
END //
DELIMITER ;

-- Function to get student attendance percentage
DELIMITER //
CREATE FUNCTION GetAttendancePercentage(p_student_id INT, p_course_id INT) 
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_percentage DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_total_classes INT DEFAULT 0;
    DECLARE v_present_classes INT DEFAULT 0;
    
    SELECT 
        COUNT(*),
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END)
    INTO v_total_classes, v_present_classes
    FROM enrollments e
    JOIN course_offerings co ON e.offering_id = co.offering_id
    JOIN attendance a ON e.enrollment_id = a.enrollment_id
    WHERE e.student_id = p_student_id 
    AND co.course_id = p_course_id;
    
    IF v_total_classes > 0 THEN
        SET v_percentage = (v_present_classes / v_total_classes) * 100;
    END IF;
    
    RETURN v_percentage;
END //
DELIMITER ;

-- Function to check if student can enroll in course (prerequisites check)
DELIMITER //
CREATE FUNCTION CanEnrollInCourse(p_student_id INT, p_course_id INT) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_can_enroll BOOLEAN DEFAULT TRUE;
    DECLARE v_prerequisite_count INT DEFAULT 0;
    DECLARE v_completed_count INT DEFAULT 0;
    
    -- Check if course has prerequisites
    SELECT COUNT(*) INTO v_prerequisite_count
    FROM courses c1
    WHERE c1.course_id = p_course_id 
    AND c1.prerequisites IS NOT NULL 
    AND c1.prerequisites != '';
    
    IF v_prerequisite_count > 0 THEN
        -- Check if student has completed prerequisites
        SELECT COUNT(*) INTO v_completed_count
        FROM enrollments e
        JOIN course_offerings co ON e.offering_id = co.offering_id
        JOIN courses c ON co.course_id = c.course_id
        JOIN grades g ON e.enrollment_id = g.enrollment_id
        WHERE e.student_id = p_student_id 
        AND e.status = 'completed'
        AND g.grade_letter IN ('A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D')
        AND FIND_IN_SET(c.course_code, (SELECT prerequisites FROM courses WHERE course_id = p_course_id)) > 0;
        
        IF v_completed_count < v_prerequisite_count THEN
            SET v_can_enroll = FALSE;
        END IF;
    END IF;
    
    RETURN v_can_enroll;
END //
DELIMITER ;

-- ==============================================
-- INDEXES FOR PERFORMANCE
-- ==============================================

-- Additional indexes for better performance
CREATE INDEX idx_grades_student_course ON grades(enrollment_id);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_payments_date ON payments(payment_date);
CREATE INDEX idx_notices_active ON notices(is_active, date_posted);
CREATE INDEX idx_library_books_category ON library_books(category);
CREATE INDEX idx_library_issues_status ON library_issues(status);

-- ==============================================
-- SAMPLE QUERIES DEMONSTRATING SQL CONCEPTS
-- ==============================================

-- Example: Students with highest GPA in each department (Subquery)
-- SELECT s.first_name, s.last_name, d.name as department, CalculateGPA(s.student_id) as gpa
-- FROM students s
-- JOIN departments d ON s.department_id = d.department_id
-- WHERE CalculateGPA(s.student_id) = (
--     SELECT MAX(CalculateGPA(s2.student_id))
--     FROM students s2
--     WHERE s2.department_id = s.department_id
-- );

-- Example: Course enrollment statistics (Aggregate functions)
-- SELECT 
--     c.course_code,
--     c.course_name,
--     COUNT(e.enrollment_id) as enrolled_students,
--     AVG(g.grade_point) as average_grade,
--     MAX(g.grade_point) as highest_grade,
--     MIN(g.grade_point) as lowest_grade
-- FROM courses c
-- JOIN course_offerings co ON c.course_id = co.course_id
-- LEFT JOIN enrollments e ON co.offering_id = e.offering_id
-- LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
-- GROUP BY c.course_id
-- HAVING COUNT(e.enrollment_id) > 0
-- ORDER BY enrolled_students DESC;

-- Example: Complex join to get student details with grades and attendance
-- SELECT 
--     s.first_name,
--     s.last_name,
--     s.roll_number,
--     c.course_code,
--     c.course_name,
--     g.grade_letter,
--     g.grade_point,
--     GetAttendancePercentage(s.student_id, c.course_id) as attendance_percentage
-- FROM students s
-- JOIN enrollments e ON s.student_id = e.student_id
-- JOIN course_offerings co ON e.offering_id = co.offering_id
-- JOIN courses c ON co.course_id = c.course_id
-- LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
-- WHERE s.student_id = 1
-- ORDER BY c.course_code;
