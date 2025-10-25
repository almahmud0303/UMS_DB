<?php
// logout.php - Logout functionality

require_once 'includes/auth.php';

$auth = new Auth();
$auth->logout();
?>
