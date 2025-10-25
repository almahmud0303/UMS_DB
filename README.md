# University Management System (UMS)

A comprehensive web-based University Management System built with PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap. This system demonstrates various SQL concepts and provides a complete solution for managing university operations.

## 🚀 Features

### **Admin Panel**
- **Dashboard**: Overview of system statistics and recent activities
- **Student Management**: Complete CRUD operations for student records
- **Teacher Management**: Full teacher profile and assignment management
- **Course Management**: Create, edit, and manage courses
- **Department Management**: Organize departments and assign heads
- **Program Management**: Manage degree programs and requirements
- **Enrollment Management**: View and manage student enrollments
- **Grade Management**: Monitor all grades across the system
- **Attendance Management**: Track attendance records
- **Payment Management**: Monitor fee payments and transactions
- **Notice Management**: Post and manage announcements
- **Library Management**: Track book issues and returns
- **Reports & Analytics**: Comprehensive reports with charts and statistics

### **Teacher Panel**
- **Dashboard**: Overview of assigned courses and students
- **My Courses**: View assigned courses and student lists
- **My Students**: Manage students in assigned courses
- **Grade Management**: Submit and manage student grades
- **Attendance**: Mark and track student attendance
- **Profile**: Manage personal information

### **Student Panel**
- **Dashboard**: Personal overview and quick access
- **My Courses**: View enrolled courses and details
- **Grades**: View grades and calculate GPA
- **Attendance**: Track personal attendance records
- **Payments**: View payment history and fee structure
- **Profile**: Manage personal information

## 🛠️ Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.0
- **Charts**: Chart.js
- **Data Tables**: DataTables
- **Server**: Apache (XAMPP recommended)

## 📋 SQL Concepts Demonstrated

This project showcases comprehensive SQL concepts including:

### **Basic Operations**
- `CREATE`, `ALTER`, `DROP` - Database and table management
- `INSERT`, `UPDATE`, `DELETE` - Data manipulation
- `SELECT`, `WHERE`, `ORDER BY`, `GROUP BY` - Data retrieval and filtering

### **Advanced Operations**
- **JOINs**: INNER, LEFT, RIGHT joins for relational data
- **Subqueries**: Complex nested queries for data analysis
- **Views**: Predefined query results for common operations
- **Indexes**: Performance optimization for large datasets
- **Aggregate Functions**: COUNT, SUM, AVG, MIN, MAX for statistics

### **Advanced Features**
- **Triggers**: Automatic GPA calculation and data validation
- **Stored Procedures**: Complex business logic implementation
- **Functions**: Custom calculations and data processing
- **Transactions**: Data consistency and rollback capabilities
- **Constraints**: Data integrity and foreign key relationships
- **User Privileges**: Role-based access control

## 🗄️ Database Schema

The system uses a comprehensive database schema with the following main tables:

- **users**: Authentication and user management
- **students**: Student profiles and academic information
- **teachers**: Teacher profiles and assignments
- **departments**: Department organization
- **programs**: Degree programs and requirements
- **courses**: Course catalog and details
- **course_offerings**: Semester-wise course offerings
- **enrollments**: Student course enrollments
- **grades**: Grade records and calculations
- **attendance**: Attendance tracking
- **fees**: Fee structure and requirements
- **payments**: Payment records and transactions
- **notices**: Announcements and communications
- **library_books**: Book catalog
- **library_issues**: Book borrowing records

## 🚀 Installation & Setup

### **Prerequisites**
- XAMPP (Apache + MySQL + PHP)
- Web browser (Chrome, Firefox, Safari, Edge)

### **Installation Steps**

1. **Clone/Download the project**
   ```bash
   # Place the project in XAMPP htdocs directory
   C:\xampp\htdocs\myapp4\
   ```

2. **Start XAMPP Services**
   - Start Apache and MySQL from XAMPP Control Panel

3. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import `database_schema.sql` to create the database structure
   - Import `sample_data.sql` to populate with sample data
   - Import `sql_features.sql` for advanced SQL features

