<?php
/**
 * Staff Dashboard
 * Main dashboard for staff members
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Staff') {
    header('Location: ../pages/login.php');
    exit;
}

// Check if staff is approved
if (!isset($_SESSION['is_approved']) || $_SESSION['is_approved'] == 0) {
    // Get approval status from database
    $query = "SELECT is_approved FROM employee WHERE employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['employee_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();

    if ($employee['is_approved'] == 0) {
        // Redirect to pending approval page
        header('Location: pending_approval.php');
        exit;
    }
}

$page_title = 'Staff Dashboard';
include '../includes/header.php';

// Get statistics for staff's site
$employee_id = (int) $_SESSION['employee_id'];

$stmt = $conn->prepare("
    SELECT assigned_site_id
    FROM employee
    WHERE employee_id = ?
");

$stmt->bind_param("i", $employee_id);
$stmt->execute();

$employee_result = $stmt->get_result();
$employee = $employee_result->fetch_assoc();

$stmt->close();

if (!$employee) {
    die("Employee record was not found.");
}

if (
    !isset($employee['assigned_site_id']) ||
    $employee['assigned_site_id'] === null ||
    $employee['assigned_site_id'] === ''
) {
    die("This staff member has no assigned site. Please assign a site from the employee table.");
}

$site_id = (int) $employee['assigned_site_id'];

// Jobs assigned to staff's site
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE origin_site_id = $site_id");
$stats['site_jobs'] = $result->fetch_assoc()['count'];

// Outstanding jobs
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE origin_site_id = $site_id AND status = 'Outstanding'");
$stats['outstanding_jobs'] = $result->fetch_assoc()['count'];

// In progress jobs
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE origin_site_id = $site_id AND status = 'In Progress'");
$stats['in_progress_jobs'] = $result->fetch_assoc()['count'];

// Completed jobs
$result = $conn->query("SELECT COUNT(*) as count FROM job WHERE origin_site_id = $site_id AND status = 'Completed'");
$stats['completed_jobs'] = $result->fetch_assoc()['count'];

// Vehicles at staff's site
$result = $conn->query("SELECT COUNT(*) as count FROM vehicle WHERE home_site_id = $site_id");
$stats['site_vehicles'] = $result->fetch_assoc()['count'];

// Get staff's site name
$result = $conn->query("SELECT site_name FROM site WHERE site_id = $site_id");
$site = $result->fetch_assoc();

// Get recent jobs for staff's site
$recent_jobs = $conn->query("
    SELECT j.job_id, j.goods_name, j.status, j.deadline, s1.site_name as origin, s2.site_name as destination
    FROM job j
    JOIN site s1 ON j.origin_site_id = s1.site_id
    JOIN site s2 ON j.destination_site_id = s2.site_id
    WHERE j.origin_site_id = $site_id
    ORDER BY j.created_at DESC
    LIMIT 5
");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Staff Dashboard</h1>
            <p class="lead">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! You are assigned to <strong><?php echo htmlspecialchars($site['site_name']); ?></strong></p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Statistics Row -->
        <div class="row mb-5">
            <!-- Site Jobs -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Total Jobs</h6>
                                <h2 class="text-primary"><?php echo $stats['site_jobs']; ?></h2>
                            </div>
                            <i class="fas fa-briefcase fa-3x" style="color: #3498db; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Site Vehicles -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Available Vehicles</h6>
                                <h2 class="text-success"><?php echo $stats['site_vehicles']; ?></h2>
                            </div>
                            <i class="fas fa-truck fa-3x" style="color: #27ae60; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Jobs -->
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-muted">Outstanding</h6>
                                <h2 class="text-warning"><?php echo $stats['outstanding_jobs']; ?></h2>
                            </div>
                            <i class="fas fa-hourglass-half fa-3x" style="color: #f39c12; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Completed Jobs Card -->
        <div class="row mb-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-12">
                                <h3 class="text-success"><?php echo $stats['completed_jobs']; ?></h3>
                                <p class="text-muted mb-0">Jobs Completed</p>
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
                        <h5 class="mb-0"><i class="fas fa-list"></i> Recent Jobs at Your Site</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Job ID</th>
                                    <th>Goods Name</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($recent_jobs->num_rows > 0) {
                                    while ($job = $recent_jobs->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $job['job_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($job['goods_name']); ?></td>
                                        <td><?php echo htmlspecialchars($job['destination']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                                <?php echo $job['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($job['deadline'])); ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                } else {
                                    echo '<tr><td colspan="6" class="text-center text-muted">No jobs found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
    </div>
</div>

<?php include '../includes/footer.php'; ?>
