<?php
// Include database connection
require_once '../config/db_config.php';

// Check if origin_site_id is provided
if (isset($_GET['origin_site_id'])) {
    $origin_site_id = intval($_GET['origin_site_id']);

    // Prepare the query to get vehicles for the selected origin site
    $query = "SELECT v.vehicle_id, v.registration_number, vt.type_name 
              FROM vehicle v
              JOIN vehicle_type vt ON v.vehicle_type_id = vt.vehicle_type_id
              WHERE v.home_site_id = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $origin_site_id); // Bind the origin site ID
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Prepare an array to store the vehicle data
        $vehicles = [];
        
        while ($vehicle = $result->fetch_assoc()) {
            $vehicles[] = $vehicle;
        }

        // Return the response as JSON
        echo json_encode(['success' => true, 'vehicles' => $vehicles]);

        $stmt->close();
    } else {
        // If there was an error preparing the statement
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve vehicles']);
    }
}
?>
