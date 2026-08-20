<?php
session_start();
include "../config/db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$job_id = $data["job_id"];
$vehicle_id = $data["vehicle_id"] ?? null;
$origin_lat = $data["origin_lat"];
$origin_lon = $data["origin_lon"];
$dest_lat = $data["dest_lat"];
$dest_lon = $data["dest_lon"];
$best_route = $data["best_route"];
$routes = $data["routes"];
$predicted_times = $data["predicted_times"];

$bestDistance = 0;
$bestApiTime = 0;
$bestPredictedTime = 0;
$bestPolyline = "";

foreach ($routes as $route) {
    if ($route["route"] == $best_route) {
        $bestDistance = $route["distance_km"];
        $bestApiTime = intval($route["api_time_min"]);
        $bestPredictedTime = intval($predicted_times[$route["route"]]);
        $bestPolyline = json_encode($route["geometry"]);
    }
}

$sql = "
INSERT INTO route_optimization
(job_id, vehicle_id, api_provider, algorithm_used,
 origin_latitude, origin_longitude, destination_latitude, destination_longitude,
 best_route_name, optimized_distance_km, estimated_duration_minutes,
 lstm_predicted_duration_minutes, polyline)
VALUES
(?, ?, 'OpenRouteService', 'LSTM',
 ?, ?, ?, ?,
 ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iiddddsdiis",
    $job_id,
    $vehicle_id,
    $origin_lat,
    $origin_lon,
    $dest_lat,
    $dest_lon,
    $best_route,
    $bestDistance,
    $bestApiTime,
    $bestPredictedTime,
    $bestPolyline
);

$stmt->execute();
$route_id = $conn->insert_id;

foreach ($routes as $route) {
    $routeName = $route["route"];
    $distance = $route["distance_km"];
    $apiTime = intval($route["api_time_min"]);
    $predictedTime = intval($predicted_times[$routeName]);
    $selected = ($routeName == $best_route) ? 1 : 0;
    $polyline = json_encode($route["geometry"]);

    $trafficLevel = "Light";
    if ($predictedTime > 40) {
        $trafficLevel = "Heavy";
    } elseif ($predictedTime > 25) {
        $trafficLevel = "Moderate";
    }

    $sqlAlt = "
    INSERT INTO route_alternative
    (route_id, route_name, distance_km, api_duration_minutes,
     lstm_predicted_duration_minutes, traffic_level, selected_route, polyline)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtAlt = $conn->prepare($sqlAlt);
    $stmtAlt->bind_param(
        "isdiisis",
        $route_id,
        $routeName,
        $distance,
        $apiTime,
        $predictedTime,
        $trafficLevel,
        $selected,
        $polyline
    );

    $stmtAlt->execute();
}

echo json_encode([
    "success" => true,
    "route_id" => $route_id
]);
?>