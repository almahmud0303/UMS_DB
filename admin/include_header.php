<?php
// admin/include_header.php - Common Header for Admin Pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin - University Management System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="dashboard.php" class="text-xl font-bold text-gray-800">
                                <i class="fas fa-graduation-cap mr-2"></i>UMS
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:ml-10 sm:flex">
                            <a href="dashboard.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'dashboard') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'dashboard') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Dashboard
                            </a>
                            <a href="students.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'students') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'students') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Students
                            </a>
                            <a href="teachers.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'teachers') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'teachers') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Teachers
                            </a>
                            <a href="courses.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'courses') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'courses') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Courses
                            </a>
                            <a href="departments.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'departments') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'departments') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Departments
                            </a>
                            <a href="programs.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'programs') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'programs') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Programs
                            </a>
                            <a href="enrollments.php" class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo (isset($current_page) && $current_page === 'enrollments') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> text-sm font-medium leading-5 focus:outline-none <?php echo (isset($current_page) && $current_page === 'enrollments') ? 'focus:border-indigo-700' : 'focus:text-gray-700 focus:border-gray-300'; ?> transition">
                                Enrollments
                            </a>
                        </div>
                    </div>

                    <!-- Settings Dropdown -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                <div><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-20">
                                <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Log Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-6">

