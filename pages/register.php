<?php
/**
 * Registration Page
 * New user registration with admin approval requirement
 */

session_start();

// Redirect if already logged in
if (isset($_SESSION['employee_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'Admin' ? '../admin/dashboard.php' : '../staff/dashboard.php'));
    exit;
}

$page_title = 'Register';
include '../includes/header.php';

$success_message = '';
$error_message = '';

// Check for success message from session
if (isset($_SESSION['registration_success'])) {
    $success_message = 'Registration successful! Your account is pending admin approval. You will be notified once approved.';
    unset($_SESSION['registration_success']); // Clear it so it doesn't show again on refresh
}

// Check for error messages from session
if (isset($_SESSION['registration_error'])) {
    $error_message = $_SESSION['registration_error'];
    unset($_SESSION['registration_error']);
}

if (isset($_SESSION['registration_errors'])) {
    $error_message = implode('<br>', $_SESSION['registration_errors']);
    unset($_SESSION['registration_errors']);
}
?>

<div class="container-fluid">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8 col-lg-6">
                <!-- Registration Card -->
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4">
                            <i class="fas fa-user-plus"></i> Create Account
                        </h2>

                        <!-- Success Alert -->
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <div class="text-center mt-4">
                                <p class="mb-3">Redirecting to login page...</p>
                                <a href="login.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php else: ?>

                        <!-- Info Alert -->
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Important:</strong> Your account will be pending admin approval. Once approved, you will have access to the staff portal.
                        </div>

                        <!-- Error Alert -->
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form method="POST" action="register_process.php" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- First Name -->
                                <div class="col-md-6 form-group mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" placeholder="John" required>
                                    <div class="invalid-feedback">
                                        Please provide your first name.
                                    </div>
                                </div>

                                <!-- Last Name -->
                                <div class="col-md-6 form-group mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Doe" required>
                                    <div class="invalid-feedback">
                                        Please provide your last name.
                                    </div>
                                </div>
                            </div>

                            <!-- Email Field -->
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@example.com" required autocomplete="email">
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>

                            <!-- Phone Number -->
                            <div class="form-group mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    id="phone" 
                                    name="phone_number" 
                                    placeholder="01234 567890"
                                >
                                <small class="form-text text-muted">Optional</small>
                            </div>

                            <!-- Site Selection -->
                            <div class="form-group mb-3">
                                <label for="site" class="form-label">Assigned Site</label>
                                <select class="form-select" id="site" name="assigned_site_id" required>
                                    <option value="">-- Select a site --</option>
                                    <option value="1">Liverpool Hub</option>
                                    <option value="2">Manchester Distribution Center</option>
                                    <option value="3">Birmingham Logistics</option>
                                    <option value="4">London Central</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a site.
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Enter a strong password" 
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button 
                                        class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword"
                                        aria-label="Toggle password visibility"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Minimum 8 characters, including uppercase, lowercase, and numbers</small>
                                <div class="invalid-feedback">
                                    Please provide a strong password.
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirmPassword" 
                                    name="confirm_password" 
                                    placeholder="Confirm your password" 
                                    required
                                    autocomplete="new-password"
                                >
                                <div class="invalid-feedback">
                                    Passwords must match.
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="form-check mb-4">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="termsCheck" 
                                    name="terms_accepted"
                                    required
                                >
                                <label class="form-check-label" for="termsCheck">
                                    I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>
                                </label>
                                <div class="invalid-feedback">
                                    You must accept the terms and conditions.
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" name="register" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </form>

                        <!-- Divider -->
                        <hr class="my-4">

                        <!-- Login Link -->
                        <div class="text-center">
                            <p class="mb-0">
                                <small>Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></small>
                            </p>
                        </div>

                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-lock"></i> Account Status</h6>
                        <small class="text-muted">
                            New accounts are created in <strong>Pending</strong> status. An administrator will review and approve your account within 24 hours. You will receive a notification once your account is approved.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    console.log("jQuery is loaded!");
    // Toggle password visibility
    $('#togglePassword').click(function() {
        const passwordInput = $('#password');
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            // Validate password match
            const password = $('#password').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (password !== confirmPassword) {
                $('#confirmPassword')[0].setCustomValidity('Passwords do not match');
            } else {
                $('#confirmPassword')[0].setCustomValidity('');
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Real-time password match validation
    $('#confirmPassword').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        
        if (password !== confirmPassword) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>