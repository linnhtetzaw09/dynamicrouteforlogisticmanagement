<?php
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// Check staff login
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'Staff') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

$staff_id = intval($_SESSION['employee_id']);

/*
 * Get the staff member's assigned site.
 */
$sql = "
    SELECT 
        s.site_id,
        s.site_name,
        s.latitude,
        s.longitude
    FROM employee e
    JOIN site s ON e.assigned_site_id = s.site_id
    WHERE e.employee_id = ?
      AND s.is_active = 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();
$current_site = $result->fetch_assoc();

if (!$current_site) {
    echo json_encode([
        'success' => false,
        'error' => 'Current site not found.'
    ]);
    exit;
}

/*
 * Get all active company sites except current site.
 */
$sites = [];

$sql_sites = "
    SELECT
        site_id AS location_id,
        site_name AS location_name,
        latitude,
        longitude
    FROM site
    WHERE is_active = 1
      AND site_id != ?
";

$stmt_sites = $conn->prepare($sql_sites);
$stmt_sites->bind_param("i", $current_site['site_id']);
$stmt_sites->execute();

$result_sites = $stmt_sites->get_result();

while ($row = $result_sites->fetch_assoc()) {

    $row['location_type'] = 'site';

    $sites[] = $row;
}

/*
 * Get all active Myanmar places.
 */
$places = [];

$sql_places = "
    SELECT
        place_id AS location_id,
        place_name AS location_name,
        place_type,
        city,
        latitude,
        longitude
    FROM place
    WHERE is_active = 1
";

$result_places = $conn->query($sql_places);

while ($row = $result_places->fetch_assoc()) {

    $row['location_type'] = 'place';

    $places[] = $row;
}

/*
 * Combine sites + places.
 */
$locations = array_merge($sites, $places);

if (empty($locations)) {
    echo json_encode([
        'success' => false,
        'error' => 'No destinations available.'
    ]);
    exit;
}

/*
 * Select random destination.
 */
$random_index = array_rand($locations);
$destination = $locations[$random_index];

/*
 * Return everything required by the route system.
 */
echo json_encode([
    'success' => true,

    'current_location' => [
        'location_type' => 'site',
        'location_id' => $current_site['site_id'],
        'location_name' => $current_site['site_name'],
        'latitude' => floatval($current_site['latitude']),
        'longitude' => floatval($current_site['longitude'])
    ],

    'destination' => [
        'location_type' => $destination['location_type'],
        'location_id' => intval($destination['location_id']),
        'location_name' => $destination['location_name'],
        'place_type' => $destination['place_type'] ?? null,
        'city' => $destination['city'] ?? null,
        'latitude' => floatval($destination['latitude']),
        'longitude' => floatval($destination['longitude'])
    ]
]);
?>