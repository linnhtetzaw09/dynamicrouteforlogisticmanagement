<?php
session_start();

require_once '../config/db_config.php';

if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

$employee_id = (int) $_SESSION['employee_id'];
$success_message = '';
$error_message = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    if ($first_name === '') {
        $error_message = 'First name is required.';
    } elseif ($last_name === '') {
        $error_message = 'Last name is required.';
    } else {
        $stmt = $conn->prepare("
            UPDATE employee
            SET first_name = ?,
                last_name = ?,
                phone_number = ?
            WHERE employee_id = ?
        ");

        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param(
                'sssi',
                $first_name,
                $last_name,
                $phone_number,
                $employee_id
            );

            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully.';

                // Update the values shown in the header
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
            } else {
                $error_message = 'Failed to update profile.';
            }

            $stmt->close();
        }
    }
}

// Load current employee information
$stmt = $conn->prepare("
    SELECT
        employee_id,
        first_name,
        last_name,
        email,
        phone_number,
        role,
        assigned_site_id,
        is_approved
    FROM employee
    WHERE employee_id = ?
");

$stmt->bind_param('i', $employee_id);
$stmt->execute();

$result = $stmt->get_result();
$employee = $result->fetch_assoc();

$stmt->close();

if (!$employee) {
    die('Employee record not found.');
}

$page_title = 'My Profile';
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="card shadow">
                <div class="card-body p-4">

                    <h2 class="mb-4">
                        <i class="fas fa-user-circle"></i> My Profile
                    </h2>

                    <?php if ($success_message !== ''): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message !== ''): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label for="first_name" class="form-label">
                                First Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="first_name"
                                name="first_name"
                                value="<?php echo htmlspecialchars($employee['first_name']); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">
                                Last Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="last_name"
                                name="last_name"
                                value="<?php echo htmlspecialchars($employee['last_name']); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                class="form-control"
                                id="phone_number"
                                name="phone_number"
                                value="<?php echo htmlspecialchars($employee['phone_number'] ?? ''); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>

                            <input
                                type="email"
                                class="form-control"
                                value="<?php echo htmlspecialchars($employee['email']); ?>"
                                readonly
                            >

                            <small class="text-muted">
                                Email cannot be changed here.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo htmlspecialchars($employee['role']); ?>"
                                readonly
                            >
                        </div>

                        <div class="d-flex gap-2 mt-4">

                            <button
                                type="submit"
                                name="update_profile"
                                class="btn btn-primary"
                            >
                                <i class="fas fa-save"></i> Save Changes
                            </button>

                            <a
                                href="<?php echo ($_SESSION['role'] === 'Admin')
                                    ? '../admin/dashboard.php'
                                    : '../staff/dashboard.php'; ?>"
                                class="btn btn-secondary"
                            >
                                <i class="fas fa-times"></i> Cancel
                            </a>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>