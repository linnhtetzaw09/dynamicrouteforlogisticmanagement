<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ---- Base URL (project folder) ----
require_once __DIR__ . '/../config/db_config.php';

// helper
function url($path) {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

$is_logged_in = isset($_SESSION['employee_id']);
$user_role = $_SESSION['role'] ?? '';
$user_name = isset($_SESSION['first_name']) ? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '')) : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Logistics Management System' : 'Logistics Management System'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    
    <!-- Custom CSS -->
    <link href="/finalpj/css/styles.css" rel="stylesheet">
    <link href="/finalpj/css/nav.css" rel="stylesheet">

</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $is_logged_in
                ? ($user_role === 'Admin' ? url('admin/dashboard.php') : url('staff/dashboard.php'))
                : url('index.php'); ?>">
                <i class="fas fa-truck"></i> Logistics Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (!$is_logged_in): ?>
                        <!-- Navigation for non-logged-in users -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>" href="<?php echo url('index.php'); ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="<?php echo url('pages/about.php'); ?>">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'vision.php') ? 'active' : ''; ?>" href="<?php echo url('pages/vision.php'); ?>">Vision & Mission</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'login.php') ? 'active' : ''; ?>" href="<?php echo url('pages/login.php'); ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'register.php') ? 'active' : ''; ?>" href="<?php echo url('pages/register.php'); ?>">Register</a>
                        </li>
                    <?php else: ?>
                        <!-- Navigation for logged-in users -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $user_role === 'Admin' ? url('admin/dashboard.php') : url('staff/dashboard.php'); ?>">
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                id="managementDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="fas fa-cog"></i>
                                <?php echo $user_role === 'Admin' ? 'Management' : 'Operations'; ?>
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="managementDropdown">

                                <?php if ($user_role === 'Admin'): ?>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('admin/sites.php'); ?>">
                                            <i class="fas fa-map-marker-alt"></i> Sites
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('admin/vehicles.php'); ?>">
                                            <i class="fas fa-truck"></i> Vehicles
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('admin/jobs.php'); ?>">
                                            <i class="fas fa-briefcase"></i> Job Management
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('admin/employees.php'); ?>">
                                            <i class="fas fa-users"></i> Employees
                                        </a>
                                    </li>

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('admin/approvals.php'); ?>">
                                            <i class="fas fa-check-circle"></i> User Approvals
                                        </a>
                                    </li>

                                <?php elseif ($user_role === 'Staff'): ?>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo url('staff/jobs.php'); ?>">
                                            <i class="fas fa-briefcase"></i> My Assigned Jobs
                                        </a>
                                    </li>

                                <?php endif; ?>

                            </ul>
                        </li>

                    
                        <!-- Reports Navigation -->
                        <?php if ($user_role === 'Admin'): ?>
                        <li class="nav-item dropdown">
                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                id="reportsDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('admin/reports_jobs.php'); ?>">
                                        Job Reports
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="<?php echo url('admin/reports_sites.php'); ?>">
                                        Site Reports
                                    </a>
                                </li>
                            </ul>
                        </li>

                    <?php endif; ?>
                        
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo url('pages/profile.php'); ?>"><i class="fas fa-user"></i> My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo url('pages/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main id="main-content" class="main-content">
