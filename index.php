<?php
// index.php - Main Entry Point

require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Redirect to appropriate dashboard based on user role
if ($auth->isLoggedIn()) {
    $user = getCurrentUser();
    if ($user && isset($user['role'])) {
        switch ($user['role']) {
            case 'admin':
                redirect('admin/dashboard.php');
                break;
            case 'teacher':
                redirect('teacher/dashboard.php');
                break;
            case 'student':
                redirect('student/dashboard.php');
                break;
            default:
                redirect('login.php');
                break;
        }
    } else {
        redirect('login.php');
    }
} else {
    redirect('login.php');
}
?>
