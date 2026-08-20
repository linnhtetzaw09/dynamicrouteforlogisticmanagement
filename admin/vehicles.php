<?php
/**
 * Vehicles Management Page
 * CRUD operations for vehicle management (Admin only)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Vehicles Management';
include '../includes/header.php';

$message = '';
$message_type = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    if ($form_action === 'create' || $form_action === 'update') {
    $registration_number = isset($_POST['registration_number']) ? trim($_POST['registration_number']) : '';
    $vehicle_type_id = isset($_POST['vehicle_type_id']) ? intval($_POST['vehicle_type_id']) : 0;
    $home_site_id = isset($_POST['home_site_id']) ? intval($_POST['home_site_id']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    // Validation for required fields
    if (empty($registration_number) || $vehicle_type_id === 0 || $home_site_id === 0) {
        $message = 'All required fields must be filled.';
        $message_type = 'danger';
    } else {
        // Check if the registration_number already exists
        $check_query = "SELECT COUNT(*) FROM vehicle WHERE registration_number = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('s', $registration_number);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            // Registration number already exists
            $message = 'The registration number is already in use. Please choose a different one.';
            $message_type = 'danger';
        } else {
            if ($form_action === 'create') {
                // Insert new vehicle
                $query = "INSERT INTO vehicle (registration_number, vehicle_type_id, home_site_id, notes) 
                          VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('siis', $registration_number, $vehicle_type_id, $home_site_id, $notes);
                
                if ($stmt->execute()) {
                    $message = 'Vehicle registered successfully!';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error registering vehicle. Please try again.';
                    $message_type = 'danger';
                }
                $stmt->close();
            } 
        }
            
            if ($form_action === 'update') {
                $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
                $query = "UPDATE vehicle SET registration_number = ?, vehicle_type_id = ?, home_site_id = ?, notes = ? 
                          WHERE vehicle_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('siisi', $registration_number, $vehicle_type_id, $home_site_id, $notes, $vehicle_id);
                
                if ($stmt->execute()) {
                    $message = 'Vehicle updated successfully!';
                    $message_type = 'success';
                    $action = 'list';
                } else {
                    $message = 'Error updating vehicle. Please try again.';
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        
    }
}
elseif ($form_action === 'delete') {
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        
        $query = "DELETE FROM vehicle WHERE vehicle_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $vehicle_id);
        
        if ($stmt->execute()) {
            $message = 'Vehicle deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting vehicle. Please try again.';
            $message_type = 'danger';
        }
        $stmt->close();
        $action = 'list';
    }
}

// Get vehicle data for edit
$vehicle_data = null;
if ($action === 'edit' && $vehicle_id > 0) {
    $query = "SELECT * FROM vehicle WHERE vehicle_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all vehicles with related data
$vehicles = $conn->query("
    SELECT v.vehicle_id, v.registration_number, vt.type_name, s.site_name, vt.max_weight_kg, vt.max_volume_m3
    FROM vehicle v
    JOIN vehicle_type vt ON v.vehicle_type_id = vt.vehicle_type_id
    JOIN site s ON v.home_site_id = s.site_id
    ORDER BY v.registration_number ASC
");


// Get vehicle types and sites for dropdowns
$vehicle_types = $conn->query("SELECT * FROM vehicle_type ORDER BY type_name ASC");
$sites = $conn->query("SELECT * FROM site WHERE is_active = 1 ORDER BY site_name ASC");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-truck"></i> Vehicles Management</h1>
            <p class="lead">Register, manage, and track company vehicles</p>
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
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Vehicles</h5>
                    <a href="vehicles.php?action=create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Register New Vehicle
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Registration</th>
                                <th>Vehicle Type</th>
                                <th>Home Site</th>
                                <th>Max Weight (kg)</th> 
                                <th>Max Volume (m³)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vehicle['registration_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['site_name']); ?></td>
                                    <td><?php echo (int)$vehicle['max_weight_kg']; ?></td>
                                    <td><?php echo (int)$vehicle['max_volume_m3']; ?></td>
                                    <td>
                                        <a href="vehicles.php?action=edit&id=<?php echo $vehicle['vehicle_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['vehicle_id']; ?>">
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
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo $action === 'create' ? '<i class="fas fa-plus"></i> Register New Vehicle' : '<i class="fas fa-edit"></i> Edit Vehicle'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="form_action" value="<?php echo ($action === 'create') ? 'create' : 'update'; ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_data['vehicle_id']; ?>">
                                <?php endif; ?>

                                <div class="form-group mb-3">
                                    <label for="registration_number" class="form-label">Registration Number *</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="registration_number" 
                                        name="registration_number" 
                                        placeholder="e.g., LV21ABC"
                                        value="<?php echo $vehicle_data ? htmlspecialchars($vehicle_data['registration_number']) : ''; ?>"
                                        required
                                    >
                                    <div class="invalid-feedback">Registration number is required.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="vehicle_type_id" class="form-label">Vehicle Type *</label>
                                        <select class="form-select" id="vehicle_type_id" name="vehicle_type_id" required>
                                            <option value="">-- Select Vehicle Type --</option>
                                            <?php 
                                            $vehicle_types->data_seek(0);
                                            while ($vt = $vehicle_types->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $vt['vehicle_type_id']; ?>" 
                                                    <?php echo ($vehicle_data && $vehicle_data['vehicle_type_id'] == $vt['vehicle_type_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($vt['type_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a vehicle type.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="home_site_id" class="form-label">Home Site *</label>
                                        <select class="form-select" id="home_site_id" name="home_site_id" required>
                                            <option value="">-- Select Site --</option>
                                            <?php 
                                            $sites->data_seek(0);
                                            while ($site = $sites->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $site['site_id']; ?>" 
                                                    <?php echo ($vehicle_data && $vehicle_data['home_site_id'] == $site['site_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($site['site_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a home site.</div>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea 
                                        class="form-control" 
                                        id="notes" 
                                        name="notes" 
                                        rows="4" 
                                        placeholder="Add any relevant notes about this vehicle"
                                    ><?php echo $vehicle_data ? htmlspecialchars($vehicle_data['notes']) : ''; ?></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $action === 'create' ? 'Register Vehicle' : 'Update Vehicle'; ?>
                                    </button>
                                    <a href="vehicles.php" class="btn btn-secondary">
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