4. **Configure Database Connection**
   - Update `config/database.php` if needed (default settings should work)

5. **Access the Application**
   - Open browser and navigate to: `http://localhost/myapp4/`

## 👥 Demo Credentials

### **Admin Access**
- **Username**: `admin`
- **Password**: `password`

### **Teacher Access**
- **Username**: `teacher1`
- **Password**: `password`

### **Student Access**
- **Username**: `student1`
- **Password**: `password`

## 📁 Project Structure

```
myapp4/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── auth.php             # Authentication system
│   └── functions.php        # Common utility functions
├── admin/
│   ├── dashboard.php        # Admin dashboard
│   ├── students.php         # Student management
│   ├── teachers.php         # Teacher management
│   ├── courses.php          # Course management
│   ├── departments.php      # Department management
│   ├── programs.php         # Program management
│   ├── enrollments.php      # Enrollment management
│   ├── grades.php           # Grade management
│   ├── attendance.php       # Attendance management
│   ├── payments.php         # Payment management
│   ├── notices.php          # Notice management
│   ├── library.php          # Library management
│   └── reports.php          # Reports and analytics
├── teacher/
│   ├── dashboard.php        # Teacher dashboard
│   ├── courses.php          # Teacher courses
│   ├── students.php         # Teacher students
│   ├── grades.php           # Grade management
│   └── attendance.php       # Attendance management
├── student/
│   ├── dashboard.php        # Student dashboard
│   ├── courses.php          # Student courses
│   ├── grades.php           # Student grades
│   ├── attendance.php       # Student attendance
│   └── payments.php         # Student payments
├── database_schema.sql      # Database structure
├── sample_data.sql          # Sample data
├── sql_features.sql        # Advanced SQL features
├── login.php               # Login page
├── logout.php              # Logout handler
├── index.php               # Main entry point
├── unauthorized.php        # Access denied page
└── README.md               # This file
```

## 🔧 Key Features Implementation

### **Authentication System**
- Role-based access control (Admin, Teacher, Student)
- Secure password hashing with PHP's `password_hash()`
- Session management and security
- Automatic redirection based on user roles

### **Database Design**
- Normalized database structure
- Foreign key constraints for data integrity
- Indexes for performance optimization
- Triggers for automatic calculations

### **User Interface**
- Responsive design with Bootstrap 5
- Modern gradient design and animations
- DataTables for efficient data display
- Chart.js for data visualization
- Font Awesome icons for better UX

### **Security Features**
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection in forms
- Role-based access control
- Secure session management

## 📊 Advanced SQL Features

### **Triggers**
- Automatic GPA calculation when grades are updated
- Data validation triggers
- Audit trail triggers

### **Stored Procedures**
- Student enrollment procedures
- Grade calculation procedures
- Attendance percentage calculations

### **Views**
- Student academic summary view
- Course enrollment summary view
- Teacher course assignment view

### **Functions**
- GPA calculation function
- Attendance percentage function
- Grade point conversion function

## 🎯 Learning Outcomes

This project demonstrates:

1. **Database Design**: Proper normalization and relationship design
2. **SQL Mastery**: All major SQL concepts and operations
3. **Web Development**: Full-stack development with PHP and MySQL
4. **Security**: Best practices for web application security
5. **UI/UX Design**: Modern, responsive user interface design
6. **System Architecture**: Role-based access control and modular design

## 🔮 Future Enhancements

- **Email Notifications**: Automated email alerts for grades, attendance
- **Mobile App**: React Native or Flutter mobile application
- **API Development**: RESTful API for third-party integrations
- **Advanced Analytics**: Machine learning for student performance prediction
- **Document Management**: File upload and management system
- **Online Examination**: Built-in examination system
- **Parent Portal**: Parent access to student information

## 📝 License

This project is created for educational purposes and demonstrates various web development and database concepts.

## 🤝 Contributing

This is an educational project. Feel free to fork, modify, and enhance for learning purposes.

## 📞 Support

For questions or issues related to this project, please refer to the code comments and documentation within the files.

---

**Built with ❤️ for educational purposes**