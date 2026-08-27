<?php
/**
 * Staff Jobs Management Page
 *
 * Staff / Drivers can:
 * - View jobs
 * - View Company Site destinations
 * - View Myanmar Place destinations
 * - Suggest routes
 *

 
 * Random destination is handled separately by random_destination.php
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


// ============================================================
// 1. GET STAFF'S ASSIGNED SITE
// ============================================================

$staff_id = $_SESSION['employee_id'];

$staff_site_query = "
    SELECT
        s.site_id,
        s.site_name,
        s.latitude,
        s.longitude
    FROM site s
    JOIN employee e
        ON e.assigned_site_id = s.site_id
    WHERE e.employee_id = ?
      AND s.is_active = 1
";

$stmt_site = $conn->prepare($staff_site_query);
$stmt_site->bind_param('i', $staff_id);
$stmt_site->execute();

$staff_site = $stmt_site->get_result()->fetch_assoc();

if (!$staff_site) {
    die('Your assigned site could not be found.');
}

$staff_site_id = intval($staff_site['site_id']);
$staff_site_name = $staff_site['site_name'];


// ============================================================
// 2. GET JOB LIST
// ============================================================

$jobs_list = $conn->query("
    SELECT
        j.*,

        s.site_name AS destination_site_name,

        p.place_name AS destination_place_name,
        p.place_type AS destination_place_type,
        p.city AS destination_place_city

    FROM job j

    LEFT JOIN site s
        ON j.destination_site_id = s.site_id

    LEFT JOIN place p
        ON j.destination_place_id = p.place_id

    WHERE j.origin_site_id = $staff_site_id

    ORDER BY j.created_at DESC
");

?>

<div class="container-fluid">

    <!-- ===================================================== -->
    <!-- PAGE HEADER -->
    <!-- ===================================================== -->

    <section class="page-header mb-5">

        <div class="container">

            <h1>
                <i class="fas fa-briefcase"></i>
                Jobs Management
            </h1>

            <p class="lead">
                View and manage your assigned delivery jobs
            </p>

        </div>

    </section>


    <div class="container-fluid">

        <!-- ================================================= -->
        <!-- ALERT MESSAGE -->
        <!-- ================================================= -->

        <?php if (!empty($message)): ?>

            <div
                class="alert alert-<?php echo $message_type; ?>
                       alert-dismissible fade show"
                role="alert"
            >

                <i class="fas fa-<?php
                    echo $message_type === 'success'
                        ? 'check-circle'
                        : 'exclamation-circle';
                ?>"></i>

                <?php echo htmlspecialchars($message); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        <?php endif; ?>


        <!-- ================================================= -->
        <!-- JOB LIST -->
        <!-- ================================================= -->

        <div class="card mb-5">

            <div class="card-header">

                <h5 class="mb-0">

                    <i class="fas fa-list"></i>
                    Available Jobs

                </h5>

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

                    if (
                        $jobs_list &&
                        $jobs_list->num_rows > 0
                    ):

                        while (
                            $job =
                            $jobs_list->fetch_assoc()
                        ):

                            // ====================================
                            // Determine destination
                            // ====================================

                            if (
                                !empty(
                                    $job[
                                        'destination_site_name'
                                    ]
                                )
                            ) {

                                $destination_display =
                                    $job[
                                        'destination_site_name'
                                    ];

                                $destination_type =
                                    'Company Site';

                            } elseif (
                                !empty(
                                    $job[
                                        'destination_place_name'
                                    ]
                                )
                            ) {

                                $destination_display =
                                    $job[
                                        'destination_place_name'
                                    ];

                                $destination_type =
                                    $job[
                                        'destination_place_type'
                                    ];

                            } else {

                                $destination_display =
                                    'Unknown';

                                $destination_type = '';

                            }

                    ?>

                        <tr>

                            <!-- JOB ID -->

                            <td>

                                <strong>
                                    #<?php echo $job['job_id']; ?>
                                </strong>

                            </td>


                            <!-- GOODS -->

                            <td>

                                <?php echo htmlspecialchars(
                                    $job['goods_name']
                                ); ?>

                            </td>


                            <!-- DESTINATION -->

                            <td>

                                <strong>
                                    <?php echo htmlspecialchars(
                                        $destination_display
                                    ); ?>
                                </strong>

                                <br>

                                <small class="text-muted">

                                    <?php echo htmlspecialchars(
                                        $destination_type
                                    ); ?>

                                    <?php

                                    if (
                                        $destination_type !==
                                        'Company Site' &&
                                        !empty(
                                            $job[
                                                'destination_place_city'
                                            ]
                                        )
                                    ):

                                    ?>

                                        -
                                        <?php echo htmlspecialchars(
                                            $job[
                                                'destination_place_city'
                                            ]
                                        ); ?>

                                    <?php endif; ?>

                                </small>

                            </td>


                            <!-- QUANTITY -->

                            <td>

                                <?php echo $job['quantity']; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="badge badge-<?php
                                        echo strtolower(
                                            str_replace(
                                                ' ',
                                                '-',
                                                $job['status']
                                            )
                                        );
                                    ?>"
                                >

                                    <?php echo htmlspecialchars(
                                        $job['status']
                                    ); ?>

                                </span>

                            </td>


                            <!-- DEADLINE -->

                            <td>

                                <?php echo date(
                                    'd M Y',
                                    strtotime(
                                        $job['deadline']
                                    )
                                ); ?>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <a
                                    href="best_route.php?job_id=<?php
                                        echo $job['job_id'];
                                    ?>"
                                    class="btn btn-sm btn-success"
                                >

                                    <i class="fas fa-route"></i>
                                    Suggest Route

                                </a>

                            </td>

                        </tr>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-muted"
                            >

                                No jobs found

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<?php include '../includes/footer.php'; ?>