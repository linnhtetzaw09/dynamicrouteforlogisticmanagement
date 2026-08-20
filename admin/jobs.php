<?php
/**
 * Jobs Management Page
 * CRUD operations for job management (Admin and Staff access)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Jobs Management';
include '../includes/header.php';

$message = '';
$message_type = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = isset($_POST['form_action']) ? $_POST['form_action'] : '';


    if ($form_action === 'create' || $form_action === 'update') {
    // Get POST data with default values
    $goods_name = isset($_POST['goods_name']) ? trim($_POST['goods_name']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $total_weight_kg = isset($_POST['total_weight_kg']) ? intval($_POST['total_weight_kg']) : 0;
    $total_volume_m3 = isset($_POST['total_volume_m3']) ? intval($_POST['total_volume_m3']) : 0;
    $is_hazardous = isset($_POST['is_hazardous']) ? 1 : 0;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $deadline = isset($_POST['deadline']) ? $_POST['deadline'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'Outstanding';
    $origin_site_id = isset($_POST['origin_site_id']) ? intval($_POST['origin_site_id']) : 0;
    $destination_site_id = isset($_POST['destination_site_id']) ? intval($_POST['destination_site_id']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Get POST data for assigned vehicle
    $assigned_vehicle_id = isset($_POST['assigned_vehicle_id']) ? intval($_POST['assigned_vehicle_id']) : NULL;

    // Get the assigned vehicle's type limits (max weight and volume)
    $vehicle_query = "SELECT vt.max_weight_kg, vt.max_volume_m3
                      FROM vehicle v
                      JOIN vehicle_type vt ON v.vehicle_type_id = vt.vehicle_type_id
                      WHERE v.vehicle_id = ?";
    $vehicle_stmt = $conn->prepare($vehicle_query);
    $vehicle_stmt->bind_param("i", $assigned_vehicle_id);
    $vehicle_stmt->execute();
    $vehicle_result = $vehicle_stmt->get_result();
    $vehicle_data = $vehicle_result->fetch_assoc();
    $vehicle_stmt->close();

    $max_weight_kg = $vehicle_data['max_weight_kg'];
    $max_volume_m3 = $vehicle_data['max_volume_m3'];

    // Calculate the total weight and volume for the job based on quantity and good's weight and volume
    $total_weight_kg = $quantity * $total_weight_kg;
    $total_volume_m3 = $quantity * $total_volume_m3;

    // Validation for required fields
    if (empty($goods_name)) {
        $message = 'Goods Name is required.';
        $message_type = 'danger';
    } elseif ($quantity <= 0) {
        $message = 'Quantity must be a positive number.';
        $message_type = 'danger';
    } elseif ($total_weight_kg <= 0) {
        $message = 'Total Weight (kg) must be a positive number.';
        $message_type = 'danger';
    } elseif ($total_volume_m3 <= 0) {
        $message = 'Total Volume (m³) must be a positive number.';
        $message_type = 'danger';
    } elseif (empty($start_date)) {
        $message = 'Start Date is required.';
        $message_type = 'danger';
    } elseif (empty($deadline)) {
        $message = 'Deadline is required.';
        $message_type = 'danger';
    } elseif ($origin_site_id <= 0) {
        $message = 'Valid Origin Site is required.';
        $message_type = 'danger';
    } elseif ($destination_site_id <= 0) {
        $message = 'Valid Destination Site is required.';
        $message_type = 'danger';
    } elseif (strtotime($start_date) > strtotime($deadline)) {
        $message = 'Start Date cannot be later than Deadline.';
        $message_type = 'danger';
    } elseif ($total_weight_kg > $max_weight_kg) {
        $message = 'Job exceeds the maximum weight limit of the assigned vehicle.';
        $message_type = 'danger';
    } elseif ($total_volume_m3 > $max_volume_m3) {
        $message = 'Job exceeds the maximum volume limit of the assigned vehicle.';
        $message_type = 'danger';
    } else {
        // If validation passes, proceed with either 'create' or 'update' action
        if ($form_action === 'create') {
            $created_employee_id = $_SESSION['employee_id'];

            // Check if assigned vehicle ID is required and valid
            if ($assigned_vehicle_id === NULL) {
                $message = 'Assigned vehicle is required.';
                $message_type = 'danger';
            } else {
                // Prepare the INSERT query
                $query = "INSERT INTO job
                    (goods_name, quantity, total_weight_kg, total_volume_m3, is_hazardous, start_date, deadline, status,
                    origin_site_id, destination_site_id, created_employee_id, description, assigned_vehicle_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($query);

                // Bind parameters
                $stmt->bind_param(
                    'siiiisssiiisi',  
                    $goods_name,      
                    $quantity,        
                    $total_weight_kg, 
                    $total_volume_m3, 
                    $is_hazardous,    
                    $start_date,      
                    $deadline,       
                    $status,          
                    $origin_site_id,  
                    $destination_site_id,
                    $created_employee_id, 
                    $description,     
                    $assigned_vehicle_id  
                );

                // Execute the query
                if ($stmt->execute()) {
                    $message = 'Job created successfully!';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error creating job: ' . $stmt->error;
                    $message_type = 'danger';
                }

                $stmt->close();
            }
        } elseif ($form_action === 'update') {
            $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
            $query = "UPDATE job SET goods_name = ?, quantity = ?, total_weight_kg = ?, total_volume_m3 = ?, is_hazardous = ?, start_date = ?, deadline = ?, status = ?, origin_site_id = ?, destination_site_id = ?, assigned_vehicle_id = ?, description = ? 
                     WHERE job_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                'siddisssiiisi',
                $goods_name,
                $quantity,
                $total_weight_kg,
                $total_volume_m3,
                $is_hazardous,
                $start_date,
                $deadline,
                $status,
                $origin_site_id,
                $destination_site_id,
                $assigned_vehicle_id,
                $description,
                $job_id
            );

            if ($stmt->execute()) {
                $message = 'Job updated successfully!';
                $message_type = 'success';
                $action = 'list';
            } else {
                $message = 'Error updating job. Please try again.';
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}
 elseif ($form_action === 'delete') {
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        
        $query = "DELETE FROM job WHERE job_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $job_id);
        
        if ($stmt->execute()) {
            $message = 'Job deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting job. Please try again.';
            $message_type = 'danger';
        }
        $stmt->close();
        $action = 'list';
    }
}

// Get job data for edit
$job_data = null;
if ($action === 'edit' && $job_id > 0) {
    $query = "SELECT * FROM job WHERE job_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all jobs with related data
$jobs = $conn->query("
    SELECT j.job_id, j.goods_name, j.quantity, j.status, j.deadline, s1.site_name as origin, s2.site_name as destination, j.created_at
    FROM job j
    JOIN site s1 ON j.origin_site_id = s1.site_id
    JOIN site s2 ON j.destination_site_id = s2.site_id
    ORDER BY j.created_at DESC
");

// Get sites for dropdowns
$sites = $conn->query("SELECT * FROM site WHERE is_active = 1 ORDER BY site_name ASC");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-briefcase"></i> Jobs Management</h1>
            <p class="lead">Create, manage, and track logistics jobs</p>
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
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Jobs</h5>
                    <div class="d-flex gap-2">
                        <a href="jobs.php?action=create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create New Job
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Goods Name</th>
                                <th>Origin</th>
                                <th>Destination</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($job = $jobs->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $job['job_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($job['goods_name']); ?></td>
                                    <td><?php echo htmlspecialchars($job['origin']); ?></td>
                                    <td><?php echo htmlspecialchars($job['destination']); ?></td>
                                    <td><?php echo $job['quantity']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                            <?php echo $job['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($job['deadline'])); ?></td>
                                    <td>
                                        <a href="jobs.php?action=edit&id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                                <input type="hidden" name="form_action" value="<?php echo ($action === 'create') ? 'create' : 'update'; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="job_id" value="<?php echo $job_data['job_id']; ?>">
                                <?php endif; ?>

                                <h6 class="mb-3">Goods Information</h6>
                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="goods_name" class="form-label">Goods Name *</label>
                                        <input type="text" class="form-control" id="goods_name" name="goods_name" 
                                            value="<?php echo $job_data ? htmlspecialchars($job_data['goods_name']) : ''; ?>" required >
                                        <div class="invalid-feedback">Goods name is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input  type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $job_data ? $job_data['quantity'] : ''; ?>" required >
                                        <div class="invalid-feedback">Quantity is required.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="total_weight_kg" class="form-label">Total Weight (kg) *</label>
                                        <input type="number" class="form-control" id="total_weight_kg" name="total_weight_kg" value="<?php echo $job_data ? (int)$job_data['total_weight_kg'] : ''; ?>" required >
                                        <div class="invalid-feedback">Weight is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="total_volume_m3" class="form-label">Total Volume (m³) *</label>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            id="total_volume_m3" 
                                            name="total_volume_m3" 
                                            value="<?php echo $job_data ? (int)$job_data['total_volume_m3'] : ''; ?>"
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
                                <div class="row">

                                    <!-- Origin Site Dropdown -->
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="origin_site_id" class="form-label">Origin Site *</label>
                                        <select class="form-select" id="origin_site_id" name="origin_site_id" required>
                                            <option value="">-- Select Origin Site --</option>
                                            <?php 
                                            $sites = $conn->query("SELECT * FROM site WHERE is_active = 1 ORDER BY site_name ASC");
                                            while ($site = $sites->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $site['site_id']; ?>">
                                                    <?php echo htmlspecialchars($site['site_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select an origin site.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="destination_site_id" class="form-label">Destination Site *</label>
                                        <select class="form-select" id="destination_site_id" name="destination_site_id" required>
                                            <option value="">-- Select Destination Site --</option>
                                            <?php 
                                            $sites->data_seek(0);
                                            while ($site = $sites->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $site['site_id']; ?>" 
                                                    <?php echo ($job_data && $job_data['destination_site_id'] == $site['site_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($site['site_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a destination site.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="assigned_vehicle_id" class="form-label">Assigned Vehicle</label>
                                        <select class="form-select" id="assigned_vehicle_id" name="assigned_vehicle_id" required>
                                            <option value="">-- Select Vehicle --</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a vehicle.</div>
                                    </div>


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
document.addEventListener('DOMContentLoaded', function () {
    // Form validation (Bootstrap)
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

    // Add an event listener to the Origin Site dropdown
    const originSiteSelect = document.getElementById('origin_site_id');
    const assignedVehicleSelect = document.getElementById('assigned_vehicle_id');
    
    originSiteSelect.addEventListener('change', function () {
        const originSiteId = this.value; // Get selected Origin Site ID
        
        // Clear the current options in the Assigned Vehicle dropdown
        assignedVehicleSelect.innerHTML = '<option value="">-- Select Vehicle --</option>';
        
        if (originSiteId) {
            // Send an AJAX request to get vehicles for the selected origin site
            fetch('/LHZ/admin/get_vehicles.php?origin_site_id=' + originSiteId)
                .then(response => response.json())  // Expecting JSON response
                .then(data => {
                    if (data.success) {
                        // Populate the Assigned Vehicle dropdown with the response data
                        data.vehicles.forEach(vehicle => {
                            const option = document.createElement('option');
                            option.value = vehicle.vehicle_id;
                            option.textContent = `${vehicle.registration_number} (${vehicle.type_name})`;
                            assignedVehicleSelect.appendChild(option);
                        });
                    } else {
                        // If no vehicles are found, show a default option
                        assignedVehicleSelect.innerHTML = '<option value="">No vehicles available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching vehicles:', error);
                });
        }
    });


});
</script>

<?php include '../includes/footer.php'; ?>
