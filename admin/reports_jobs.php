<?php

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is Admin or Staff
if (!isset($_SESSION['employee_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Job Reports';
include '../includes/header.php';

// Get filter parameters (affects BOTH table + pie chart now)
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query  = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to       = isset($_GET['date_to']) ? $_GET['date_to'] : '';


$where = " WHERE 1=1 ";

if (!empty($status_filter)) {
    $where .= " AND j.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!empty($search_query)) {
    $search = $conn->real_escape_string($search_query);
    $where .= " AND (j.goods_name LIKE '%$search%' OR s1.site_name LIKE '%$search%' OR s2.site_name LIKE '%$search%')";
}

if (!empty($date_from)) {
    $where .= " AND j.start_date >= '" . $conn->real_escape_string($date_from) . "'";
}

if (!empty($date_to)) {
    $where .= " AND j.deadline <= '" . $conn->real_escape_string($date_to) . "'";
}

// ----------------------
// Table query (filtered)
// ----------------------
$query = "
    SELECT j.job_id, j.goods_name, j.quantity, j.total_weight_kg, j.total_volume_m3, j.is_hazardous,
           j.status, j.start_date, j.deadline,
           s1.site_name AS origin, s2.site_name AS destination,
           CONCAT(e.first_name, ' ', e.last_name) AS created_by,
           j.created_at
    FROM job j
    JOIN site s1 ON j.origin_site_id = s1.site_id
    JOIN site s2 ON j.destination_site_id = s2.site_id
    JOIN employee e ON j.created_employee_id = e.employee_id
    $where
    ORDER BY j.created_at DESC
";

$jobs = $conn->query($query);

// ----------------------
// Stats query (SAME filters as table)
// This is what the pie chart uses now.
// ----------------------
$stats_query = "
    SELECT
        COUNT(*) AS total_jobs,
        SUM(CASE WHEN j.status = 'Outstanding' THEN 1 ELSE 0 END) AS outstanding,
        SUM(CASE WHEN j.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(j.total_weight_kg) AS total_weight,
        SUM(j.total_volume_m3) AS total_volume,
        SUM(CASE WHEN j.is_hazardous = 1 THEN 1 ELSE 0 END) AS hazardous_count
    FROM job j
    JOIN site s1 ON j.origin_site_id = s1.site_id
    JOIN site s2 ON j.destination_site_id = s2.site_id
    $where
";

$stats = $conn->query($stats_query)->fetch_assoc();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-chart-bar"></i> Job Reports</h1>
            <p class="lead">Generate reports and search for job information</p>
        </div>
    </section>

    <div class="container-fluid">

        <!-- Statistics Cards (filtered now) -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Jobs</h6>
                        <h2 class="text-primary"><?php echo (int)($stats['total_jobs'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Outstanding</h6>
                        <h2 class="text-warning"><?php echo (int)($stats['outstanding'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Completed</h6>
                        <h2 class="text-success"><?php echo (int)($stats['completed'] ?? 0); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart (filtered now) -->
        <div class="row mb-5 justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Job Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper" style="height: 320px;">
                            <canvas id="jobsPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-5">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Search & Filter</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            class="form-control"
                            id="search"
                            name="search"
                            placeholder="Search by goods name or site"
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- All Status --</option>
                            <option value="Outstanding" <?php echo $status_filter === 'Outstanding' ? 'selected' : ''; ?>>Outstanding</option>
                            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            type="date"
                            class="form-control allow-any-date"
                            id="date_from"
                            name="date_from"
                            value="<?php echo htmlspecialchars($date_from); ?>"
                        >
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            type="date"
                            class="form-control allow-any-date"
                            id="date_to"
                            name="date_to"
                            value="<?php echo htmlspecialchars($date_to); ?>"
                        >
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-3">
                    <a href="reports_jobs.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                    <button onclick="exportTableToCSV('#jobsTable', 'job_report.csv')" class="btn btn-success btn-sm" type="button">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Job Results (<?php echo (int)($jobs ? $jobs->num_rows : 0); ?> found)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="jobsTable">
                    <thead>
                        <tr>
                            <th>Job ID</th>
                            <th>Goods Name</th>
                            <th>Origin → Destination</th>
                            <th>Qty</th>
                            <th>Weight (kg)</th>
                            <th>Volume (m³)</th>
                            <th>Hazardous</th>
                            <th>Status</th>
                            <th>Deadline</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($jobs && $jobs->num_rows > 0): ?>
                            <?php while ($job = $jobs->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$job['job_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($job['goods_name']); ?></td>
                                    <td><?php echo htmlspecialchars($job['origin']); ?> → <?php echo htmlspecialchars($job['destination']); ?></td>
                                    <td><?php echo (int)$job['quantity']; ?></td>
                                    <td><?php echo (int)$job['total_weight_kg']; ?></td>
                                    <td><?php echo (int)$job['total_volume_m3']; ?></td>

                                    <td>
                                        <span class="badge bg-<?php echo ((int)$job['is_hazardous'] === 1) ? 'danger' : 'success'; ?>">
                                            <?php echo ((int)$job['is_hazardous'] === 1) ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $status_class = strtolower(str_replace(' ', '-', $job['status'])); ?>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($job['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $job['deadline'] ? date('d M Y', strtotime($job['deadline'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($job['created_by']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No jobs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Statistics (filtered now) -->
        <div class="row mt-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Summary Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <h6 class="text-muted">Total Weight</h6>
                                <h3 class="text-primary"><?php echo (int)($stats['total_weight'] ?? 0); ?> kg</h3>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h6 class="text-muted">Total Volume</h6>
                                <h3 class="text-info"><?php echo (int)($stats['total_volume'] ?? 0); ?> m³</h3>
                            </div>
                            <div class="col-md-4 mb-3">
                                <h6 class="text-muted">Hazardous Jobs</h6>
                                <h3 class="text-danger"><?php echo (int)($stats['hazardous_count'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const el = document.getElementById("jobsPieChart");
    if (!el) return;

    const dataValues = [
        <?php echo (int)($stats['outstanding'] ?? 0); ?>,
        <?php echo (int)($stats['completed'] ?? 0); ?>
    ];

    const allZero = dataValues.every(v => v === 0);

    new Chart(el, {
        type: "doughnut",
        data: {
            labels: ["Outstanding", "Completed"],
            datasets: [{
                data: allZero ? [1] : dataValues
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { boxWidth: 14, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (allZero) return "No data for current filters";
                            const label = ctx.label || "";
                            const val = ctx.parsed || 0;
                            return `${label}: ${val}`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
