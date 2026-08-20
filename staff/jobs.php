<?php
/**
 * Staff Jobs Management Page
 * CRUD operations for jobs (Staff can only manage jobs from their assigned site)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Staff') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Jobs Management';
include '../includes/header.php';

$message = '';
$message_type = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Get Staff's Assigned Site Details
$staff_id = $_SESSION['employee_id'];
$staff_site_query = "SELECT s.site_id, s.site_name FROM site s 
                     JOIN employee e ON e.assigned_site_id = s.site_id 
                     WHERE e.employee_id = ?";
$stmt_site = $conn->prepare($staff_site_query);
$stmt_site->bind_param('i', $staff_id);
$stmt_site->execute();
$staff_site = $stmt_site->get_result()->fetch_assoc();
$staff_site_id = $staff_site['site_id'];
$staff_site_name = $staff_site['site_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    if ($form_action === 'create' || $form_action === 'update') {
        $goods_name = trim($_POST['goods_name'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $total_weight_kg = floatval($_POST['total_weight_kg'] ?? 0);
        $total_volume_m3 = floatval($_POST['total_volume_m3'] ?? 0);
        $is_hazardous = isset($_POST['is_hazardous']) ? 1 : 0;
        $start_date = $_POST['start_date'] ?? '';
        $deadline = $_POST['deadline'] ?? '';
        $status = $_POST['status'] ?? 'Outstanding';
        $destination_site_id = intval($_POST['destination_site_id'] ?? 0);
        $assigned_vehicle_id = intval($_POST['assigned_vehicle_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        // 2. Vehicle Capacity Validation
        $v_query = "SELECT v.max_weight_kg, v.max_volume_m3 FROM vehicle v 
                    JOIN vehicle_type vt ON v.vehicle_type_id = vt.vehicle_type_id 
                    WHERE v.vehicle_id = ?";
        $v_stmt = $conn->prepare($v_query);
        $v_stmt->bind_param('i', $assigned_vehicle_id);
        $v_stmt->execute();
        $vehicle = $v_stmt->get_result()->fetch_assoc();

        if (!$vehicle) {
            $message = 'Invalid vehicle selected.';
            $message_type = 'danger';
        } elseif ($total_weight_kg > $vehicle['max_weight_kg']) {
            $message = "Vehicle capacity exceeded! Max weight: {$vehicle['max_weight_kg']}kg.";
            $message_type = 'danger';
        } elseif ($total_volume_m3 > $vehicle['max_volume_m3']) {
            $message = "Vehicle capacity exceeded! Max volume: {$vehicle['max_volume_m3']}m³.";
            $message_type = 'danger';
        } elseif (empty($goods_name) || $destination_site_id === 0) {
            $message = 'Please fill in all required fields.';
            $message_type = 'danger';
        } else {
            // 3. Database Operations
            if ($form_action === 'create') {
                $query = "INSERT INTO job (goods_name, quantity, total_weight_kg, total_volume_m3, is_hazardous, start_date, deadline, status, origin_site_id, destination_site_id, created_employee_id, assigned_vehicle_id, description) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('siiiisssiiiis', $goods_name, $quantity, $total_weight_kg, $total_volume_m3, $is_hazardous, $start_date, $deadline, $status, $staff_site_id, $destination_site_id, $staff_id, $assigned_vehicle_id, $description);
                
                if ($stmt->execute()) {
                    $message = 'Job created successfully!';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error creating job: ' . $conn->error;
                    $message_type = 'danger';
                }
            } elseif ($form_action === 'update') {
                $job_id = intval($_POST['job_id'] ?? 0);
                $query = "UPDATE job SET goods_name=?, quantity=?, total_weight_kg=?, total_volume_m3=?, is_hazardous=?, start_date=?, deadline=?, status=?, destination_site_id=?, assigned_vehicle_id=?, description=? 
                         WHERE job_id=? AND origin_site_id=?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('siiiisssiiiii', $goods_name, $quantity, $total_weight_kg, $total_volume_m3, $is_hazardous, $start_date, $deadline, $status, $destination_site_id, $assigned_vehicle_id, $description, $job_id, $staff_site_id);
                
                if ($stmt->execute()) {
                    $message = 'Job updated successfully!';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error updating job.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// 4. Fetch Data for the UI
$job_data = null;
if ($action === 'edit' && $job_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM job WHERE job_id = ? AND origin_site_id = ?");
    $stmt->bind_param('ii', $job_id, $staff_site_id);
    $stmt->execute();
    $job_data = $stmt->get_result()->fetch_assoc();
}

// Get available vehicles at staff's site
$vehicles = $conn->query("SELECT vehicle_id, registration_number FROM vehicle WHERE home_site_id = $staff_site_id AND status = 'Available'");

// Get destination sites (excluding origin)
$dest_sites = $conn->query("SELECT site_id, site_name FROM site WHERE is_active = 1 AND site_id != $staff_site_id");

// Get jobs list
$jobs_list = $conn->query("SELECT j.*, s.site_name as destination FROM job j JOIN site s ON j.destination_site_id = s.site_id WHERE j.origin_site_id = $staff_site_id ORDER BY j.created_at DESC");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-briefcase"></i> Jobs Management</h1>
            <p class="lead">Deliver jobs from your assigned site</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="card mb-5">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Your Jobs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Goods Name</th>
                                <th>Destination</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($jobs_list && $jobs_list->num_rows > 0) {
                                while ($job = $jobs_list->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $job['job_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($job['goods_name']); ?></td>
                                    <td><?php echo htmlspecialchars($job['destination']); ?></td>
                                    <td><?php echo $job['quantity']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                            <?php echo $job['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($job['deadline'])); ?></td>
                                    <td>
                                        <a href="best_route.php?job_id=<?php echo $job['job_id']; ?>" 
                                        class="btn btn-sm btn-success">
                                            <i class="fas fa-route"></i> Suggest Route
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted">No jobs found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <!-- Form View -->
            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $action === 'create' ? '<i class="fas fa-plus"></i> Create New Job' : '<i class="fas fa-edit"></i> Edit Job'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="job_id" value="<?php echo $job_data['job_id']; ?>">
                                <?php endif; ?>

                                <h6 class="mb-3">Goods Information</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="goods_name" class="form-label">Goods Name *</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="goods_name" 
                                            name="goods_name" 
                                            value="<?php echo $job_data ? htmlspecialchars($job_data['goods_name']) : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Goods name is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            id="quantity" 
                                            name="quantity" 
                                            value="<?php echo $job_data ? $job_data['quantity'] : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Quantity is required.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="total_weight_kg" class="form-label">Total Weight (kg) *</label>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            class="form-control" 
                                            id="total_weight_kg" 
                                            name="total_weight_kg" 
                                            value="<?php echo $job_data ? $job_data['total_weight_kg'] : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Weight is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="total_volume_m3" class="form-label">Total Volume (m³) *</label>
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            class="form-control" 
                                            id="total_volume_m3" 
                                            name="total_volume_m3" 
                                            value="<?php echo $job_data ? $job_data['total_volume_m3'] : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Volume is required.</div>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="is_hazardous" 
                                        name="is_hazardous"
                                        <?php echo ($job_data && $job_data['is_hazardous']) ? 'checked' : ''; ?>
                                    >
                                    <label class="form-check-label" for="is_hazardous">
                                        Hazardous Materials
                                    </label>
                                </div>

                                <hr>

                                <h6 class="mb-3">Route Information</h6>

<!-- Origin Site: Locked to Staff's Site -->
<div class="alert alert-info">
    <strong>Origin Site:</strong> <?php echo htmlspecialchars($staff_site_name); ?>
    <!-- Hidden input ensures the site_id is still sent with the form -->
    <input type="hidden" name="origin_site_id" value="<?php echo $staff_site_id; ?>">
</div>

<!-- Destination Site: Can choose any site except the origin -->
<div class="form-group mb-3">
    <label for="destination_site_id" class="form-label">Destination Site *</label>
    <select class="form-select" id="destination_site_id" name="destination_site_id" required>
        <option value="">-- Select Destination Site --</option>
        <?php
        if ($dest_sites && $dest_sites->num_rows > 0):
            while ($site = $dest_sites->fetch_assoc()):
        ?>
                <option
                    value="<?php echo $site['site_id']; ?>"
                    <?php
                    echo (
                        $job_data &&
                        $job_data['destination_site_id'] == $site['site_id']
                    ) ? 'selected' : '';
                    ?>
                >
                    <?php echo htmlspecialchars($site['site_name']); ?>
                </option>
        <?php
            endwhile;
        else:
        ?>
            <option value="" disabled>No destination sites found</option>
        <?php endif; ?>
    </select>
</div>

<!-- Assigned Vehicle: Filtered to only show vehicles at the Origin Site -->
<div class="col-md-6 form-group mb-3">
    <label for="assigned_vehicle_id" class="form-label">Assigned Vehicle *</label>
    <select class="form-select" id="assigned_vehicle_id" name="assigned_vehicle_id" required>
        <option value="">-- Select Vehicle at <?php echo htmlspecialchars($staff_site_name); ?> --</option>
        <?php if ($vehicles && $vehicles->num_rows > 0): ?>
            <?php while ($v = $vehicles->fetch_assoc()): ?>
                <option
                    value="<?php echo $v['vehicle_id']; ?>"
                    <?php
                    echo (
                        $job_data &&
                        $job_data['assigned_vehicle_id'] == $v['vehicle_id']
                    ) ? 'selected' : '';
                    ?>
                >
                    <?php echo htmlspecialchars($v['registration_number']); ?>
                </option>
            <?php endwhile; ?>
        <?php else: ?>
            <option value="" disabled>No available vehicles found</option>
        <?php endif; ?>
    </select>
    <div class="invalid-feedback">Please select a vehicle available at your site.</div>
</div>


                                <hr>

                                <h6 class="mb-3">Timeline & Status</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="start_date" class="form-label">Start Date *</label>
                                        <input 
                                            type="date" 
                                            class="form-control" 
                                            id="start_date" 
                                            name="start_date" 
                                            value="<?php echo $job_data ? $job_data['start_date'] : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Start date is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="deadline" class="form-label">Deadline *</label>
                                        <input 
                                            type="date" 
                                            class="form-control" 
                                            id="deadline" 
                                            name="deadline" 
                                            value="<?php echo $job_data ? $job_data['deadline'] : ''; ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Deadline is required.</div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Outstanding" <?php echo ($job_data && $job_data['status'] === 'Outstanding') ? 'selected' : ''; ?>>Outstanding</option>
                                        <option value="In Progress" <?php echo ($job_data && $job_data['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Completed" <?php echo ($job_data && $job_data['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea 
                                        class="form-control" 
                                        id="description" 
                                        name="description" 
                                        rows="4"
                                    ><?php echo $job_data ? htmlspecialchars($job_data['description']) : ''; ?></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $action === 'create' ? 'Create Job' : 'Update Job'; ?>
                                    </button>
                                    <a href="jobs.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
