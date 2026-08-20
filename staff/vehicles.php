<?php
/**
 * Staff Vehicles View Page
 * Staff can view vehicle information (read-only)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Staff') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Vehicles';
include '../includes/header.php';

// Get all vehicles
$vehicles = $conn->query("
    SELECT v.vehicle_id, v.registration_number, vt.type_name, vt.max_weight_kg, vt.max_volume_m3, s.site_name, v.notes
    FROM vehicle v
    JOIN vehicle_type vt ON v.vehicle_type_id = vt.vehicle_type_id
    JOIN site s ON v.home_site_id = s.site_id
    ORDER BY v.registration_number ASC
");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-truck"></i> Vehicles</h1>
            <p class="lead">View information about all company vehicles</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Vehicles</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Registration</th>
                            <th>Vehicle Type</th>
                            <th>Max Weight</th>
                            <th>Max Volume</th>
                            <th>Home Site</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($vehicle['registration_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vehicle['type_name']); ?></td>
                                <td><?php echo (int)$vehicle['max_weight_kg']; ?> kg</td>
                                <td><?php echo (int)$vehicle['max_volume_m3']; ?> m³</td>
                                <td><?php echo htmlspecialchars($vehicle['site_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($vehicle['notes'] ?? '', 0, 50)); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
