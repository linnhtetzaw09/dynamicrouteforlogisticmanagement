<?php
/**
 * Admin User Approvals Management
 * Manage pending user registrations and approvals
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'User Approvals';
include '../includes/header.php';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($employee_id > 0 && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $query = "UPDATE employee SET is_approved = 1 WHERE employee_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $employee_id);
            if ($stmt->execute()) {
                $message = 'User account approved successfully!';
                $message_type = 'success';
            }
            $stmt->close();
        } elseif ($action === 'reject') {
            $query = "DELETE FROM employee WHERE employee_id = ? AND is_approved = 0";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $employee_id);
            if ($stmt->execute()) {
                $message = 'User account rejected and deleted.';
                $message_type = 'success';
            }
            $stmt->close();
        }
    }
}

// Get pending approvals
$query = "
    SELECT e.employee_id, e.first_name, e.last_name, e.email, e.phone_number, e.created_at, s.site_name
    FROM employee e
    LEFT JOIN site s ON e.assigned_site_id = s.site_id
    WHERE e.is_approved = 0
    ORDER BY e.created_at DESC
";

$pending_users = $conn->query($query);

// Get approved users
$query = "
    SELECT e.employee_id, e.first_name, e.last_name, e.email, e.phone_number, e.created_at, s.site_name, e.role
    FROM employee e
    LEFT JOIN site s ON e.assigned_site_id = s.site_id
    WHERE e.is_approved = 1
    ORDER BY e.created_at DESC
";

$approved_users = $conn->query($query);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <section class="page-header mb-5">
        <div class="container">
            <h1><i class="fas fa-check-circle"></i> User Approvals</h1>
            <p class="lead">Manage pending user registrations and approvals</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Alert Messages -->
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="card mb-5">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                            <i class="fas fa-hourglass-half"></i> Pending Approvals
                            <span class="badge bg-warning ms-2"><?php echo $pending_users->num_rows; ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                            <i class="fas fa-check-circle"></i> Approved Users
                            <span class="badge bg-success ms-2"><?php echo $approved_users->num_rows; ?></span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Pending Approvals Tab -->
                <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <?php if ($pending_users->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Assigned Site</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $pending_users->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['assigned_site_name'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="employee_id" value="<?php echo $user['employee_id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Approve this user">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reject this user" onclick="return confirm('Are you sure you want to reject this user?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="fas fa-info-circle"></i> No pending approvals at this time.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Approved Users Tab -->
                <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                    <?php if ($approved_users->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Phone</th>
                                        <th>Assigned Site</th>
                                        <th>Approval Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $approved_users->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'Admin' ? 'danger' : 'info'; ?>">
                                                    <?php echo $user['role']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['asssigned_site_name'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="employees.php?action=edit&id=<?php echo $user['employee_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="fas fa-info-circle"></i> No approved users yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Information Card -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Approval Process</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            When a new user registers on the system, their account is created in <strong>Pending</strong> status. 
                            As an administrator, you have the responsibility to review and approve or reject these registrations.
                        </p>
                        <ul>
                            <li><strong>Approve:</strong> Click the "Approve" button to grant the user access to the staff portal. They will be able to log in and start using the system.</li>
                            <li><strong>Reject:</strong> Click the "Reject" button to deny access and remove the registration. The user can register again if needed.</li>
                        </ul>
                        <p class="mb-0">
                            <strong>Note:</strong> Only approved users can access the staff portal and perform operations. Pending users will see a notification that their account is awaiting approval.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
