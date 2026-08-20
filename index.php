<?php
/**
 * Home Page
 * Public-facing homepage for the Logistics Management System
 */

$page_title = 'Home';
include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Hero Section -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-truck"></i> Welcome to Logistics Manager</h1>
            <p class="lead">Streamline your logistics operations with our comprehensive management system</p>

            <div class="d-flex gap-3 flex-wrap mt-4">
                <a href="pages/register.php" class="btn btn-dark btn-lg">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <a href="pages/login.php" class="btn btn-outline-dark btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Why Choose Us -->
        <section class="mb-5">
            <h2 class="mb-4">Why Choose Logistics Manager?</h2>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x mb-3" style="color: #3498db;"></i>
                            <h5 class="card-title">Real-Time Tracking</h5>
                            <p class="card-text">Monitor your jobs and vehicles in real-time with our intuitive dashboard.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-3x mb-3" style="color: #27ae60;"></i>
                            <h5 class="card-title">Centralized Database</h5>
                            <p class="card-text">Store all your sites, vehicles, and jobs in one secure, centralized location.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3" style="color: #e74c3c;"></i>
                            <h5 class="card-title">Team Management</h5>
                            <p class="card-text">Manage staff roles and permissions with our flexible user management system.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-alt fa-3x mb-3" style="color: #f39c12;"></i>
                            <h5 class="card-title">Advanced Reports</h5>
                            <p class="card-text">Generate comprehensive reports and search for information across your operations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us Section -->
        <section id="about" class="mb-5">
            <h2 class="mb-4">About Us</h2>
            <div class="card">
                <div class="card-body">
                    <p class="card-text">
                        Logistics Manager is a logistics management system built to help organizations plan, track,
                        and manage operations efficiently. We simplify job workflows, fleet tracking, and site coordination
                        so teams can work faster and reduce mistakes.
                    </p>
                    <p class="card-text mb-0">
                        With a centralized database and strong reporting tools, our platform supports better decision-making
                        and smoother day-to-day logistics performance.
                    </p>
                </div>
            </div>
        </section>

        <!-- Vision & Mission Section -->
        <section id="vision-mission" class="mb-5">
            <h2 class="mb-4">Vision &amp; Mission</h2>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="mb-2"><i class="fas fa-eye text-info"></i> Vision</h5>
                            <p class="card-text mb-0">
                                To become the most trusted platform for efficient, transparent, and reliable logistics operations.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="mb-2"><i class="fas fa-flag-checkered text-warning"></i> Mission</h5>
                            <ul class="mb-0">
                                <li>Provide an easy-to-use system for managing logistics jobs and status tracking.</li>
                                <li>Improve fleet efficiency through organized vehicle and capacity management.</li>
                                <li>Reduce delays and mistakes by using clear workflows and centralized data.</li>
                                <li>Deliver strong reporting and analytics to support planning and decision-making.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Key Features -->
        <section class="mb-5">
            <h2 class="mb-4">Key Features</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-check-circle text-success"></i> Job Management</h5>
                            <p class="card-text">Create, track, and manage logistics jobs with ease. Monitor status from outstanding to completion.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Create new jobs</li>
                                <li><i class="fas fa-check text-success"></i> Track job status</li>
                                <li><i class="fas fa-check text-success"></i> Manage hazardous materials</li>
                                <li><i class="fas fa-check text-success"></i> Set deadlines and priorities</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-check-circle text-success"></i> Fleet Management</h5>
                            <p class="card-text">Manage your entire fleet of vehicles with detailed specifications and tracking.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Vehicle type management</li>
                                <li><i class="fas fa-check text-success"></i> Track vehicle locations</li>
                                <li><i class="fas fa-check text-success"></i> Weight and volume specifications</li>
                                <li><i class="fas fa-check text-success"></i> Maintenance notes</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-check-circle text-success"></i> Site Management</h5>
                            <p class="card-text">Maintain detailed information about all your distribution sites and hubs.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Site details and addresses</li>
                                <li><i class="fas fa-check text-success"></i> Vehicle allocation per site</li>
                                <li><i class="fas fa-check text-success"></i> Contact information</li>
                                <li><i class="fas fa-check text-success"></i> Site status management</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-check-circle text-success"></i> Reporting & Analytics</h5>
                            <p class="card-text">Get comprehensive insights into your logistics operations with powerful reporting tools.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Job completion reports</li>
                                <li><i class="fas fa-check text-success"></i> Outstanding job tracking</li>
                                <li><i class="fas fa-check text-success"></i> Site performance metrics</li>
                                <li><i class="fas fa-check text-success"></i> Advanced search functionality</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Call to Action -->
        <section class="mb-5">
            <div class="card" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: #fff; border: none;">
                <div class="card-body text-center py-5">
                    <h2 class="card-title mb-4">Ready to Get Started?</h2>
                    <p class="card-text lead mb-4">Join our logistics management system and streamline your operations today.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="pages/register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                        <a href="pages/login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
