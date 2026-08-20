<?php
/**
 * Staff Sites View Page
 * Staff can view site information (read-only)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Staff') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Sites';
include '../includes/header.php';

// Get all active sites
$sites = $conn->query("SELECT * FROM site WHERE is_active = 1 ORDER BY site_name ASC");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-map-marker-alt"></i> Sites</h1>
            <p class="lead">View information about all distribution sites</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <?php while ($site = $sites->fetch_assoc()): 
                $vehicle_count = $conn->query("SELECT COUNT(*) as count FROM vehicle WHERE home_site_id = " . $site['site_id'])->fetch_assoc()['count'];
                $job_count = $conn->query("SELECT COUNT(*) as count FROM job WHERE origin_site_id = " . $site['site_id'])->fetch_assoc()['count'];
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($site['site_name']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Address:</strong><br>
                                <?php echo htmlspecialchars($site['address_line_1']); ?><br>
                                <?php echo htmlspecialchars($site['address_city'] . ', ' . $site['address_postcode']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Contact Phone:</strong><br>
                                <?php echo htmlspecialchars($site['contact_phone'] ?? 'Not provided'); ?>
                            </p>
                            <hr>
                            <p class="mb-2">
                                <i class="fas fa-truck text-info"></i> <strong>Vehicles:</strong> <?php echo $vehicle_count; ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-briefcase text-warning"></i> <strong>Jobs:</strong> <?php echo $job_count; ?>
                            </p>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">
                                Status: <span class="badge bg-<?php echo $site['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $site['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
