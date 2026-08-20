<?php
/**
 * Pending Approval Page
 * Shown to staff members whose accounts are pending admin approval
 */

session_start();
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$page_title = 'Account Pending Approval';
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8 col-lg-6">
                <!-- Pending Approval Card -->
                <div class="card shadow-lg border-warning">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-hourglass-half fa-5x mb-4" style="color: #f39c12;"></i>
                        
                        <h2 class="card-title mb-3">Account Pending Approval</h2>
                        
                        <p class="card-text lead mb-4">
                            Thank you for registering with Logistics Manager!
                        </p>

                        <div class="alert alert-info" role="alert">
                            <p class="mb-0">
                                Your account has been created successfully and is currently <strong>pending admin approval</strong>. 
                                An administrator will review your registration and approve your account within 24 hours.
                            </p>
                        </div>

                        <h5 class="mt-4 mb-3">What happens next?</h5>
                        <div class="text-start">
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item">
                                    <strong>Admin Review:</strong> An administrator will review your registration details.
                                </li>
                                <li class="list-group-item">
                                    <strong>Verification:</strong> Your information will be verified against company records.
                                </li>
                                <li class="list-group-item">
                                    <strong>Approval:</strong> Once approved, you will receive a notification email.
                                </li>
                                <li class="list-group-item">
                                    <strong>Access Granted:</strong> You will then have full access to the staff portal.
                                </li>
                            </ol>
                        </div>

                        <div class="alert alert-warning mt-4" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Important:</strong> Until your account is approved, you will not be able to access the staff portal or create/manage jobs.
                        </div>

                        <div class="mt-4">
                            <p class="text-muted mb-3">
                                <strong>Account Details:</strong><br>
                                Email: <?php echo htmlspecialchars($_SESSION['email']); ?><br>
                                Name: <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                            </p>
                        </div>

                        <!-- Contact Support -->
                        <div class="card mt-4 bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Need Help?</h6>
                                <p class="card-text small mb-0">
                                    If you have any questions or concerns, please contact our support team at 
                                    <a href="mailto:support@logisticsmanager.com">support@logisticsmanager.com</a> 
                                    or call <a href="tel:+441512345678">0151-234-5678</a>
                                </p>
                            </div>
                        </div>

                        <!-- Logout Button -->
                        <div class="mt-4">
                            <a href="../pages/logout.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Information Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-question-circle"></i> FAQ</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        How long does approval take?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Typically, account approvals are processed within 24 hours during business hours. You will receive an email notification once your account has been approved.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        What if my account is rejected?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        If your account is rejected, you will receive an email with the reason. You can contact support to discuss and potentially reapply with corrected information.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Can I change my details while pending?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        To change your registration details while pending, please contact our support team. They will be able to assist you.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
