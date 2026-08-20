<?php
/**
 * Admin Dashboard
 * Main dashboard for administrators
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Admin Dashboard';
include '../includes/header.php';

// Get statistics
$stats = [];

// Total sites
$result = $conn->query("SELECT COUNT(*) as count FROM site WHERE is_active = 1");
$stats['active_sites'] = $result->fetch_assoc()['count'];

// Total vehicles
$result = $conn->query("SELECT COUNT(*) as count FROM vehicle");
$stats['total_vehicles'] = $result->fetch_assoc()['count'];

// Total employees
$result = $conn->query("SELECT COUNT(*) as count FROM employee WHERE role = 'Staff' AND is_approved = 1");
$stats['approved_staff'] = $result->fetch_assoc()['count'];

// Pending approvals
$result = $conn->query("SELECT COUNT(*) as count FROM employee WHERE is_approved = 0");
$stats['pending_approvals'] = $result->fetch_assoc()['count'];

// Outstanding jobs
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE status = 'Outstanding'");
$stats['outstanding_jobs'] = $result->fetch_assoc()['count'];

// In progress jobs
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE status = 'In Progress'");
$stats['in_progress_jobs'] = $result->fetch_assoc()['count'];

// Completed jobs
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE status = 'Completed'");
$stats['completed_jobs'] = $result->fetch_assoc()['count'];

// Get recent jobs
$recent_jobs = $conn->query("
    SELECT j.job_id, j.goods_name, j.status, j.deadline, s1.site_name as origin, s2.site_name as destination
    FROM job j
    JOIN site s1 ON j.origin_site_id = s1.site_id
    JOIN site s2 ON j.destination_site_id = s2.site_id
    ORDER BY j.created_at DESC
    LIMIT 5
");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <p class="lead">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! Here's an overview of your logistics operations.</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Statistics Row -->
        <div class="row mb-5">
            <!-- Active Sites -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Active Sites</h6>
                                <h2 class="text-primary"><?php echo $stats['active_sites']; ?></h2>
                            </div>
                            <i class="fas fa-map-marker-alt fa-3x" style="color: #3498db; opacity: 0.3;"></i>
                        </div>
                        <a href="sites.php" class="btn btn-sm btn-outline-primary mt-3">View Sites</a>
                    </div>
                </div>
            </div>

            <!-- Total Vehicles -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Total Vehicles</h6>
                                <h2 class="text-success"><?php echo $stats['total_vehicles']; ?></h2>
                            </div>
                            <i class="fas fa-truck fa-3x" style="color: #27ae60; opacity: 0.3;"></i>
                        </div>
                        <a href="vehicles.php" class="btn btn-sm btn-outline-success mt-3">Manage Vehicles</a>
                    </div>
                </div>
            </div>

            <!-- Approved Staff -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Approved Staff</h6>
                                <h2 class="text-info"><?php echo $stats['approved_staff']; ?></h2>
                            </div>
                            <i class="fas fa-users fa-3x" style="color: #3498db; opacity: 0.3;"></i>
                        </div>
                        <a href="employees.php" class="btn btn-sm btn-outline-info mt-3">Manage Staff</a>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Pending Approvals</h6>
                                <h2 class="text-warning"><?php echo $stats['pending_approvals']; ?></h2>
                            </div>
                            <i class="fas fa-hourglass-half fa-3x" style="color: #f39c12; opacity: 0.3;"></i>
                        </div>
                        <a href="approvals.php" class="btn btn-sm btn-outline-warning mt-3">Review</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="jobs.php?action=create" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Create New Job
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="sites.php?action=create" class="btn btn-success w-100">
                                    <i class="fas fa-plus"></i> Add New Site
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="vehicles.php?action=create" class="btn btn-info w-100">
                                    <i class="fas fa-plus"></i> Register Vehicle
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="approvals.php" class="btn btn-warning w-100">
                                    <i class="fas fa-check-circle"></i> Review Approvals
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Statistics -->
        <div class="row mb-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-briefcase"></i> Job Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="text-warning"><?php echo $stats['outstanding_jobs']; ?></h3>
                                    <p class="text-muted mb-0">Outstanding Jobs</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="text-success"><?php echo $stats['completed_jobs']; ?></h3>
                                    <p class="text-muted mb-0">Completed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Jobs -->
        <div class="row mb-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Recent Jobs</h5>
                        <a href="jobs.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Job ID</th>
                                    <th>Goods Name</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $job['job_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($job['goods_name']); ?></td>
                                        <td><?php echo htmlspecialchars($job['origin']); ?></td>
                                        <td><?php echo htmlspecialchars($job['destination']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                                <?php echo $job['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($job['deadline'])); ?></td>
                                        <td>
                                            <a href="jobs.php?action=edit&id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
