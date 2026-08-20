<?php


session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is Admin or Staff
if (!isset($_SESSION['employee_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Sites Reports';
include '../includes/header.php';

// ----------------------
// Filters (for site list only)
// ----------------------
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// ----------------------
// Overall statistics (NOT filtered)
// ----------------------
$total_sites = (int)($conn->query("SELECT COUNT(*) AS count FROM site")->fetch_assoc()['count'] ?? 0);
$total_vehicles = (int)($conn->query("SELECT COUNT(*) AS count FROM vehicle")->fetch_assoc()['count'] ?? 0);
$total_jobs = (int)($conn->query("SELECT COUNT(*) AS count FROM job")->fetch_assoc()['count'] ?? 0);

// ----------------------
// Sites query (filtered list/cards + summary table)
// ----------------------
$query = "SELECT * FROM site WHERE 1=1";

if (!empty($search_query)) {
    $search = $conn->real_escape_string($search_query);
    $query .= " AND (site_name LIKE '%$search%' OR address_city LIKE '%$search%' OR address_line_1 LIKE '%$search%')";
}

$query .= " ORDER BY site_name ASC";
$sites = $conn->query($query);

// ----------------------
// PIE CHART DATA
// If search: show Vehicles + Origin Jobs for first matched site
// Else: show job distribution across all sites (origin+destination)
// ----------------------
$chart_title = "Job Distribution by Site";
$chart_type = "pie";
$chart_labels = [];
$chart_values = [];
$selected_site_id = null;
$selected_site_name = null;

if (!empty($search_query) && $sites && $sites->num_rows > 0) {
    // Use FIRST matched site for pie chart
    $first_site = $sites->fetch_assoc();
    $selected_site_id = (int)$first_site['site_id'];
    $selected_site_name = $first_site['site_name'];

    // IMPORTANT: reset pointer so your cards/table loop still works
    $sites->data_seek(0);

    // Get Vehicles based here
    $vehicles_based = (int)($conn->query("
        SELECT COUNT(*) AS count
        FROM vehicle
        WHERE home_site_id = $selected_site_id
    ")->fetch_assoc()['count'] ?? 0);

    // Get Jobs originating here
    $origin_jobs = (int)($conn->query("
        SELECT COUNT(*) AS count
        FROM job
        WHERE origin_site_id = $selected_site_id
    ")->fetch_assoc()['count'] ?? 0);

    $chart_title = "Site Summary: " . $selected_site_name;
    $chart_type = "doughnut";
    $chart_labels = ["Vehicles Based Here", "Origin Jobs"];
    $chart_values = [$vehicles_based, $origin_jobs];

} else {
    // Default chart: job distribution across all sites (origin + destination)
    $site_jobs_result = $conn->query("
        SELECT
            s.site_id,
            s.site_name,
            COUNT(j.job_id) AS total_jobs
        FROM site s
        LEFT JOIN job j
            ON j.origin_site_id = s.site_id
            OR j.destination_site_id = s.site_id
        GROUP BY s.site_id
        HAVING total_jobs > 0
        ORDER BY total_jobs DESC
    ");

    if ($site_jobs_result) {
        while ($row = $site_jobs_result->fetch_assoc()) {
            $chart_labels[] = $row['site_name'];
            $chart_values[] = (int)$row['total_jobs'];
        }
    }
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-chart-bar"></i> Sites Reports</h1>
            <p class="lead">View detailed information about all distribution sites</p>
        </div>
    </section>

    <div class="container-fluid">

        <!-- Statistics Cards (overall) -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Sites</h6>
                        <h2 class="text-primary"><?php echo $total_sites; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Vehicles</h6>
                        <h2 class="text-success"><?php echo $total_vehicles; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Jobs</h6>
                        <h2 class="text-info"><?php echo $total_jobs; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Avg Vehicles/Site</h6>
                        <h2 class="text-warning">
                            <?php echo $total_sites > 0 ? number_format($total_vehicles / $total_sites, 1) : 0; ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie / Doughnut Chart (dynamic) -->
        <div class="row mb-5 justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> <?php echo htmlspecialchars($chart_title); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($chart_labels)): ?>
                            <div class="chart-wrapper" style="height: 320px;">
                                <canvas id="sitesChart"></canvas>
                            </div>
                            <?php if (!empty($search_query) && $selected_site_id): ?>
                                <p class="text-center text-muted mt-3 mb-0">
                                    Showing chart for <strong><?php echo htmlspecialchars($selected_site_name); ?></strong>
                                    (first matched site).
                                </p>
                            <?php else: ?>
                                <p class="text-center text-muted mt-3 mb-0">
                                    Each slice represents a site based on its total number of jobs (origin + destination).
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i> No data found to display in the chart.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Filter -->
        <div class="card mb-5">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Search Sites</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input
                            type="text"
                            class="form-control"
                            name="search"
                            placeholder="Search by site name or city"
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="reports_sites.php" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sites Details (cards) -->
        <div class="row">
            <?php
            if ($sites && $sites->num_rows > 0) {
                while ($site = $sites->fetch_assoc()):
                    $site_id = (int)$site['site_id'];

                    // Vehicles for this site
                    $vehicles = $conn->query("
                        SELECT v.vehicle_id, v.registration_number, vt.type_name
                        FROM vehicle v
                        JOIN vehicle_type vt ON v.vehicle_type_id = vt.vehicle_type_id
                        WHERE v.home_site_id = $site_id
                        ORDER BY v.registration_number ASC
                    ");

                    // Jobs originating from this site
                    $origin_jobs = (int)($conn->query("SELECT COUNT(*) AS count FROM job WHERE origin_site_id = $site_id")->fetch_assoc()['count'] ?? 0);

                    // Jobs destined to this site
                    $destination_jobs = (int)($conn->query("SELECT COUNT(*) AS count FROM job WHERE destination_site_id = $site_id")->fetch_assoc()['count'] ?? 0);

                    // Employees at this site
                    $employees = (int)($conn->query("SELECT COUNT(*) AS count FROM employee WHERE site_id = $site_id")->fetch_assoc()['count'] ?? 0);

                    $total_site_jobs = $origin_jobs + $destination_jobs;
            ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($site['site_name']); ?>
                                </h5>
                            </div>

                            <div class="card-body">
                                <h6 class="mb-2">Location</h6>
                                <p class="mb-3 text-muted">
                                    <?php echo htmlspecialchars($site['address_line_1']); ?><br>
                                    <?php echo htmlspecialchars($site['address_city'] . ', ' . $site['address_postcode']); ?><br>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($site['contact_phone'] ?? 'Not provided'); ?>
                                </p>

                                <div class="row mb-3">
                                    <div class="col-6 mb-2">
                                        <small class="text-muted">Vehicles</small>
                                        <h5 class="text-success"><?php echo $vehicles ? (int)$vehicles->num_rows : 0; ?></h5>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <small class="text-muted">Employees</small>
                                        <h5 class="text-info"><?php echo $employees; ?></h5>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <small class="text-muted">Origin Jobs</small>
                                        <h5 class="text-warning"><?php echo $origin_jobs; ?></h5>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <small class="text-muted">Destination Jobs</small>
                                        <h5 class="text-danger"><?php echo $destination_jobs; ?></h5>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted">Total Jobs (Origin + Destination)</small>
                                    <h5 class="text-primary mb-0"><?php echo $total_site_jobs; ?></h5>
                                </div>

                                <?php if ($vehicles && $vehicles->num_rows > 0): ?>
                                    <hr>
                                    <h6 class="mb-2">Vehicles Based Here</h6>
                                    <div class="list-group list-group-sm">
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <div class="list-group-item">
                                                <small>
                                                    <strong><?php echo htmlspecialchars($vehicle['registration_number']); ?></strong>
                                                    <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($vehicle['type_name']); ?></span>
                                                </small>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <span class="badge bg-<?php echo ((int)$site['is_active'] === 1) ? 'success' : 'secondary'; ?>">
                                        <?php echo ((int)$site['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                endwhile;
            } else {
                echo '<div class="col-12"><div class="alert alert-info" role="alert"><i class="fas fa-info-circle"></i> No sites found</div></div>';
            }
            ?>
        </div>

        <!-- Summary Table -->
        <div class="card mt-5">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table"></i> Sites Summary Table</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Site Name</th>
                            <th>City</th>
                            <th>Vehicles</th>
                            <th>Employees</th>
                            <th>Origin Jobs</th>
                            <th>Destination Jobs</th>
                            <th>Total Jobs</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($sites && $sites->num_rows > 0) {
                            $sites->data_seek(0);
                            while ($site = $sites->fetch_assoc()):
                                $site_id = (int)$site['site_id'];

                                $vehicle_count = (int)($conn->query("SELECT COUNT(*) AS count FROM vehicle WHERE home_site_id = $site_id")->fetch_assoc()['count'] ?? 0);
                                $employee_count = (int)($conn->query("SELECT COUNT(*) AS count FROM employee WHERE site_id = $site_id")->fetch_assoc()['count'] ?? 0);
                                $origin_count = (int)($conn->query("SELECT COUNT(*) AS count FROM job WHERE origin_site_id = $site_id")->fetch_assoc()['count'] ?? 0);
                                $destination_count = (int)($conn->query("SELECT COUNT(*) AS count FROM job WHERE destination_site_id = $site_id")->fetch_assoc()['count'] ?? 0);
                                $total_count = $origin_count + $destination_count;
                        ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($site['site_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($site['address_city']); ?></td>
                                    <td><?php echo $vehicle_count; ?></td>
                                    <td><?php echo $employee_count; ?></td>
                                    <td><?php echo $origin_count; ?></td>
                                    <td><?php echo $destination_count; ?></td>
                                    <td><strong><?php echo $total_count; ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo ((int)$site['is_active'] === 1) ? 'success' : 'secondary'; ?>">
                                            <?php echo ((int)$site['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                </tr>
                        <?php
                            endwhile;
                        } else {
                            echo '<tr><td colspan="8" class="text-center text-muted">No sites found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("sitesChart");
    if (!el) return;

    const labels = <?php echo json_encode($chart_labels); ?>;
    const values = <?php echo json_encode($chart_values); ?>;
    const chartType = <?php echo json_encode($chart_type); ?>;

    if (!labels.length || !values.length) return;

    new Chart(el, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                data: values
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom" },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw || 0;
                            const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                            return `${context.label}: ${value} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
