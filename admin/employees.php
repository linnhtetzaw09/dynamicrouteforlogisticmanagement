<?php
/**
 * Employees Management Page
 * CRUD operations for employee management (Admin only)
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Employees Management';
include '../includes/header.php';

$message = '';
$message_type = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    if ($form_action === 'update') {
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
        $role = isset($_POST['role']) ? $_POST['role'] : 'Staff';
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;

        // Validation
        if (empty($first_name) || empty($last_name)) {
            $message = 'First name and last name are required.';
            $message_type = 'danger';
        } else {
            $query = "UPDATE employee SET first_name = ?, last_name = ?, phone_number = ?, role = ?, site_id = ? 
                     WHERE employee_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssssii', $first_name, $last_name, $phone_number, $role, $site_id, $employee_id);
            
            if ($stmt->execute()) {
                $message = 'Employee updated successfully!';
                $message_type = 'success';
                $action = 'list';
            } else {
                $message = 'Error updating employee. Please try again.';
                $message_type = 'danger';
            }
            $stmt->close();
        }
    } elseif ($form_action === 'delete') {
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        
        // Prevent deleting the current user
        if ($employee_id === $_SESSION['employee_id']) {
            $message = 'You cannot delete your own account.';
            $message_type = 'danger';
        } else {
            $query = "DELETE FROM employee WHERE employee_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $employee_id);
            
            if ($stmt->execute()) {
                $message = 'Employee deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting employee. Please try again.';
                $message_type = 'danger';
            }
            $stmt->close();
        }
        $action = 'list';
    }
}

// Get employee data for edit
$employee_data = null;
if ($action === 'edit' && $employee_id > 0) {
    $query = "SELECT * FROM employee WHERE employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee_data = $result->fetch_assoc();
    $stmt->close();
}

// Get all employees
$employees = $conn->query("
    SELECT e.employee_id, e.first_name, e.last_name, e.email, e.phone_number, e.role, e.is_approved, s.site_name
    FROM employee e
    LEFT JOIN site s ON e.assigned_site_id = s.site_id
    ORDER BY e.first_name ASC
");

// Get sites for dropdown
$sites = $conn->query("SELECT * FROM site WHERE is_active = 1 ORDER BY site_name ASC");
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-users"></i> Employees Management</h1>
            <p class="lead">Manage staff members and their roles</p>
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
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Employees</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Site</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($emp = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['phone_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $emp['role'] === 'Admin' ? 'danger' : 'info'; ?>">
                                            <?php echo $emp['role']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['site_name'] ?? 'Not assigned'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $emp['is_approved'] ? 'success' : 'warning'; ?>">
                                            <?php echo $emp['is_approved'] ? 'Approved' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="employees.php?action=edit&id=<?php echo $emp['employee_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($emp['employee_id'] !== $_SESSION['employee_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="employee_id" value="<?php echo $emp['employee_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'edit'): ?>
            <!-- Edit Form View -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Employee</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="form_action" value="update">
                                <input type="hidden" name="employee_id" value="<?php echo $employee_data['employee_id']; ?>">

                                <div class="alert alert-info" role="alert">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($employee_data['email']); ?> (Cannot be changed)
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="first_name" 
                                            name="first_name" 
                                            value="<?php echo htmlspecialchars($employee_data['first_name']); ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">First name is required.</div>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="last_name" 
                                            name="last_name" 
                                            value="<?php echo htmlspecialchars($employee_data['last_name']); ?>"
                                            required
                                        >
                                        <div class="invalid-feedback">Last name is required.</div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="phone_number" class="form-label">Phone Number</label>
                                    <input 
                                        type="tel" 
                                        class="form-control" 
                                        id="phone_number" 
                                        name="phone_number" 
                                        value="<?php echo htmlspecialchars($employee_data['phone_number'] ?? ''); ?>"
                                    >
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="role" class="form-label">Role *</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="Staff" <?php echo $employee_data['role'] === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                                            <option value="Admin" <?php echo $employee_data['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 form-group mb-3">
                                        <label for="site_id" class="form-label">Assigned Site</label>
                                        <select class="form-select" id="site_id" name="site_id">
                                            <option value="">-- Not Assigned --</option>
                                            <?php 
                                            $sites->data_seek(0);
                                            while ($site = $sites->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $site['site_id']; ?>" 
                                                    <?php echo ($employee_data['site_id'] == $site['site_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($site['site_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="alert alert-warning" role="alert">
                                    <strong>Approval Status:</strong> 
                                    <span class="badge bg-<?php echo $employee_data['is_approved'] ? 'success' : 'warning'; ?>">
                                        <?php echo $employee_data['is_approved'] ? 'Approved' : 'Pending'; ?>
                                    </span>
                                    <small class="d-block mt-2">To approve pending users, go to <a href="approvals.php">User Approvals</a></small>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Employee
                                    </button>
                                    <a href="employees.php" class="btn btn-secondary">
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
