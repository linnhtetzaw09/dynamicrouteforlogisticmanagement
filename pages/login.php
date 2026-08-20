<?php
/**
 * Login Page
 * User authentication and login functionality
 */

session_start();

// Redirect if already logged in
if (isset($_SESSION['employee_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'Admin' ? '../admin/dashboard.php' : '../staff/dashboard.php'));
    exit;
}

$page_title = 'Login';
include '../includes/header.php';

// Display error message if login failed
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $error_message = 'Invalid email or password. Please try again.';
}
?>

<div class="container-fluid">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <!-- Login Card -->
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </h2>

                        <!-- Error Alert -->
                        <?php 
                        if (isset($_SESSION['login_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['login_error']); ?>
                        <?php endif; ?>


                        <!-- Login Form -->
                        <form method="POST" action="login_process.php" class="needs-validation" novalidate>
                            <!-- Email Field -->
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required autocomplete="email">
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Please provide your password.
                                </div>
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="form-check mb-4">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="rememberMe" 
                                    name="remember_me"
                                >
                                <label class="form-check-label" for="rememberMe">
                                    Remember me
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>

                        <!-- Divider -->
                        <hr class="my-4">

                        <!-- Additional Links -->
                        <div class="text-center">
                            <p class="mb-2">
                                <small>Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></small>
                            </p>
                            <p>
                                <small><a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot your password?</a></small>
                            </p>
                        </div>

                        
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-shield-alt"></i> Security Notice</h6>
                        <small class="text-muted">
                            Your login credentials are encrypted and securely transmitted. Never share your password with anyone.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Enter your email address and we'll send you instructions to reset your password.</p>
                <form id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="resetEmail" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="resetEmail" 
                            name="email" 
                            placeholder="Enter your email" 
                            required
                        >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendResetBtn">Send Reset Link</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
$(document).ready(function() {
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
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });


    // Send password reset
    $('#sendResetBtn').click(function() {
        const email = $('#resetEmail').val();
        if (email) {
            // In a real application, this would send an email
            alert('Password reset instructions have been sent to ' + email);
            $('#forgotPasswordModal').modal('hide');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
